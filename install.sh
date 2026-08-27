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

read -r -p "Nome do banco de dados [spmail]: " DB_NAME
DB_NAME="${DB_NAME:-spmail}"

read -r -p "Usuário de banco DEDICADO para a aplicação [spmail_app]: " DB_USER
DB_USER="${DB_USER:-spmail_app}"

DB_PASS="$(openssl rand -base64 24 | tr -d '=+/')"

# Em instalação padrão do Ubuntu, root do MySQL usa auth_socket (só root do SO
# consegue conectar sem senha via `sudo mysql`). É o que usamos aqui - a senha
# de root do MySQL nunca é pedida nem armazenada.
if ! mysql -e "SELECT 1;" >/dev/null 2>&1; then
	falhar "Não foi possível conectar ao MySQL como root do sistema (sudo mysql). Configure o acesso root do MySQL manualmente e rode este script novamente."
fi

mysql <<-SQL
	CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
	CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASS}';
	ALTER USER '${DB_USER}'@'localhost' IDENTIFIED WITH caching_sha2_password BY '${DB_PASS}';
	GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
	FLUSH PRIVILEGES;
SQL

ok "Banco '${DB_NAME}' e usuário dedicado '${DB_USER}'@'localhost' prontos (sem uso de root na aplicação)."

# --------------------------------------------------------------------------
# .env + chave de criptografia
# --------------------------------------------------------------------------
passo "Gerando .env"

ENV_FILE="${APP_DIR}/.env"
APP_KEY="$(openssl rand -base64 32)"

if [[ -f "$ENV_FILE" ]]; then
	aviso ".env já existe em ${ENV_FILE} - não será sobrescrito."
	aviso "Se quiser gerar um novo, apague o arquivo antigo e rode este script de novo."
else
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
