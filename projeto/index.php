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

$logoLogin = !empty($logoPath) ? $caminhoURL.$logoPath : $caminhoURL."assets/logo_maior.svg";

?>

<!DOCTYPE html>
<html lang="pt_br" data-theme="light">
	<head>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
		<title>SpMail - Login</title>
		<link rel="stylesheet" href="css/pico.classless.min.css">
		<link rel="stylesheet" href="css/estilo.css">
		<link rel="icon" type="image/svg+xml" href="assets/simbolo.svg" />
	</head>
	<body>
		<div class="login">
			<center>
				<img src="<?php echo htmlspecialchars($logoLogin); ?>" alt="<?php echo htmlspecialchars($nomeEmpresa); ?>"/>
			</center>
			<h1>Entre para enviar emails</h1>
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
