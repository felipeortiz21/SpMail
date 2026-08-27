<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	include_once("libs/seguranca.php");
	include_once("libs/db.php");
	include_once("libs/template.php");
	protegePagina(); // Envio de teste exige usuário autenticado

	include "functions.php";
	ini_set('error_reporting', E_ALL);     //Reporta todos os erros.

	$id = $_REQUEST["id"];
?>
<?php if(isset($_REQUEST['email'])): ?>
<?php
	$rs = dbQuery(
		$con,
		"SELECT men.id as id, men.assunto as assunto, men.mensagem as mensagem, men.url as url,
			(SELECT email FROM usuarios WHERE id=men.email_envio) as email_envio,
			(SELECT nome FROM usuarios WHERE id=men.email_envio) as nome_envio,
			(SELECT nome FROM usuarios WHERE id=men.email_envio) as nome,
			(SELECT senha_email FROM usuarios WHERE id=men.email_envio) as senha_email,
			grupos, emails_adicionais
		FROM mensagens men WHERE id=?",
		"i",
		$id
	);
	while($row = mysqli_fetch_array($rs)){
		$id = $row["id"];
		$assunto = $row["assunto"];
		$envio = $row["email_envio"];
		$nome_envio = $row["nome_envio"];
		$nome = $row["nome"];
		$mensagem = $row["mensagem"];
		$url = $row["url"];
		$senha_email = descriptografarSenhaEmail($row["senha_email"]);
	}

	$destinatario = $_REQUEST['email'];

	// Busca um contato real com esse email para o preview de {nome}/{telefone};
	// se o endereço de teste não for um contato cadastrado, os tokens ficam vazios.
	$rsContato = dbQuery($con, "SELECT nome, telefone FROM contatos WHERE email = ? LIMIT 1", "s", $destinatario);
	$contatoTeste = $rsContato ? (mysqli_fetch_assoc($rsContato) ?: []) : [];
	$contatoTeste['email'] = $destinatario;

	$assuntoPersonalizado = substituirVariaveis($assunto, $contatoTeste);
	$mensagemPersonalizada = substituirVariaveis($mensagem, $contatoTeste);

	$url = $caminhoURL."/emails/".$url.".html";
	$urlCancelamento = $caminhoURL."/cancelamento.php?email=".urlencode($destinatario);
    //Montar Mensagem
	$emailCompleto = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		<title>'.htmlspecialchars($assuntoPersonalizado).'</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		</head>
		<body>';
	$emailCompleto .= $mensagemPersonalizada;
	$emailCompleto .= "<img src=\"".$caminhoURL."/contador.php?email=".urlencode($destinatario)."&mensagem=".$id."\" height=\"90\" style=\"height: 90px; width: auto; text-align: center; border: none;\" alt=\"contador\" />";
	// Removido a pedido: link de "visualizar no navegador" e "Cancelar
	// Inscrição" - o email enviado passa a ter só o conteúdo criado na campanha.
	$emailCompleto .= '</body></html>';

	$urlAtivaSimples = "href='".$caminhoURL."link.php?email=".urlencode($destinatario)."&mensagem=$id&link=";
	$urlAtivaDupla = 'href="'.$caminhoURL."link.php?email=".urlencode($destinatario)."&mensagem=$id&link=";
	$emailCompleto = str_replace("href='http://",$urlAtivaSimples, $emailCompleto);
	$emailCompleto = str_replace('href="http://',$urlAtivaDupla, $emailCompleto);
	$emailCompleto = str_replace("href='https://",$urlAtivaSimples, $emailCompleto);
	$emailCompleto = str_replace('href="https://',$urlAtivaDupla, $emailCompleto);

	// Cabeçalhos
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	$headers .= 'From: '.$nome.' <'.$envio.'> '."\r\n".
	'Reply-To: '.$envio."\r\n" .
	'X-Mailer: PHP/' . phpversion();

	$errors = "";
	$retorno = false;
	if($usarSMTP){
		include_once("libs/phpmail/PHPMailerAutoload.php");
		$mail = new PHPMailer(true);

		try {
			$mail->IsSMTP(); // sem isso, o PHPMailer ignora as configurações de SMTP e tenta a função mail() nativa do PHP
			$mail->CharSet = $charset;
			$mail->Host = $smtp;
			$mail->SMTPDebug = 0;
			if($emailResposta != null && $emailResposta != ""){
				$mail->AddReplyTo($emailResposta, $nomeEmailResposta);
			}
			$mail->SMTPAuth = $autenticacao;
			$mail->SMTPSecure = $seguranca;
			$mail->Port = $porta;
			$mail->Username = $envio;
			$mail->Password = $senha_email;

			$mail->SetFrom($envio, $nome_envio);
			$mail->Subject = $assuntoPersonalizado;
			$mail->MsgHTML($emailCompleto);
			$mail->AddAddress($destinatario, "");

			$arquivoDkimTemp = configurarDkim($mail, $dkimAtivo, $dkimDominio, $dkimSelector, $dkimChavePrivada, $envio);

			$retorno = $mail->Send();

		} catch (phpmailerException $e) {
		  echo htmlspecialchars($e->errorMessage());
		} catch (Exception $e) {
		  echo htmlspecialchars($e->getMessage());
		} finally {
		  if(!empty($arquivoDkimTemp)){
		  	@unlink($arquivoDkimTemp);
		  }
		}

	}else{
		$retorno = @mail($destinatario, $assuntoPersonalizado, $emailCompleto, $headers);
	}

	if(!$retorno || !isset($_REQUEST['email']) || $_REQUEST['email'] == ""){
		echo "<h1 class=\"mensagem_ruim\">Erro ao enviar Email. Verifique as configurações de e-mail estão corretas.</h1>";
	}else{
		echo "<h1 class=\"mensagem\">Email de Teste Enviado com Sucesso</h1>";
	}
?>
<style>

h1.mensagem{
	padding:1em;
	display:block;
	color: #0F3776;
	margin: 0;
	text-align: center;
	font-weight: normal;
}

h1.mensagem_ruim{
	padding:1em;
	display:block;
	color: #740B0C;
	margin: 0;
	text-align: center;
	font-weight: normal;
}
</style>
<?php endif;?>
