<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
ini_set('display_errors', 0);

if(is_file(__DIR__ . "/.instalado")){
	die("<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>Este SpMail já foi instalado. Por segurança, o instalador não roda de novo.</div>");
}

include_once("../libs/seguranca.php");
include_once("../libs/db.php");

$nome = "";
$email = "";
$senha = "";
$confSenha = "";
$senhaEmail = "";

if(isset($_REQUEST["nome"])){
	$nome = trim($_REQUEST["nome"]);
	$email = trim($_REQUEST["email"]);
	$senha = trim($_REQUEST["senha"]);
	$confSenha = trim($_REQUEST["confSenha"]);
	$senhaEmail = trim($_REQUEST["senha_email"]);

	if($senha == $confSenha){
		dbQuery($con, "TRUNCATE usuarios;");

		$hashSenha = password_hash($senha, PASSWORD_DEFAULT);
		$senhaEmailCifrada = criptografarSenhaEmail($senhaEmail);

		// O primeiro usuário do sistema já nasce como Administrador Geral
		$rsSql = dbQuery(
			$con,
			"INSERT INTO usuarios VALUES (DEFAULT,?,?,?,?,?,1)",
			"sssss",
			$nome, $email, PAPEL_ADMIN_GERAL, $hashSenha, $senhaEmailCifrada
		);

		if($rsSql){
			// Marca o instalador como concluído (trava de reentrada)
			file_put_contents(__DIR__ . "/.instalado", date('c'));
			echo('<META http-equiv="refresh" content="1;URL=excluir.php">');
			exit;
		}else{
			echo "<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>Não foi possível atualizar o Banco de Dados. Por favor, verifique os dados que foram passados.</div>";
		}
	}else{
		echo "<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>A senha não bateu com a confirmação, por favor digite novamente.</div>";
	}
}
?><!DOCTYPE html>
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
			<h1>Está Quase Pronto :D</h1>
			<h2>Cadastre o primeiro usuário administrador!</h2>
			<p>Para finalizar a instalação, você deverá cadastrar o primeiro endereço de email por onde você enviará as mensagens. Este primeiro usuário é criado como Administrador Geral, com acesso à tela de Configurações do Sistema. Você poderá cadastrar outros usuários no sistema depois.</p>
			<form action="finalizar.php" method="post">
				<!-- fake fields are a workaround for chrome autofill getting the wrong fields -->
				<input style="display:none" type="text" name="fakeusernameremembered"/>
				<input style="display:none" type="password" name="fakepasswordremembered"/>
				<div>
					<p class="mini-info">Nome do Usuário</p>
					<input type="text" name="nome" placeholder="ex: João da Silva" autocomplete="off" value="<?php echo htmlspecialchars($nome); ?>" required/>
				</div>
				<div>
					<p class="mini-info">Endereço de e-mail. Você deverá usar este email como login, no SpMail. Este endereço também poderá ser usado enviar as mensagens. Por segurança, considere usar uma conta de APENAS para esse fim.</p>
					<input type="email" name="email" placeholder="ex: mailing@meudominio.com.br" autocomplete="off"  value="<?php echo htmlspecialchars($email); ?>" required/>
				</div>
				<div>
					<p class="mini-info">Crie uma senha para acessar o SpMail. Esta é a senha que você usará no momento que fizer o login.</p>
					<input type="password" name="senha" placeholder="Senha" autocomplete="off" required/>
					<input type="password" name="confSenha" placeholder="Confirme a Senha" autocomplete="off" required/>
				</div>
				<div>
					<p class="mini-info">Digite a senha que é usada para acessar o email. Esta senha é criptografada antes de ser salva no banco de dados, mas ainda assim é recomendado que este email seja usado APENAS para envio de mailmarketing.</p>
					<input type="password" name="senha_email" placeholder="Senha para envio das mensagens" autocomplete="off"   value="<?php echo htmlspecialchars($senhaEmail); ?>"/>
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
