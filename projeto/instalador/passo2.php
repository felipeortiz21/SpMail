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

include_once("../libs/config.php");
include_once("../libs/db.php");

if(isset($_REQUEST["url"])){
	$cUrl = trim($_REQUEST['url']);
	$cPasta = trim($_REQUEST['pasta']);
	$cNomeEmpresa = trim($_REQUEST['nome_empresa']);
	$cSmtp = trim($_REQUEST['smtp']);
	$cPorta = trim($_REQUEST['porta']);
	$cSeguranca = trim($_REQUEST['seguranca']);
	$cAutenticacao = (int) $_REQUEST['autenticacao'];
	$cEmailResposta = trim($_REQUEST['email_resposta']);
	$cNomeEmailResposta = trim($_REQUEST['nome_email_resposta']);
	$cEmailsPorHora = (int) $_REQUEST['emails_por_hora'];
	$cEmailsPorHoraNaoComercial = (int) $_REQUEST['emails_por_hora_nao_comercial'];
	$cHorarioComercialIni = (int) $_REQUEST['horario_comercial_ini'];
	$cHorarioComercialFin = (int) $_REQUEST['horario_comercial_fin'];
	$cAtrasoMinimo = (int) ($_REQUEST['envio_atraso_minimo_segundos'] ?? 2);
	$cAtrasoMaximo = (int) ($_REQUEST['envio_atraso_maximo_segundos'] ?? 5);

	dbQuery($con, "TRUNCATE config;");

	// DKIM começa desativado - é totalmente opcional e pode ser configurado
	// depois em Configurações, quando/se o domínio tiver uma chave DKIM pronta.
	$rsSql = dbQuery(
		$con,
		"INSERT INTO config VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,'','','','')",
		"ssssssissiiiiii",
		$cUrl, $cPasta, $cNomeEmpresa, $cSmtp, $cPorta, $cSeguranca, $cAutenticacao,
		$cEmailResposta, $cNomeEmailResposta, $cEmailsPorHora, $cEmailsPorHoraNaoComercial,
		$cHorarioComercialIni, $cHorarioComercialFin, $cAtrasoMinimo, $cAtrasoMaximo
	);

	if($rsSql){
		echo('<META http-equiv="refresh" content="1;URL=finalizar.php">');
		exit;
	}else{
		echo "<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>Não foi possível atualizar o Banco de Dados. Por favor, verifique os dados que foram passados. Também certifique-se que o usuário que você digitou tem permissões para modificar este banco de dados.</div>";
	}
}
?>
<!DOCTYPE html>
<html lang="pt_br" data-theme="light">
	<head>
	    <meta charset="utf-8">
	    <meta name="viewport" content="width=device-width, initial-scale=1">
	    <link rel="preconnect" href="https://fonts.googleapis.com">
	    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
		<link rel="stylesheet" href="../css/pico.classless.min.css">
		<link rel="stylesheet" href="../css/estilo.css">
        <title>Instalador SpMail</title>
        <meta name="description" content="Gerenciador de Mailmarketing">
        <meta name="robots" content="no-index" />
        <link rel="icon" type="image/svg+xml" href="../assets/simbolo.svg" />
	</head>
	<body>
		<div class="login instalador">
			<center>
				<img src="../assets/logo_maior.svg"/>
			</center>
			<h1>Banco Criado com Sucesso!</h1>
			<h2>Agora, por favor, preencha as informações básicas para a operação do SpMail.</h2>
			<form action="passo2.php" method="post">
				<div>
					<p class="mini-info">Preencha com a URL correta do site (sem barra no final) - inclusive o esquema certo, http:// ou https://. Esse caminho é importante para definir onde os links, contadores e imagens irão referenciar. Se estiver testando localmente sem certificado (ex: http://localhost:3002), use http://, não https://.</p>
					<?php
						$esquemaAtual = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
					?>
					<input type="text" name="url" placeholder="https://meusite.com.br" value="<?php echo $esquemaAtual . $_SERVER['HTTP_HOST']; ?>" autocomplete="off" required/>
				</div>
				<div>
					<p class="mini-info">Se o SpMail estiver instalado direto na raiz do domínio (sem subpasta - o mais comum), deixe este campo em branco. Preencha só se ele estiver dentro de uma subpasta, ex: "mailing" em https://meusite.com.br/mailing/</p>
					<?php
						$uri = $_SERVER['REQUEST_URI'];
						$uri = str_replace("/instalador/passo2.php","",$uri);
						$uri = ltrim($uri, '/');
					?>
					<input type="text" name="pasta" placeholder="(deixe em branco se não usar subpasta)" autocomplete="off" value="<?php echo htmlspecialchars($uri); ?>"/>
				</div>
				<div>
					<p class="mini-info">Digite o nome da Empresa ou Instituição que usará o SpMail</p>
					<input type="text" name="nome_empresa" placeholder="Minha Empresa" autocomplete="off" required/>
				</div>
				<h4>Dados de Emails</h4>
				<p>Todos os emails que serão cadastrados usarão os mesmos dados de acesso. Essa decisão visa diminuir a incidência de uso do sistema para spammers. Consulte sua hospedagem ou servidor de emails para verificar esses dados. Você pode deixar para preencher esses itens depois, mas é altamente recomendável que faça isso agora.</p>
				<div>
					<p class="mini-info">Digite o endereço do SMTP dos emails que serão usados para envio.</p>
					<input type="text" name="smtp" placeholder="smtp.servidor.com" autocomplete="off"/>
				</div>
				<div>
					<p class="mini-info">Digite a porta do SMTP dos emails</p>
					<input type="text" name="porta" placeholder="465" autocomplete="off"/>
				</div>
				<div>
					<p class="mini-info">Escolha o tipo de segurança. É altamente recomendado usar um tipo de segurança.</p>
					<select name="seguranca" id="seguranca" placeholder="Tipo de Segurança">
						<option value="ssl" default>SSL</option>
						<option value="tls">TLS</option>
						<option value="">Nenhuma</option>
					</select>
				</div>
				<div>
					<p class="mini-info">Servidor requer autenticação?</p>
					<select name="autenticacao" id="autenticacao" placeholder="Tipo de Autenticação">
						<option value="1" default>Requer Autenticação</option>
						<option value="0">Não Requer Autenticação</option>
					</select>
				</div>
				<div>
					<p class="mini-info">Quando as pessoas responderem seus emails, elas responderão para qual email?</p>
					<input type="email" name="email_resposta" placeholder="emailresposta@meudominio.com" autocomplete="off"/>
				</div>
				<div>
					<p class="mini-info">Nome que irá aparecer para as pessoas no email resposta.</p>
					<input type="text" name="nome_email_resposta" placeholder="Contato da Empresa" autocomplete="off"/>
				</div>
				<div>
					<p class="mini-info">Quantidade de emails que serão enviados por hora. Verifique com seu servidor de emails quantos emails você pode enviar por hora. A maioria varia entre 300 e 500 emails. Recomenda-se utilizar de metade a dois terços dos emails permitidos por hora para evitar que seu servidor fique inoperante temporariamente.</p>
					<input type="number" name="emails_por_hora" placeholder="Apenas Números" autocomplete="off" value="200" min="0" />
				</div>
				<div>
					<p class="mini-info">Quantidade de emails que serão enviados por hora FORA DO HORÁRIO COMERCIAL. <b>Mantenha vazio caso não queira utilizar</b> Para agilizar o envio dos emails, mantenha o número de emails permitidos por hora maior em relação a este. Este email visa uma segurança para que pessoas que usam o mesmo servidor de emails para o trabalho, possam utilizar sem correr riscos de cair. Recomenda-se usar um terço da quantidade total permitida.</p>
					<input type="number" name="emails_por_hora_nao_comercial" placeholder="Apenas Números" autocomplete="off" value="300" min="0" />
				</div>
				<div>
					<p class="mini-info">Digite a hora que se inicia o horário comercial na sua empresa ou instituição. Considere APENAS a hora, em formato de 0 a 23 horas.</p>
					<input type="number" name="horario_comercial_ini" placeholder="Apenas Números" autocomplete="off" value="8" min="0" max="23"/>
				</div>
				<div>
					<p class="mini-info">Digite a hora que é finalizado o horário comercial na sua empresa ou instituição. Considere APENAS a hora, em formato de 0 a 23 horas.</p>
					<input type="number" name="horario_comercial_fin" placeholder="Apenas Números" autocomplete="off" value="18" min="0" max="23"/>
				</div>
				<div>
					<p class="mini-info">Atraso aleatório (em segundos) entre um email e outro, pra não ter um padrão perfeitamente constante (evita parecer comportamento de bot pra provedores como Gmail/Outlook). Pode ajustar depois em Configurações.</p>
					<input type="number" name="envio_atraso_minimo_segundos" placeholder="Atraso Mínimo (segundos)" autocomplete="off" value="2" min="1"/>
					<input type="number" name="envio_atraso_maximo_segundos" placeholder="Atraso Máximo (segundos)" autocomplete="off" value="5" min="1"/>
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
