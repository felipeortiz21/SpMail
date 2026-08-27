<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
if(isset($_REQUEST["excluir"])){
	function delTree($dir) {
		$files = array_diff(scandir($dir), array('.','..'));
		foreach ($files as $file) {
		  (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}

	delTree("../instalador");
	echo('<META http-equiv="refresh" content="1;URL=../">');
	exit;
}
?>
<!DOCTYPE html>
<html lang="pt_br" data-theme="light">
	<head>
		<meta charset="utf-8"/>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
		<title>Instalador SpMail</title>
		<link rel="stylesheet" href="../css/pico.classless.min.css">
		<link rel="stylesheet" href="../css/estilo.css">
	</head>
	<body>
		<div class="login" style="width:80%; max-width:700px; margin-top:10px;">
			<center>
				<img style="max-width:250px;" src="../assets/logo_maior.svg"/>
			</center>
			<h1>Está Tudo Completo! Obrigado por instalar o SpMail.</h1>
			<p>Para sua segurança, a pasta do instalador será automaticamente excluída e você será encaminhado para a tela de login.</p>
			<form action="excluir.php">
				<center>
					<input type="hidden" name="excluir" value="true"/>
					<button type="submit">Finalizar Instalador e Entrar no SpMail</button>
				</center>
			</form>
		</div>

		<div class="powered" style="position: fixed;right: 5px;bottom: 5px;text-align: right;color:lightgrey; font-size:16px;">
			Powered by Spiral Soluções e Consultoria
		</div>
	</body>
</html>
