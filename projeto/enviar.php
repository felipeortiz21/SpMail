<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	// Este arquivo é chamado tanto por um clique do usuário logado (o navegador,
	// com sessão ativa) quanto pelo próprio servidor, em segundo plano, via curl,
	// para continuar um envio em lotes - essa segunda chamada NÃO tem sessão.
	// Por isso a autenticação aqui aceita OU uma sessão válida, OU um token
	// assinado (gerado com a APP_KEY) específico para a mensagem em questão.
	include_once("libs/config.php");
	include_once("libs/seguranca.php");
	include_once("libs/db.php");
	include_once("libs/template.php");
	// tokenContinuacaoEnvio() mora em libs/seguranca.php (usada também pelo
	// watchdog em cron/retomar_envios.php, que não tem sessão de navegador).

	$tokenRecebido = $_REQUEST['token'] ?? '';
	$tokenValido = isset($_REQUEST['id']) && hash_equals(tokenContinuacaoEnvio($_REQUEST['id']), $tokenRecebido);

	// Deixa o header.php saber que essa requisição pode não ter sessão de
	// navegador (chamada interna de continuação) sem quebrar o layout normal
	// quando é o usuário mesmo clicando em "Enviar".
	$GLOBALS['permitirSemSessao'] = $tokenValido;
	include "header.php";

	if(!$tokenValido){
		protegePagina();
	}

	ini_set('error_reporting', E_ALL);     //Reporta todos os erros.
	date_default_timezone_set('America/Sao_Paulo');
	$id = $_REQUEST["id"];
	$acao = $_REQUEST["acao"];

	$continuar = false;
	$dt = new DateTime();
    $horarioEnvio = $dt->format('Y-m-d H:i:s');

	if($horarioComercial_ini != ""){
		$horaAtual = $dt->format('H');
		if($horaAtual >= $horarioComercial_ini && $horaAtual <= $horarioComercial_fin){
			$emailsHora = $emailsHoraNaoComercial;
		}
	}

    $imediato = false;

	$assunto = "";
	$email_envio = "";
	$emails_adicionais = "";
	$grupo = "";
	$mensagem = "";
	$status = "";
	$data_envio = "";
	$data_atualizacao = "";
	$obs = "";
	$email = "";
	$enviados = "";
	$emailsRestantes = "";

	if(isset($_REQUEST["imediato"])){
		$imediato = true;
	}


	if(isset($_REQUEST["continuar"])){
		$continuar = true;
	}else{
	    //Registrar o Início do Envio
		dbQuery($con, "UPDATE mensagens SET data_envio_ini=?, status='1' WHERE id=?", "si", $horarioEnvio, $id);
	}

	$EmailsEnviados = array();
	$EmailsFaltantes = array();
	$arrEmailsComp = array();
	$arrEmail = array();

	// Busca nome/telefone de um contato pelo email (usado para as emails
	// adicionais digitados manualmente, que não têm um registro de origem)
	function buscarContatoPorEmail($con, $email){
		$rs = dbQuery($con, "SELECT nome, telefone FROM contatos WHERE email = ? LIMIT 1", "s", $email);
		$row = $rs ? mysqli_fetch_assoc($rs) : null;
		return $row ?: ['nome' => '', 'telefone' => ''];
	}
?>

<?php
	if($acao==1):

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
			$grupo = $row["grupos"];
            $emailsComp = $row["emails_adicionais"];
        }

        if(trim($emailsComp) != "" ){
        	$arrEmailsComp = explode(",", $emailsComp);
        }

        //Caso o email já tenha sido enviado anterioremente, continue os emails pela tabela restante
        if($continuar){
	        $rs = dbQuery(
	        	$con,
	        	"SELECT r.email as email, MAX(c.nome) as nome, MAX(c.telefone) as telefone
	        	 FROM restantes r
	        	 LEFT JOIN contatos c ON c.email = r.email
	        	 WHERE r.mensagem=? AND r.enviado='0'
	        	 GROUP BY r.email
	        	 LIMIT 10",
	        	"i",
	        	$id
	        );
	    }else{
			if($grupo > 0){
				$rs = dbQuery($con, "SELECT email, nome, telefone FROM contatos WHERE grupo=? AND aut='1'", "i", $grupo);
			}else{
				$rs = dbQuery($con, "SELECT email, nome, telefone FROM contatos WHERE aut='1'", "");
			}
		}

        $i = 0;
		while($row = mysqli_fetch_array($rs)){
			$arrEmail[$i] = ['email' => $row["email"], 'nome' => $row["nome"] ?? '', 'telefone' => $row["telefone"] ?? ''];
			if(!$continuar){
				if (!filter_var($arrEmail[$i]['email'], FILTER_VALIDATE_EMAIL) === false) {
					dbQuery($con, "INSERT INTO restantes (mensagem, email, enviado) VALUES(?,?,'0')", "is", $id, $arrEmail[$i]['email']);
				}
			}
            $i++;
		}

		if(!$continuar){
			for($i=0;$i<count($arrEmailsComp);$i++){
				if (!filter_var($arrEmailsComp[$i], FILTER_VALIDATE_EMAIL) === false) {
					dbQuery($con, "INSERT INTO restantes (mensagem, email, enviado) VALUES(?,?,'0')", "is", $id, $arrEmailsComp[$i]);
				}
			}
		}


		//Agora precisamos adicionar os emails complementares aos emails que serão utilizados
		if(trim($emailsComp) != "" && !$continuar){
			$arrEmailsCompRico = [];
			foreach($arrEmailsComp as $emailComp){
				$emailComp = trim($emailComp);
				if($emailComp === '') continue;
				$contatoEncontrado = buscarContatoPorEmail($con, $emailComp);
				$arrEmailsCompRico[] = ['email' => $emailComp, 'nome' => $contatoEncontrado['nome'], 'telefone' => $contatoEncontrado['telefone']];
			}
			$arrEmail = array_merge($arrEmailsCompRico, $arrEmail);
		}


		//---FIM DA EXPLICAÇÃO
		$url = $caminhoURL."/emails/".$url.".html";
	    for($i=0;$i < count($arrEmail); $i++){        //Inicia o laço para construir os emails.

		    if($i < 1){  // Manipular para enviar mais de um email no mesmo processo
		    	$contatoAtual = $arrEmail[$i];
		    	$emailDestino = $contatoAtual['email'];
		    	$assuntoPersonalizado = substituirVariaveis($assunto, $contatoAtual);
		    	$mensagemPersonalizada = substituirVariaveis($mensagem, $contatoAtual);

			    $urlCancelamento = $caminhoURL."/cancelamento.php?email=".urlencode($emailDestino);
				//Montar Mensagem
				$emailCompleto = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
					<html xmlns="http://www.w3.org/1999/xhtml">
					<head>
					<style>
					body{
						font-family: Helvetica, Roboto, Arial;
					}
					</style>
					<title>'.htmlspecialchars($assuntoPersonalizado).'</title>
					<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
					</head>
					<body>';
				$emailCompleto .= $mensagemPersonalizada;
        		$emailCompleto .= "<img src=\"".$caminhoURL."/contador.php?email=".urlencode($emailDestino)."&mensagem=".$id."\" height=\"90\" style=\"height: 90px; width: auto; text-align: center; border: none;\" />";
				// Removido a pedido: link de "visualizar no navegador" e "Cancelar
				// Inscrição" - o email enviado passa a ter só o conteúdo criado na
				// campanha. cancelamento.php continua existindo e acessível por URL
				// direta, só não tem mais link automático dentro do email.
				$emailCompleto .= '</body>';

				$urlAtivaSimples = "href='".$caminhoURL."link.php?email=".urlencode($emailDestino)."&mensagem=$id&link=";
				$urlAtivaDupla = 'href="'.$caminhoURL."link.php?email=".urlencode($emailDestino)."&mensagem=$id&link=";
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

					$mail = new PHPMailer();
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
					$mail->AddAddress($emailDestino, "");

					$arquivoDkimTemp = configurarDkim($mail, $dkimAtivo, $dkimDominio, $dkimSelector, $dkimChavePrivada, $envio);

					$retorno = $mail->Send();

					if($arquivoDkimTemp){
						@unlink($arquivoDkimTemp);
					}
				}else{
					$retorno = @mail($emailDestino, $assuntoPersonalizado, $emailCompleto, $headers);
				}

				if($retorno){
					dbQuery($con, "UPDATE restantes SET enviado='1', erro_mensagem='' WHERE mensagem=? AND email=?", "is", $id, $emailDestino);
				}else{
					// Guarda o motivo do erro (do PHPMailer, quando disponível) pra dar
					// pra diagnosticar falhas em massa sem precisar vasculhar log de servidor.
					$motivoErro = isset($mail) && !empty($mail->ErrorInfo) ? substr($mail->ErrorInfo, 0, 500) : 'Falha desconhecida ao enviar';
					dbQuery($con, "UPDATE restantes SET enviado='2', erro_mensagem=? WHERE mensagem=? AND email=?", "sis", $motivoErro, $id, $emailDestino);
				}
			}
        }
?>
<?php
	endif;

	if($grupo > 0){
		$rsGrupo2 = dbQuery($con, "SELECT titulo FROM grupos WHERE id=?", "i", $grupo);
		$nomeGrupo = "Todos";
		while($row = mysqli_fetch_array($rsGrupo2)){
			$nomeGrupo = $row["titulo"];
		}
	}else{
		$nomeGrupo = "Todos";
	}
?>
<?php if(!$continuar): ?>
<div class="wrap so_tabela tela_confirmacao">
	<h1 class="sucesso">Iniciado Processo de Envio de Emails</h1>
    <center>
		<p>Utilize a Tela de <a href="<?php echo $caminhoURL; ?>emails.php">Emails Enviados</a> para
    <div class="area_tabela">
	   	<center>
	    <h3>Assunto: <?php echo htmlspecialchars($assunto); ?></h3>
	    <h3>Grupo: <?php echo htmlspecialchars($nomeGrupo); ?></h3>
	    <h3>Processo Iniciado em: <?php echo date('d/m/Y H:i',strtotime($horarioEnvio)); ?></h3>
	    <div class="tabela">
	    </div>

    </div>
</div>
<?php
	endif;
?>
<?php
	//Registrar o Atualização do Envio -- Retirar caso pese muito no servidor
	dbQuery($con, "UPDATE mensagens SET data_atualizacao=?, status='1', obs='Enviando' WHERE id=?", "si", $horarioEnvio, $id);

	include "footer.php";

	if(count($arrEmail) != 0){
		$local = $caminhoURL."enviar.php?id=".$id."&acao=".$acao."&continuar=1&token=".tokenContinuacaoEnvio($id);
		if($continuar):?>
			<h1>Envios Retomados</h1>
		<?php
			// Atraso aleatório (configurado em Configurações), pra não ter um
			// padrão perfeitamente constante entre os envios.
			sleep(segundosAleatoriosEntreEnvios($envioAtrasoMinimo, $envioAtrasoMaximo));
		endif;
		$local_escapado = escapeshellarg($local);
		$exec = exec("curl --request GET $local_escapado > /dev/null 2>/dev/null &"); //Executar de forma de assínscrona e em background

	}else{
		//Registrar o Fim do Envio
		dbQuery($con, "UPDATE mensagens SET data_envio_fin=?, status='2', obs='Terminado' WHERE id=?", "si", $horarioEnvio, $id);
	}
?>
