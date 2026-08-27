<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
include("conexao.php");
include_once("db.php");

// Valor do campo `setores` que concede acesso de Administrador Geral
// (tela de Configurações do Sistema e gestão de usuários).
define('PAPEL_ADMIN_GERAL', 'Administrador Geral');

$_SG['paginaLogin'] = 'index.php'; // Página de login
$_SG['tabela'] = 'usuarios';       // Nome da tabela onde os usuários são salvos
$_SG['caseSensitive'] = false;  // Usar case-sensitive? Onde 'thiago' é diferente de 'THIAGO'
$_SG['validaSempre'] = true;	// Deseja validar o usuário e a senha a cada carregamento de página?
$_SG['abreSessao'] = true;      // Inicia a sessão com um session_start()?
// ==============================

// ======================================
//   ~ Não edite a partir deste ponto ~
// ======================================


// Verifica se precisa iniciar a sessão
if ($_SG['abreSessao'] == true && session_status() !== PHP_SESSION_ACTIVE) {
	session_start();
}

/**
* Função que valida um usuário e senha
*
* Aceita tanto hash moderno (password_hash) quanto o hash MD5 usado em
* instalações antigas - nesse segundo caso, ao validar com sucesso, o hash
* é atualizado automaticamente para o formato seguro, sem exigir troca de
* senha por parte do usuário.
*
* @param string $usuario - O usuário a ser validado
* @param string $senha - A senha a ser validada
*
* @return bool - Se o usuário foi validado ou não (true/false)
*/
function validaUsuario($usuario, $senha) {
	global $_SG, $con;

	$sql = "SELECT id, email, senha, setores, ativo FROM " . $_SG['tabela'] . " WHERE email = ? LIMIT 1";
	$rs = dbQuery($con, $sql, "s", $usuario);
	$resultado = $rs ? mysqli_fetch_assoc($rs) : null;

	if (empty($resultado)) {
		return false;
	}

	if ((int)$resultado['ativo'] === 0) {
		// Usuário desativado pelo Administrador Geral
		return false;
	}

	$hashArmazenado = $resultado['senha'];
	$autenticado = false;

	if(preg_match('/^[a-f0-9]{32}$/i', $hashArmazenado)){
		// Hash legado em MD5 (instalações anteriores a esta versão)
		if(md5($senha) === $hashArmazenado){
			$autenticado = true;
			// Upgrade silencioso para hash seguro, sem exigir ação do usuário
			$novoHash = password_hash($senha, PASSWORD_DEFAULT);
			dbQuery($con, "UPDATE usuarios SET senha = ? WHERE id = ?", "si", $novoHash, $resultado['id']);
		}
	}else{
		$autenticado = password_verify($senha, $hashArmazenado);
	}

	if(!$autenticado){
		return false;
	}

	$_SESSION['usuarioID'] = $resultado['id'];
	$_SESSION['usuarioNome'] = $resultado['email'];
	$_SESSION['usuarioPapel'] = $resultado['setores'];

	if ($_SG['validaSempre'] == true) {
		$_SESSION['usuarioLogin'] = $usuario;
		$_SESSION['usuarioSenha'] = $senha;
	}

	return true;
}

/**
* Função para verificar se o usuário logado é Administrador Geral
*/
function usuarioEhAdminGeral(){
	return isset($_SESSION['usuarioPapel']) && $_SESSION['usuarioPapel'] === PAPEL_ADMIN_GERAL;
}

/**
* Função que protege uma página
*/
function protegePagina() {
	global $_SG;

	if (!isset($_SESSION['usuarioID']) OR !isset($_SESSION['usuarioNome'])) {
		// Não há usuário logado, manda pra página de login
		expulsaVisitante();
	} else if (isset($_SESSION['usuarioID'])) {
		// Há usuário logado, verifica se precisa validar o login novamente
		if ($_SG['validaSempre'] == true) {
			// Verifica se os dados salvos na sessão batem com os dados do banco de dados
			if (!validaUsuario($_SESSION['usuarioLogin'], $_SESSION['usuarioSenha'])) {
				// Os dados não batem, manda pra tela de login
				expulsaVisitante();
				}
		}
	}
}

/**
* Protege uma página exigindo, além do login, o papel de Administrador Geral.
* Usar no lugar de protegePagina() em telas restritas (Configurações, Usuários).
*/
function protegePaginaAdmin(){
	protegePagina();

	if(!usuarioEhAdminGeral()){
		http_response_code(403);
		die('<div class="alert wrap">Acesso restrito ao Administrador Geral. Fale com um administrador do sistema se você acredita que deveria ter acesso a esta tela.</div>');
	}
}

/**
* Função para expulsar um visitante
*/
function expulsaVisitante() {
	global $_SG;

	// Remove as variáveis da sessão (caso elas existam)
	unset($_SESSION['usuarioID'], $_SESSION['usuarioNome'], $_SESSION['usuarioLogin'], $_SESSION['usuarioSenha'], $_SESSION['usuarioPapel']);

	// Manda pra tela de login
	header("Location: ".$_SG['paginaLogin']);
	exit;
}

/**
* Criptografa a senha de envio de email (SMTP) para guardar no banco.
* Usa AES-256-GCM com a chave APP_KEY (definida no .env). Se APP_KEY não
* estiver configurada, devolve o valor em texto plano (comportamento antigo)
* e registra um aviso no log do servidor - não quebra instalações que ainda
* não migraram para o .env.
*
* @param string $senhaPlana
* @return string Valor pronto para gravar na coluna senha_email
*/
function criptografarSenhaEmail($senhaPlana){
	$chave = obterChaveApp();
	if($chave === null){
		trigger_error('APP_KEY não configurada - senha de envio será salva sem criptografia. Configure APP_KEY no .env.', E_USER_WARNING);
		return $senhaPlana;
	}

	if($senhaPlana === ''){
		return '';
	}

	$iv = random_bytes(12);
	$tag = '';
	$cifrado = openssl_encrypt($senhaPlana, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

	return base64_encode($iv . $tag . $cifrado);
}

/**
* Descriptografa a senha de envio de email. Se o valor armazenado não for
* um pacote cifrado válido (ex: registro antigo em texto plano, gravado
* antes desta versão), devolve o valor como está - migração silenciosa,
* igual à de senha de login.
*
* @param string $valorArmazenado
* @return string Senha em texto plano, pronta para uso no PHPMailer
*/
function descriptografarSenhaEmail($valorArmazenado){
	if($valorArmazenado === ''){
		return '';
	}

	$chave = obterChaveApp();
	if($chave === null){
		return $valorArmazenado;
	}

	$binario = base64_decode($valorArmazenado, true);
	// iv (12) + tag (16) + pelo menos 1 byte de conteúdo cifrado
	if($binario === false || strlen($binario) <= 28){
		return $valorArmazenado; // Não parece um pacote cifrado - trata como legado em texto plano
	}

	$iv = substr($binario, 0, 12);
	$tag = substr($binario, 12, 16);
	$cifrado = substr($binario, 28);

	$decifrado = openssl_decrypt($cifrado, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

	// Falha de autenticação (chave errada ou não era realmente um pacote cifrado) -> assume texto plano legado
	return $decifrado === false ? $valorArmazenado : $decifrado;
}

/**
* Lê e decodifica a APP_KEY do ambiente (.env). Retorna null se ausente.
*/
function obterChaveApp(){
	$chaveBase64 = getenv('APP_KEY');
	if($chaveBase64 === false || $chaveBase64 === ''){
		return null;
	}

	$chave = base64_decode($chaveBase64, true);
	return ($chave === false || strlen($chave) < 32) ? null : $chave;
}
?>
