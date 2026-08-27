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
	protegePaginaAdmin();
?>
<?php
	$msg = "";
	$erroLogo = "";
	$rsSql = false;

	if(isset($_REQUEST["url"])){
		$novaUrl = $_REQUEST['url'];
		$novaPasta = $_REQUEST['pasta'];
		$novoNomeEmpresa = $_REQUEST['nome_empresa'];
		$novoSmtp = $_REQUEST['smtp'];
		$novaPorta = $_REQUEST['porta'];
		$novaSeguranca = $_REQUEST['seguranca'];
		$novaAutenticacao = (int) $_REQUEST['autenticacao'];
		$novoEmailResposta = $_REQUEST['email_resposta'];
		$novoNomeEmailResposta = $_REQUEST['nome_email_resposta'];
		$novosEmailsPorHora = (int) $_REQUEST['emails_por_hora'];
		$novosEmailsPorHoraNaoComercial = (int) $_REQUEST['emails_por_hora_nao_comercial'];
		$novoHorarioIni = (int) $_REQUEST['horario_comercial_ini'];
		$novoHorarioFin = (int) $_REQUEST['horario_comercial_fin'];
		$novoAtrasoMinimo = (int) $_REQUEST['envio_atraso_minimo_segundos'];
		$novoAtrasoMaximo = (int) $_REQUEST['envio_atraso_maximo_segundos'];
		$novoDkimAtivo = isset($_REQUEST['dkim_ativo']) ? 1 : 0;
		$novoDkimDominio = trim($_REQUEST['dkim_dominio'] ?? '');
		$novoDkimSelector = trim($_REQUEST['dkim_selector'] ?? '');
		$novaDkimChave = trim($_REQUEST['dkim_chave_privada'] ?? '');
		$novaDkimChaveCifrada = criptografarSegredo($novaDkimChave);

		// Upload de logo (whitelabel) - opcional; se nada for enviado, mantém a
		// logo atual (não sobrescreve com vazio).
		$novoLogoPath = null;
		if(isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE){
			if($_FILES['logo']['error'] !== UPLOAD_ERR_OK){
				$erroLogo = "Não foi possível receber o arquivo da logo.";
			}elseif($_FILES['logo']['size'] > 2 * 1024 * 1024){
				$erroLogo = "A logo enviada passa de 2MB - envie um arquivo menor.";
			}else{
				$extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
				$partesNome = explode('.', $_FILES['logo']['name']);
				$ext = strtolower(array_pop($partesNome));

				if(!in_array($ext, $extensoesPermitidas, true)){
					$erroLogo = "Formato não permitido. Envie jpg, png, gif ou webp.";
				}elseif(@getimagesize($_FILES['logo']['tmp_name']) === false){
					$erroLogo = "O arquivo enviado não é uma imagem válida.";
				}else{
					$pastaUploads = __DIR__ . '/uploads';
					if(!is_dir($pastaUploads)){
						mkdir($pastaUploads, 0755, true);
					}
					$nomeArquivo = 'logo_' . bin2hex(random_bytes(4)) . '.' . $ext;
					move_uploaded_file($_FILES['logo']['tmp_name'], $pastaUploads . '/' . $nomeArquivo);
					$novoLogoPath = 'uploads/' . $nomeArquivo;
				}
			}
		}

		if($erroLogo === ""){
			if($novoLogoPath !== null){
				$rsSql = dbQuery(
					$con,
					"UPDATE config SET url=?, pasta=?, nome_empresa=?, smtp=?, porta=?, seguranca=?, autenticacao=?, email_resposta=?, nome_email_resposta=?, emails_por_hora=?, emails_por_hora_nao_comercial=?, horario_comercial_ini=?, horario_comercial_fin=?, envio_atraso_minimo_segundos=?, envio_atraso_maximo_segundos=?, dkim_ativo=?, dkim_dominio=?, dkim_selector=?, dkim_chave_privada=?, logo_path=?",
					"ssssssissiiiiiiissss",
					$novaUrl, $novaPasta, $novoNomeEmpresa, $novoSmtp, $novaPorta, $novaSeguranca,
					$novaAutenticacao, $novoEmailResposta, $novoNomeEmailResposta,
					$novosEmailsPorHora, $novosEmailsPorHoraNaoComercial, $novoHorarioIni, $novoHorarioFin,
					$novoAtrasoMinimo, $novoAtrasoMaximo, $novoDkimAtivo, $novoDkimDominio, $novoDkimSelector, $novaDkimChaveCifrada, $novoLogoPath
				);
			}else{
				$rsSql = dbQuery(
					$con,
					"UPDATE config SET url=?, pasta=?, nome_empresa=?, smtp=?, porta=?, seguranca=?, autenticacao=?, email_resposta=?, nome_email_resposta=?, emails_por_hora=?, emails_por_hora_nao_comercial=?, horario_comercial_ini=?, horario_comercial_fin=?, envio_atraso_minimo_segundos=?, envio_atraso_maximo_segundos=?, dkim_ativo=?, dkim_dominio=?, dkim_selector=?, dkim_chave_privada=?",
					"ssssssissiiiiiiisss",
					$novaUrl, $novaPasta, $novoNomeEmpresa, $novoSmtp, $novaPorta, $novaSeguranca,
					$novaAutenticacao, $novoEmailResposta, $novoNomeEmailResposta,
					$novosEmailsPorHora, $novosEmailsPorHoraNaoComercial, $novoHorarioIni, $novoHorarioFin,
					$novoAtrasoMinimo, $novoAtrasoMaximo, $novoDkimAtivo, $novoDkimDominio, $novoDkimSelector, $novaDkimChaveCifrada
				);
			}
			$msg = "Dados de Configuração foram Atualizados com Sucesso. Por favor, aguarde que até que a página seja recarregada automaticamente.";
			echo "<meta http-equiv='refresh' content='5'>";
			die("<div class='alert wrap'>$msg</div>");
		}
	}
?>
<?php
	$strSQL = "SELECT * FROM config LIMIT 1";
	$rs = mysqli_query($con,$strSQL);

	while($row = mysqli_fetch_array($rs)){
		$cUrl = $row["url"];
		$cPasta = $row["pasta"];
		$cNomeEmpresa = $row["nome_empresa"];
		$cSmtp = $row["smtp"];
		$cPorta = $row["porta"];
		$cSeguranca = $row["seguranca"];
		$cAutenticacao = $row["autenticacao"];
		$cEmailResposta = $row["email_resposta"];
		$cNomeEmailResposta = $row["nome_email_resposta"];
		$cEmailsPorHora = $row["emails_por_hora"];
		$cEmailsPorHoraNaoComercial = $row["emails_por_hora_nao_comercial"];
		$cHorarioComercialIni = $row["horario_comercial_ini"];
		$cHorarioComercialFin = $row["horario_comercial_fin"];
		$cAtrasoMinimo = $row["envio_atraso_minimo_segundos"] ?? 2;
		$cAtrasoMaximo = $row["envio_atraso_maximo_segundos"] ?? 5;
		$cDkimAtivo = !empty($row["dkim_ativo"] ?? false);
		$cDkimDominio = $row["dkim_dominio"] ?? "";
		$cDkimSelector = $row["dkim_selector"] ?? "";
		$cDkimChave = !empty($row["dkim_chave_privada"]) ? descriptografarSegredo($row["dkim_chave_privada"]) : "";
		$cLogoPath = $row["logo_path"] ?? "";
	}

	$logoAtualUrl = $cLogoPath !== "" ? $caminhoURL.$cLogoPath : $caminhoURL."assets/simbolo.svg";
?>
<div class="wrap">
	<h1>Configurações do Sistema</h1>

	<?php if($erroLogo !== ""): ?>
		<div class="alert"><?php echo htmlspecialchars($erroLogo); ?></div>
	<?php endif; ?>

	<form method="post" action="configuracoes.php" id="formulario" enctype="multipart/form-data" style="max-width: 720px;">
		<input type="hidden" name="acao" id="acao" value="1"  />

		<details class="secao" open>
			<summary>Dados da Empresa e URL</summary>
			<div class="secao-corpo">
				<input type="text" name="url"  id="url" placeholder="https://DigiteSeuSite.com.br" required="true" value="<?php echo htmlspecialchars($cUrl); ?>"/>
				<input type="text" name="pasta"  id="pasta" placeholder="Deixe em branco se não usar subpasta" value="<?php echo htmlspecialchars($cPasta); ?>"/>
				<input type="text" name="nome_empresa"  id="nome_empresa" placeholder="Nome da Empresa" required="true" value="<?php echo htmlspecialchars($cNomeEmpresa);?>"/>
			</div>
		</details>

		<details class="secao" open>
			<summary>SMTP</summary>
			<div class="secao-corpo">
				<input type="text" name="smtp"  id="smtp" placeholder="Endereço STMP" required="true" value="<?php echo htmlspecialchars($cSmtp);?>"/>
				<input type="text" name="porta"  id="porta" placeholder="Porta STMP" required="true" value="<?php echo htmlspecialchars($cPorta) ;?>"/>
				<select name="seguranca" id="seguranca" placeholder="Tipo de Segurança" required>
					<option value="ssl" <?php echo $cSeguranca === 'ssl' ? 'selected' : ''; ?>>SSL</option>
					<option value="tls" <?php echo $cSeguranca === 'tls' ? 'selected' : ''; ?>>TLS</option>
					<option value="" <?php echo $cSeguranca === '' ? 'selected' : ''; ?>>Nenhuma</option>
				</select>
				<select name="autenticacao" id="autenticacao" placeholder="Tipo de Autenticação" required>
					<option value="1" <?php echo ((int)$cAutenticacao === 1) ? 'selected' : ''; ?>>Requer Autenticação</option>
					<option value="0" <?php echo ((int)$cAutenticacao === 0) ? 'selected' : ''; ?>>Não Requer Autenticação</option>
				</select>
				<input type="text" name="email_resposta"  id="email_resposta" placeholder="Email Padrão para Respostas" required="true" value="<?php echo htmlspecialchars($cEmailResposta) ;?>"/>
				<input type="text" name="nome_email_resposta"  id="nome_email_resposta" placeholder="Nome Padrão para Respostas" required="true" value="<?php echo htmlspecialchars($cNomeEmailResposta);?>"/>
			</div>
		</details>

		<details class="secao" open>
			<summary>Limites de Envio e Horário Comercial</summary>
			<div class="secao-corpo">
				<input type="number" name="emails_por_hora"  id="emails_por_hora" placeholder="Emails Enviados por Hora" required="true" value="<?php echo (int) $cEmailsPorHora;?>"  min="0" />
				<input type="number" name="emails_por_hora_nao_comercial"  id="emails_por_hora_nao_comercial" placeholder="Emails Enviados por Hora Não Comercial" required="true" value="<?php echo (int) $cEmailsPorHoraNaoComercial;?>"  min="0" />
				<input type="number" name="horario_comercial_ini"  id="horario_comercial_ini" placeholder="Início do Horário Comercial (Brasília)" required="true" value="<?php echo (int) $cHorarioComercialIni;?>" min="0" max="23"/>
				<input type="number" name="horario_comercial_fin"  id="horario_comercial_fin" placeholder="Fim do Horário Comercial (Brasília)" required="true" value="<?php echo (int) $cHorarioComercialFin;?>"  min="0" max="23"/>
			</div>
		</details>

		<details class="secao">
			<summary>Anti-spam e DKIM <small>(avançado, opcional)</small></summary>
			<div class="secao-corpo">
				<p class="mini-info">Atraso aleatório (em segundos) entre um email e outro, pra evitar um padrão perfeitamente constante entre os envios (reconhecido como comportamento de bot por provedores como Gmail/Outlook). Ex: 2 a 5 = cada envio espera um tempo aleatório entre 2 e 5 segundos antes do próximo.</p>
				<input type="number" name="envio_atraso_minimo_segundos" id="envio_atraso_minimo_segundos" placeholder="Atraso Mínimo (segundos)" value="<?php echo (int) $cAtrasoMinimo;?>" min="1"/>
				<input type="number" name="envio_atraso_maximo_segundos" id="envio_atraso_maximo_segundos" placeholder="Atraso Máximo (segundos)" value="<?php echo (int) $cAtrasoMaximo;?>" min="1"/>

				<p class="mini-info">Assina os emails enviados com DKIM, aumentando a chance de não cair em spam. Totalmente opcional - deixe desativado se não tiver uma chave DKIM configurada no DNS do seu domínio.</p>
				<label><input type="checkbox" name="dkim_ativo" id="dkim_ativo" value="1" <?php echo $cDkimAtivo ? 'checked' : ''; ?>/> Ativar assinatura DKIM</label>
				<input type="text" name="dkim_dominio" id="dkim_dominio" placeholder="Domínio (ex: meudominio.com.br)" value="<?php echo htmlspecialchars($cDkimDominio); ?>"/>
				<input type="text" name="dkim_selector" id="dkim_selector" placeholder="Selector (ex: default, mail, spmail)" value="<?php echo htmlspecialchars($cDkimSelector); ?>"/>
				<textarea name="dkim_chave_privada" id="dkim_chave_privada" placeholder="Cole aqui a chave privada DKIM (formato PEM, -----BEGIN RSA PRIVATE KEY-----...)" rows="6"><?php echo htmlspecialchars($cDkimChave); ?></textarea>
			</div>
		</details>

		<details class="secao" open>
			<summary>Identidade Visual <small>(whitelabel)</small></summary>
			<div class="secao-corpo">
				<p class="mini-info">Troca a logo exibida na tela de login e no topo do sistema. Deixe sem enviar nada pra manter a atual. Formatos aceitos: jpg, png, gif, webp (até 2MB).</p>
				<div class="logo-preview">
					<img src="<?php echo htmlspecialchars($logoAtualUrl); ?>" alt="Logo atual"/>
					<span><?php echo $cLogoPath !== "" ? "Logo customizada em uso" : "Usando a logo padrão do SpMail"; ?></span>
				</div>
				<input type="file" name="logo" id="logo" accept="image/png,image/jpeg,image/gif,image/webp"/>
			</div>
		</details>

		<div class="botoes">
			<button type="submit">Salvar</button>
		</div>
	</form>

	<details class="secao" style="max-width: 720px;">
		<summary>Gestão de Usuários</summary>
		<div class="secao-corpo">
			<p>Para criar, editar ou desativar usuários do sistema (incluindo o papel de Administrador Geral), acesse a tela de <a href="usuarios.php">Usuários</a>.</p>
			<p>Para importar contatos em lote via CSV, acesse a tela de <a href="clientes.php">Contatos</a>.</p>
		</div>
	</details>
</div>

<?php include "footer.php"; ?>
