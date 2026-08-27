<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	if(session_status() !== PHP_SESSION_ACTIVE){
		session_start();
	}

	include_once("libs/config.php");
	// $permitirSemSessao é usado por processos internos do próprio servidor
	// (ex: a continuação automática de envio em enviar.php), que não têm
	// sessão de navegador mas já se autenticaram por outro meio (token).
	if(!isset($_SESSION["usuarioNome"]) && empty($GLOBALS['permitirSemSessao'])){
		header("location:".$caminhoURL."index.php");
		exit;
	}
	$ehAdminGeral = isset($_SESSION['usuarioPapel']) && $_SESSION['usuarioPapel'] === 'Administrador Geral';
	$logoTopo = !empty($logoPath) ? $caminhoURL.$logoPath : $caminhoURL."assets/simbolo.png";
	?>
<!DOCTYPE html>
<html lang="pt_br" data-theme="light">
	<head>
	    <meta charset="utf-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <link rel="preconnect" href="https://fonts.googleapis.com">
	    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="<?php echo $caminhoURL; ?>css/pico.classless.min.css">
		<link rel="stylesheet" href="<?php echo $caminhoURL; ?>css/estilo.css">
        <link rel="stylesheet" href="https://tinymce.cachefly.net/4.2/skins/lightgray/skin.min.css">
		<script src="<?php echo $caminhoURL; ?>js/advanced.js"></script>
		<script type="text/javascript" src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
		<script src="<?php echo $caminhoURL; ?>js/tinymce/tinymce.min.js"></script>
        <script src="<?php echo $caminhoURL; ?>js/jquery.colorbox-min.js"></script>

        <title>SpMail - <?php echo htmlspecialchars($nomeEmpresa); ?></title>
        <meta name="description" content="Gerenciador de Mailmarketing">
        <meta name="robots" content="no-index" />
        <link rel="icon" type="image/png" href="assets/simbolo.png" />
	</head>
	<body>
		<header class="cabecalho">
			<div class="marca">
				<img src="<?php echo htmlspecialchars($logoTopo); ?>" alt="<?php echo htmlspecialchars($nomeEmpresa); ?>"/>
				<h1><?php echo htmlspecialchars($nomeEmpresa); ?></h1>
			</div>
			<div class="menu">
			<nav>
				<ul>
					<li><a href="<?php echo $caminhoURL; ?>dashboard.php">Dashboard</a></li>
					<li><a href="<?php echo $caminhoURL; ?>email.php">Novo Email</a></li>
					<li><a href="<?php echo $caminhoURL; ?>emails.php">Emails Enviados</a></li>
					<li><a href="<?php echo $caminhoURL; ?>grupos.php">Grupos</a></li>
					<li><a href="<?php echo $caminhoURL; ?>clientes.php">Contatos</a></li>
					<?php if($ehAdminGeral): ?>
					<li><a href="<?php echo $caminhoURL; ?>usuarios.php">Usuários</a></li>
					<li><a href="<?php echo $caminhoURL; ?>configuracoes.php">Configurações</a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</div>
		</header>
		<p class="green"><?php echo $enviados ?? ''; ?></p>

