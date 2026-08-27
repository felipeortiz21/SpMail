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
		$novaVariacao = (int) $_REQUEST['envio_variacao_percentual'];
		$novoDkimAtivo = isset($_REQUEST['dkim_ativo']) ? 1 : 0;
		$novoDkimDominio = trim($_REQUEST['dkim_dominio'] ?? '');
		$novoDkimSelector = trim($_REQUEST['dkim_selector'] ?? '');
		$novaDkimChave = trim($_REQUEST['dkim_chave_privada'] ?? '');
		$novaDkimChaveCifrada = criptografarSegredo($novaDkimChave);

		$rsSql = dbQuery(
			$con,
			"UPDATE config SET url=?, pasta=?, nome_empresa=?, smtp=?, porta=?, seguranca=?, autenticacao=?, email_resposta=?, nome_email_resposta=?, emails_por_hora=?, emails_por_hora_nao_comercial=?, horario_comercial_ini=?, horario_comercial_fin=?, envio_variacao_percentual=?, dkim_ativo=?, dkim_dominio=?, dkim_selector=?, dkim_chave_privada=?",
			"ssssssissiiiiiisss",
			$novaUrl, $novaPasta, $novoNomeEmpresa, $novoSmtp, $novaPorta, $novaSeguranca,
			$novaAutenticacao, $novoEmailResposta, $novoNomeEmailResposta,
			$novosEmailsPorHora, $novosEmailsPorHoraNaoComercial, $novoHorarioIni, $novoHorarioFin,
			$novaVariacao, $novoDkimAtivo, $novoDkimDominio, $novoDkimSelector, $novaDkimChaveCifrada
		);
		$msg = "Dados de Configuração foram Atualizados com Sucesso. Por favor, aguarde que até que a página seja recarregada automaticamente.";
		echo "<meta http-equiv='refresh' content='5'>";
		die("<div class='alert wrap'>$msg</div>");
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
		$cVariacao = $row["envio_variacao_percentual"] ?? 30;
		$cDkimAtivo = !empty($row["dkim_ativo"] ?? false);
		$cDkimDominio = $row["dkim_dominio"] ?? "";
		$cDkimSelector = $row["dkim_selector"] ?? "";
		$cDkimChave = !empty($row["dkim_chave_privada"]) ? descriptografarSegredo($row["dkim_chave_privada"]) : "";
	}
?>
<div class="wrap grupos">
	<!--Crud-->
	<h1>Configurações do Sistema</h1>
	<div class="area_crud">
		<div class="crud">
			<form method="post" action="configuracoes.php" id="formulario">
				<input type="hidden" name="acao" id="acao" value="1"  />
				<input type="text" name="url"  id="url" placeholder="https://DigiteSeuSite.com.br" required="true" value="<?php echo htmlspecialchars($cUrl); ?>"/>
				<input type="text" name="pasta"  id="pasta" placeholder="Deixe em branco se não usar subpasta" value="<?php echo htmlspecialchars($cPasta); ?>"/>
				<input type="text" name="nome_empresa"  id="nome_empresa" placeholder="Nome da Empresa" required="true" value="<?php echo htmlspecialchars($cNomeEmpresa);?>"/>
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
				<input type="number" name="emails_por_hora"  id="emails_por_hora" placeholder="Emails Enviados por Hora" required="true" value="<?php echo (int) $cEmailsPorHora;?>"  min="0" />
				<input type="number" name="emails_por_hora_nao_comercial"  id="emails_por_hora_nao_comercial" placeholder="Emails Enviados por Hora Não Comercial" required="true" value="<?php echo (int) $cEmailsPorHoraNaoComercial;?>"  min="0" />
				<input type="number" name="horario_comercial_ini"  id="horario_comercial_ini" placeholder="Início do Horário Comercial (Brasília)" required="true" value="<?php echo (int) $cHorarioComercialIni;?>" min="0" max="23"/>
				<input type="number" name="horario_comercial_fin"  id="horario_comercial_fin" placeholder="Fim do Horário Comercial (Brasília)" required="true" value="<?php echo (int) $cHorarioComercialFin;?>"  min="0" max="23"/>

				<h4>Anti-spam</h4>
				<p class="mini-info">Variação aleatória (%) aplicada ao intervalo entre um email e outro, pra evitar um padrão perfeitamente constante entre os envios (reconhecido como comportamento de bot por provedores como Gmail/Outlook). Ex: 30 = cada envio varia entre 70% e 130% do intervalo calculado a partir de "Emails por Hora".</p>
				<input type="number" name="envio_variacao_percentual" id="envio_variacao_percentual" placeholder="Variação Aleatória (%)" value="<?php echo (int) $cVariacao;?>" min="0" max="100"/>

				<h4>DKIM (opcional)</h4>
				<p class="mini-info">Assina os emails enviados com DKIM, aumentando a chance de não cair em spam. Totalmente opcional - deixe desativado se não tiver uma chave DKIM configurada no DNS do seu domínio.</p>
				<label><input type="checkbox" name="dkim_ativo" id="dkim_ativo" value="1" <?php echo $cDkimAtivo ? 'checked' : ''; ?>/> Ativar assinatura DKIM</label>
				<input type="text" name="dkim_dominio" id="dkim_dominio" placeholder="Domínio (ex: meudominio.com.br)" value="<?php echo htmlspecialchars($cDkimDominio); ?>"/>
				<input type="text" name="dkim_selector" id="dkim_selector" placeholder="Selector (ex: default, mail, spmail)" value="<?php echo htmlspecialchars($cDkimSelector); ?>"/>
				<textarea name="dkim_chave_privada" id="dkim_chave_privada" placeholder="Cole aqui a chave privada DKIM (formato PEM, -----BEGIN RSA PRIVATE KEY-----...)" rows="6"><?php echo htmlspecialchars($cDkimChave); ?></textarea>

				<div class="botoes" >
					<button type="submit">Salvar</button>
				</div>
			</form>

		</div>
		<div class="area_tabela_crud ">
			<h3>Gestão de Usuários</h3>
			<p>Para criar, editar ou desativar usuários do sistema (incluindo o papel de Administrador Geral), acesse a tela de <a href="usuarios.php">Usuários</a>.</p>
			<p>Para importar contatos em lote via CSV, acesse a tela de <a href="clientes.php">Contatos</a>.</p>
		</div>
    </div>
</div>

<?php include "footer.php"; ?>
