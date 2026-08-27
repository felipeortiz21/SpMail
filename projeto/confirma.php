<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	include "header.php";
	include "libs/seguranca.php";
	include_once "libs/db.php";
	include "functions.php";
	protegePagina();
	ini_set('error_reporting', E_ALL);

	$email_envio = (int) $_REQUEST['origem'];
	$grupo = $_REQUEST['grupo'];
	$emails_adicionais = trim($_REQUEST['emails_adicionais']);
	$assunto = $_REQUEST['assunto'];
	$mensagem = $_REQUEST['mensagem'];
	$slug = criarSlug($assunto);
	if($grupo == "todos"){
		$grupo = 0;
	}
	$grupo = (int) $grupo;

	$nomeGrupo = "";
	if($grupo > 0){
		$rs = dbQuery($con, "SELECT titulo FROM grupos WHERE id=?", "i", $grupo);
		while($row = mysqli_fetch_array($rs)){
			$nomeGrupo = $row["titulo"];
		}
	}else{
		$nomeGrupo = "Todos";
	}

	if(isset($_REQUEST['id'])){
		$id = (int) $_REQUEST['id'];
		dbQuery(
			$con,
			"UPDATE mensagens SET grupos=?, emails_adicionais=?, mensagem=?, assunto=?, email_envio=?, status='0', data_envio_ini=DEFAULT, data_envio_fin=DEFAULT, data_atualizacao=DEFAULT, obs='Preparando Para Envio', url='' WHERE id=?",
			"sssssi",
			$grupo, $emails_adicionais, $mensagem, $assunto, $email_envio, $id
		);
	}else{
		dbQuery(
			$con,
			"INSERT INTO mensagens VALUES(DEFAULT,?,?,?,?,?,0,DEFAULT,DEFAULT,DEFAULT,'Preparando Para Envio','')",
			"sssss",
			$grupo, $emails_adicionais, $mensagem, $assunto, $email_envio
		);
		$id = mysqli_insert_id($con);
	}

	//Pegar Email por Extenso
	$rs = dbQuery($con, "SELECT * FROM usuarios WHERE id=?", "i", $email_envio);
	while($row = mysqli_fetch_array($rs)){
		$email_envio = $row["nome"];
		$nome_contato = $row["nome"];
		$email_contato = $row["email"];
	}

	$url = 'id'.$id.'-'.$slug;
	$id_mensagem = $id;

	//Gravar URL
	dbQuery($con, "UPDATE mensagens SET url=? WHERE id=?", "si", $url, $id);


	//GERAR ARQUIVO HTML DO EMAIL
	$myfile = fopen("emails/".$url.".html", "w") or die("Não foi Possível Gerar o Email");

	$url = $caminhoURL."emails/$url.html"; //Corrigir URL para o Banco e Link completo

	$email = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html xmlns="http://www.w3.org/1999/xhtml">
			<head>
			<style>
			body{
				font-family: Helvetica, Roboto, Arial;
			}
			</style>
			<title>'.$assunto.'</title>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
			</head>
			<body>';
	$email .= $mensagem;

	//Dados Template
	$email .= "<center style='font-size:.8em;'>Caso não consiga visualizar corretamente este email, <a href='$url' target='_blank'>clique aqui para acessar</a>.</center>";
	$email .= '</body>';
	fwrite($myfile, $email);
	fclose($myfile);


	if($emails_adicionais == ""){
		$emails_adicionais = 'Nenhum Email Adicional';
	}

?>
<script language="javascript" type="text/javascript">
  function resizeIframe(obj) {
    obj.style.height = obj.contentWindow.document.body.scrollHeight + 'px';
  }

  function enviarTeste(evt){
	  var url="enviar_teste.php?";
	  url += "id=";
	  url += $("#teste_id").val();
	  url += "&email=";
	  url += $("#teste_email").val();

	  $.colorbox({href:url});
  }
</script>
<div class="wrap confirma">
	<h1>Confirmação de Envio de Email</h1>
	<div class="enviarEmail">
		<div class="crud">
		<p>Por favor, verifique abaixo o resultado do email. Caso esteja tudo bem, clique em ENVIAR E-MAIL. Lembre-se que o formato do email pode sofrer algumas alterações dependendo do tipo do cliente de email e do navegador do destinatário.</p>
		<div class="buttons">

				<div>
					<input type="hidden" name="id" id="teste_id" value="<?php echo (int) $id; ?>"/>
					<input type="email" name="email" id="teste_email" placeholder="Email de Teste" required="true"/>
					<button class="teste" onClick="enviarTeste(event)">Enviar Teste</button>
				</div>
				<form action="enviar.php">
					<input type="hidden" name="id" id="id" value="<?php echo (int) $id; ?>"/>
					<input type="hidden" name="acao" id="acao" value="1"/>
					<input type="hidden" name="grupot" id="grupo" value="<?php echo (int) $grupo; ?>"/>
					<button>Enviar E-mail</button>
				</form>
				<button class="voltar">Voltar</button>

		</div>
		</div>
		<div class="resumo">
			<h2>Resumo</h2>
			<p><b>Enviado Por: </b><?php echo htmlspecialchars($email_envio) ?></p>
			<p><b>Para Categoria: </b><?php echo htmlspecialchars($nomeGrupo) ?></p>
			<p><b>Outros Destinatários: </b><?php echo htmlspecialchars($emails_adicionais) ?></p>
			<p><b>Assunto: </b><?php echo htmlspecialchars($assunto)?></p>
			<p><b>URL: </b><a href='<?php echo htmlspecialchars($url)?>' target='_blank'><?php echo htmlspecialchars($url)?></a></p>
			<iframe class="conferir" width="700" src="<?php echo htmlspecialchars($url) ?>" frameborder="0" scrolling="no" onload="javascript:resizeIframe(this);" id="iframe" onload='javascript:resizeIframe(this);'/>
		</div>
	</div>
</div>
<?php
	include "footer.php";
	?>
