#!/usr/bin/env bash
#
# SpMail - instalador de servidor (Ubuntu/Debian)
# Mantido por Spiral Soluções e Consultoria LTDA
#
# Prepara o servidor para rodar o SpMail: verifica/instala PHP-FPM + extensões
# e MySQL/MariaDB, cria um banco de dados e um usuário de banco DEDICADO
# (nunca usa root na aplicação), gera o .env com as credenciais e a chave de
# criptografia, e ajusta permissões dos arquivos.
#
# Este script NÃO cria o schema nem o primeiro usuário do sistema - isso é
# feito pelo wizard web em /instalador/ (index.php), que é aberto ao final.
#
# Uso:
#   sudo ./install.sh
#
set -euo pipefail

# --------------------------------------------------------------------------
# Utilitários de saída
# --------------------------------------------------------------------------
COR_OK="\033[0;32m"
COR_ERRO="\033[0;31m"
COR_AVISO="\033[0;33m"
COR_RESET="\033[0m"

ok()    { echo -e "${COR_OK}[OK]${COR_RESET} $1"; }
erro()  { echo -e "${COR_ERRO}[ERRO]${COR_RESET} $1"; }
aviso() { echo -e "${COR_AVISO}[AVISO]${COR_RESET} $1"; }
passo() { echo -e "\n== $1 =="; }

falhar() {
	erro "$1"
	exit 1
}

confirmar() {
	# confirmar "pergunta" -> 0 (sim) ou 1 (não). Default = sim.
	local pergunta="$1"
	read -r -p "$pergunta [S/n] " resposta
	resposta="${resposta:-S}"
	[[ "$resposta" =~ ^[Ss]$ ]]
}

# --------------------------------------------------------------------------
# Checagens iniciais
# --------------------------------------------------------------------------
if [[ "${EUID}" -ne 0 ]]; then
	falhar "Rode este script como root (sudo ./install.sh)."
fi

if ! command -v apt-get >/dev/null 2>&1; then
	falhar "Este instalador foi feito para Ubuntu/Debian (apt-get não encontrado)."
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
APP_DIR="${SCRIPT_DIR}/projeto"
WEB_USER="${WEB_USER:-www-data}"

[[ -d "$APP_DIR" ]] || falhar "Pasta 'projeto' não encontrada em ${SCRIPT_DIR}. Rode este script a partir da raiz do repositório clonado."

echo "SpMail - Instalador de Servidor"
echo "Pasta da aplicação: ${APP_DIR}"

# --------------------------------------------------------------------------
# PHP + extensões
# --------------------------------------------------------------------------
passo "Verificando PHP e extensões"

PHP_EXTENSOES=(php-mysqli php-mbstring php-curl php-xml php-zip php-gd)

php_fpm_presente() {
	# Detecta qualquer php-fpm instalado (8.1, 8.2, 8.3...), independente da versão
	compgen -G "/etc/init.d/php*-fpm" >/dev/null 2>&1 || systemctl list-unit-files 2>/dev/null | grep -q '^php.*-fpm\.service'
}

if ! command -v php >/dev/null 2>&1 || ! php_fpm_presente; then
	if confirmar "PHP-FPM não encontrado. Instalar PHP 8.3 + FPM agora?"; then
		apt-get update -y
		apt-get install -y php8.3 php8.3-fpm "${PHP_EXTENSOES[@]}"
		ok "PHP 8.3 + FPM instalado."
	else
		aviso "Pulando instalação do PHP. O sistema não vai funcionar sem PHP-FPM + extensões."
	fi
else
	ok "PHP encontrado: $(php -v | head -n1)"
	FALTANTES=()
	for ext in mysqli mbstring curl xml zip gd; do
		php -m | grep -qi "^${ext}$" || FALTANTES+=("php-${ext}")
	done
	if [[ ${#FALTANTES[@]} -gt 0 ]]; then
		aviso "Extensões PHP faltando: ${FALTANTES[*]}"
		if confirmar "Instalar agora?"; then
			apt-get update -y
			apt-get install -y "${FALTANTES[@]}"
			ok "Extensões instaladas."
		fi
	else
		ok "Todas as extensões necessárias já estão presentes."
	fi
fi

# --------------------------------------------------------------------------
# curl (binário de linha de comando - diferente da extensão php-curl!)
# --------------------------------------------------------------------------
passo "Verificando curl"

# enviar.php dispara sozinho a continuação do envio em lote via
# `exec("curl ...")` chamando o binário de linha de comando - a extensão
# php-curl (usada por código PHP via curl_init()) não é o mesmo pacote e não
# cobre isso. Sem o binário, o envio para de continuar sozinho após o
# primeiro email, silenciosamente.
if ! command -v curl >/dev/null 2>&1; then
	if confirmar "Binário curl não encontrado (necessário para o envio em lote continuar sozinho). Instalar agora?"; then
		apt-get update -y
		apt-get install -y curl
		ok "curl instalado."
	else
		aviso "Sem o curl, cada campanha vai parar depois do primeiro email e vai exigir clicar em 'Continuar Envios' manualmente."
	fi
else
	ok "curl encontrado: $(curl --version | head -n1)"
fi

# exec() as vezes vem desabilitado em php.ini por padrao de hospedagem -
# sem ele, a mesma continuacao automatica tambem para de funcionar. O CLI e
# o PHP-FPM costumam ter php.ini SEPARADOS no Ubuntu, entao checa os dois.
if ! php -r 'exit(function_exists("exec") ? 0 : 1);' 2>/dev/null; then
	aviso "A função exec() está desabilitada no PHP CLI (disable_functions)."
fi
FPM_INI=$(find /etc/php -path "*/fpm/php.ini" 2>/dev/null | head -n1)
if [[ -n "$FPM_INI" ]] && grep -qi "^disable_functions\s*=.*\bexec\b" "$FPM_INI"; then
	aviso "exec está em disable_functions no php.ini do PHP-FPM (${FPM_INI}) - o envio em lote não vai conseguir continuar sozinho. Remova 'exec' dessa lista e reinicie o php-fpm."
else
	ok "exec() não parece estar bloqueado no PHP-FPM."
fi

# --------------------------------------------------------------------------
# MySQL / MariaDB
# --------------------------------------------------------------------------
passo "Verificando MySQL/MariaDB"

if ! command -v mysql >/dev/null 2>&1; then
	if confirmar "Cliente MySQL não encontrado. Instalar mysql-server agora?"; then
		apt-get update -y
		apt-get install -y mysql-server
		systemctl enable --now mysql
		ok "mysql-server instalado e iniciado."
	else
		falhar "MySQL/MariaDB é obrigatório para continuar."
	fi
else
	ok "Cliente MySQL encontrado: $(mysql --version)"
fi

# --------------------------------------------------------------------------
# Banco de dados + usuário dedicado (nunca root na aplicação)
# --------------------------------------------------------------------------
passo "Configurando banco de dados"

ENV_FILE="${APP_DIR}/.env"
REAPROVEITAR_ENV=false

if [[ -f "$ENV_FILE" ]]; then
	aviso ".env já existe em ${ENV_FILE} (essa VM já foi configurada antes)."
	if confirmar "Reaproveitar as credenciais de banco já salvas nesse .env? (recomendado - evita trocar a senha do usuário já em uso e quebrar a conexão)"; then
		DB_NAME="$(grep -m1 '^DB_NAME=' "$ENV_FILE" | cut -d'=' -f2-)"
		DB_USER="$(grep -m1 '^DB_USER=' "$ENV_FILE" | cut -d'=' -f2-)"
		DB_PASS="$(grep -m1 '^DB_PASS=' "$ENV_FILE" | cut -d'=' -f2-)"
		REAPROVEITAR_ENV=true
		ok "Reaproveitando banco '${DB_NAME}' e usuário '${DB_USER}' do .env existente."
	fi
fi

if [[ "$REAPROVEITAR_ENV" != "true" ]]; then
	read -r -p "Nome do banco de dados [spmail]: " DB_NAME
	DB_NAME="${DB_NAME:-spmail}"

	read -r -p "Usuário de banco DEDICADO para a aplicação [spmail_app]: " DB_USER
	DB_USER="${DB_USER:-spmail_app}"

	DB_PASS="$(openssl rand -base64 24 | tr -d '=+/')"
fi

# Serviço do MySQL precisa estar de pé antes de tentar conectar
if command -v systemctl >/dev/null 2>&1 && ! systemctl is-active --quiet mysql 2>/dev/null && ! systemctl is-active --quiet mariadb 2>/dev/null; then
	aviso "Serviço do MySQL/MariaDB não parece estar ativo. Tentando iniciar..."
	systemctl start mysql 2>/dev/null || systemctl start mariadb 2>/dev/null || true
fi

# Em instalação padrão do Ubuntu (recém instalada), root do MySQL usa
# auth_socket (só root do SO consegue conectar sem senha via `sudo mysql`).
# Mas se esse servidor já teve o root trocado para senha (caching_sha2_password
# ou outro), a conexão sem senha falha - nesse caso pedimos a senha de root
# do MySQL uma única vez, só para criar o banco e o usuário da aplicação;
# ela NÃO é salva em lugar nenhum.
MYSQL_ROOT_ARGS=()
set +e
ERRO_CONEXAO="$(mysql -e "SELECT 1;" 2>&1 >/dev/null)"
CODIGO_CONEXAO=$?
set -e
if [[ $CODIGO_CONEXAO -ne 0 ]]; then
	aviso "Conexão sem senha (auth_socket) falhou:"
	echo "  $ERRO_CONEXAO"
	if confirmar "Esse servidor exige senha de root do MySQL. Digitar a senha agora?"; then
		read -r -s -p "Senha de root do MySQL: " MYSQL_ROOT_PASS
		echo
		MYSQL_ROOT_ARGS=(-u root -p"${MYSQL_ROOT_PASS}")
		set +e
		ERRO_CONEXAO="$(mysql "${MYSQL_ROOT_ARGS[@]}" -e "SELECT 1;" 2>&1 >/dev/null)"
		CODIGO_CONEXAO=$?
		set -e
		# O aviso "Using a password on the command line..." sempre aparece no
		# stderr quando se usa -p"senha", mesmo com a senha certa - por isso
		# checamos o código de saída real, não se o stderr está vazio.
		if [[ $CODIGO_CONEXAO -ne 0 ]]; then
			echo "  $ERRO_CONEXAO"
			falhar "Ainda não foi possível conectar ao MySQL com a senha informada."
		fi
	else
		falhar "Não é possível continuar sem acesso de root ao MySQL."
	fi
fi

if [[ "$REAPROVEITAR_ENV" == "true" ]]; then
	# So garante que o banco/usuario existem e as permissoes estao certas -
	# NAO mexe na senha (CREATE USER IF NOT EXISTS nao faz nada se o usuario
	# ja existir, e nao ha ALTER USER aqui).
	mysql "${MYSQL_ROOT_ARGS[@]}" <<-SQL
		CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
		CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASS}';
		GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
		FLUSH PRIVILEGES;
	SQL
else
	mysql "${MYSQL_ROOT_ARGS[@]}" <<-SQL
		CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
		CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASS}';
		ALTER USER '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASS}';
		GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
		FLUSH PRIVILEGES;
	SQL
fi

ok "Banco '${DB_NAME}' e usuário dedicado '${DB_USER}'@'localhost' prontos (sem uso de root na aplicação)."

# --------------------------------------------------------------------------
# .env + chave de criptografia
# --------------------------------------------------------------------------
passo "Gerando .env"

if [[ "$REAPROVEITAR_ENV" == "true" ]]; then
	ok ".env mantido como estava (credenciais reaproveitadas)."
else
	APP_KEY="$(openssl rand -base64 32)"
	cat > "$ENV_FILE" <<-ENV
		DB_HOST=localhost
		DB_USER=${DB_USER}
		DB_PASS=${DB_PASS}
		DB_NAME=${DB_NAME}
		APP_KEY=${APP_KEY}
	ENV
	chmod 640 "$ENV_FILE"
	ok ".env criado em ${ENV_FILE}"
fi

# --------------------------------------------------------------------------
# Permissões
# --------------------------------------------------------------------------
passo "Ajustando permissões"

chown -R "${WEB_USER}:${WEB_USER}" "$APP_DIR"
find "$APP_DIR" -type d -exec chmod 755 {} \;
find "$APP_DIR" -type f -exec chmod 644 {} \;
[[ -f "$ENV_FILE" ]] && chmod 640 "$ENV_FILE" && chown "${WEB_USER}:${WEB_USER}" "$ENV_FILE"

ok "Permissões ajustadas para o usuário '${WEB_USER}'."

# --------------------------------------------------------------------------
# Finalização
# --------------------------------------------------------------------------
passo "Instalação de servidor concluída"

read -r -p "URL/IP configurado no Nginx para acessar o SpMail [http://localhost]: " URL_FINAL
URL_FINAL="${URL_FINAL:-http://localhost}"

echo
ok "Servidor preparado com sucesso."
echo "Próximo passo: abra ${URL_FINAL}/instalador/ no navegador para concluir a"
echo "instalação (criação das tabelas e do primeiro usuário administrador)."
echo
echo "Resumo:"
echo "  Banco de dados: ${DB_NAME}"
echo "  Usuário do banco (app): ${DB_USER}"
echo "  Arquivo .env: ${ENV_FILE}"
