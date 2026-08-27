<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com

		AVISO: este endpoint parece ser uma integração específica de um
		cliente do projeto original (opt-in fixo no grupo 4), não faz parte
		do fluxo padrão do SpMail e não está referenciado em nenhum menu.
		Mantido apenas com a correção de segurança - avalie se ainda é
		necessário ou se pode ser removido.
	******************************/
header("Content-Type: text/plain");
include_once("libs/config.php");
include_once("libs/db.php");

if(empty($_REQUEST["email"])){
	die("{\"erro\": \"Preencha um email para continuar.\"}");
}

$email = trim($_REQUEST['email']);
$nome = trim($_REQUEST['nome'] ?? '');

$strRes = dbQuery($con, "SELECT email, aut FROM contatos WHERE email=? AND grupo='4' LIMIT 1", "s", $email);

if(mysqli_num_rows($strRes) == 0){
	dbQuery($con, "INSERT INTO contatos VALUES(DEFAULT,?,?,'','4','1')", "ss", $email, $nome);
	$msg = "Seu e-mail ".$email." foi inserido com sucesso.";
}else{
	dbQuery($con, "UPDATE contatos SET aut='1' WHERE email = ?", "s", $email);
	$msg = "Contato ".$email." atualizado!";
}

echo "{";
echo "\"mensagem\" : \"". addslashes($msg) ."\"";
echo "}";
?>
