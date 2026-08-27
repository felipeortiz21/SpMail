<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	if(is_file(__DIR__ . "/.instalado")){
		die("<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>Este SpMail já foi instalado. Por segurança, o instalador não roda de novo. Se você realmente precisa reinstalar, apague o arquivo <code>instalador/.instalado</code> manualmente no servidor.</div>");
	}
?>
<!DOCTYPE html>
<html lang="pt_br">
	<head>
	    <meta charset="utf-8">
		<link rel="stylesheet" href="../css/estilo.css">
        <title>Instalador SpMail</title>
        <meta name="description" content="Gerenciador de Mailmarketing">
        <meta name="robots" content="no-index" />
        <link rel="icon" type="image/png" href="../assets/simbolo.png" />
	</head>
	<body>
		<div class="login instalador">
			<center>
				<img src="../assets/logo_maior.png"/>
			</center>
			<h1>Bem Vindo ao Instalador do SpMail</h1>
			<h2>Para prosseguir, preencha os dados do Banco.</h2>
			<form action="criar_config.php" method="post">
				<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
				<input style="display:none" type="text" name="fakeusernameremembered"/>
				<input style="display:none" type="password" name="fakepasswordremembered"/>

				<div>
					<p class="mini-info">Na maioria das vezes, o endereço é, simplesmente, <em>localhost</em>. Em caso de dúvida, consulte a hospedagem</p>
					<input type="text" name="host" placeholder="Endereço do Banco" autocomplete="off" required/>
				</div>
				<div>
					<p class="mini-info">Preencha com o nome do Banco de Dados criado para este </p>
					<input type="text" name="dbname" placeholder="Nome do Banco" autocomplete="off" required/>
				</div>
				<div>
					<p class="mini-info">Login do Usuário com acesso ao Banco de Dados</p>
					<input type="text" name="user" placeholder="Usuário do Banco" autocomplete="off" required/>
				</div>
				<div>
					<p class="mini-info">Senha do Usuário com acesso ao Banco de Dados</p>
					<input type="password" name="pswd" placeholder="Senha do Usuário" autocomplete="off" required/>
				</div>

				<button type="submit">Próximo Passo</button>
			</form>
			<div class="info">
				Requisitos: PHP 8.1 ou superior e um banco MySQL 8 / MariaDB.<br/>
				Caso você tenha dificuldades, consulte sua hospedagem para saber como criar um novo banco de dados, usuário e senha.
			</div>
		</div>

		<div class="powered" style="position: fixed;right: 5px;bottom: 5px;text-align: right;color:lightgrey; font-size:16px;">
			Powered by Spiral Soluções e Consultoria
		</div>
	</body>
</html>
