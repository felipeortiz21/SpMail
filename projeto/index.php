<?php

	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	session_start();

	include("libs/config.php");
	include("libs/seguranca.php");

	$msg = "";

if(isset($_REQUEST["nome"]) && isset($_REQUEST["senha"])){

	$nome = $_REQUEST["nome"];
	$senha = $_REQUEST["senha"];

	//Verificar Usuário
	$validado = validaUsuario($nome, $senha);

	if(!$validado):
		$msg = '<div class="alert">Nome de Usuário ou Senha Inválido</div>';
	else:
		header("location:dashboard.php");
		exit;
	endif;

}

?>

<!DOCTYPE html>
<html lang="pt_br">
	<head>
		<meta charset="utf-8"/>
		<title>SpMail - Login</title>
		<link rel="stylesheet" href="css/estilo.css">
	</head>
	<body>
		<div class="login">
			<center>
				<img src="<?php echo $caminhoURL; ?>assets/logo_maior.png"/>
			</center>
			<h1>Entre Para Enviar Emails</h1>
			<form action="#" method="post">
				<input type="text" name="nome" placeholder="Nome de Usuário / email"/>
				<input type="password" name="senha" placeholder="Senha"/>
				<button type="submit">Entrar</button>
			</form>
			<?php echo $msg; ?>
		</div>

		<div class="powered" style="position: fixed;right: 5px;bottom: 5px;text-align: right;color:lightgrey; font-size:16px;">
			Powered by Spiral Soluções e Consultoria
		</div>
	</body>
</html>
