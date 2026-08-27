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
	include "libs/db.php";
	include "functions.php";
	protegePagina();

	$edicao = false;
	$dadosMensagem = [];

	if(isset($_REQUEST["id_mail"])){
		$id = (int) $_REQUEST["id_mail"];
		$rs = dbQuery($con, "SELECT * FROM mensagens WHERE id=?", "i", $id);
		$dadosMensagem = mysqli_fetch_array($rs) ?: [];

		//Registrar Atualização
		dbQuery($con, "UPDATE mensagens SET data_atualizacao=? WHERE id=?", "si", date('Y-m-d H:i:s'), $id);

		$edicao = true;
	}
?>
<div class="wrap confirma">
	<h1>Enviar Email <b><?php echo (int) ($id ?? 0); ?></b></h1>
	<div class="emails">
	<form name="formulario" action="confirma.php" method="POST" id="emails"  onsubmit="return validarCampos()">
		<div class="enviarEmail">
			<div class="crud">
			<?php if(isset($_REQUEST["id_mail"])):?>
			<input type="hidden" name="id" value="<?php echo (int) $id;?>"/>
			<?php endif;?>
			<select name="origem" id="origem">
				<option value="0">Selecione o email de Envio</option>
				<?php
					$rs = dbQuery($con, "SELECT * FROM usuarios WHERE ativo = 1", "");
				?>
				<?php while($row = mysqli_fetch_array($rs)):?>
                	<?php if($row['id']==($dadosMensagem['email_envio'] ?? null)):?>
                   		<option name="emailEnvio" value="<?php echo (int) $row['id']; ?>" selected><?php echo htmlspecialchars($row['nome']); ?> - <?php echo htmlspecialchars($row['email']); ?></option>
                    <?php else:?>
						<option name="emailEnvio" value="<?php echo (int) $row['id']; ?>"><?php echo htmlspecialchars($row['nome']); ?> - <?php echo htmlspecialchars($row['email']); ?></option>
                    <?php endif;?>
				<?php endwhile; ?>
			</select>
			<select name="grupo" id="grupo">
				<option value="0">Selecione o Grupo</option>
				<?php
					$rsGrupos = dbQuery($con, "SELECT * FROM grupos", "");
				?>
				<?php while($row = mysqli_fetch_array($rsGrupos)):?>
                	<?php if($row['id']==($dadosMensagem['grupos'] ?? null)):?>
					<option name="selecionarGrupo" value="<?php echo (int) $row['id']; ?>" selected><?php echo htmlspecialchars($row['titulo']); ?></option>
                    <?php else:?>
                    <option name="selecionarGrupo" value="<?php echo (int) $row['id']; ?>"><?php echo htmlspecialchars($row['titulo']); ?></option>
                    <?php endif;?>

				<?php endwhile; ?>
				<option value="todos">Todos os Grupos</option>
			</select>
			<div class="variaveis">
				<label for="varAssunto">Inserir variável no assunto:</label>
				<select id="varAssunto" onchange="inserirVariavelAssunto(this.value); this.selectedIndex = 0;">
					<option value="">Escolha uma variável</option>
					<option value="{nome}">Nome do contato - {nome}</option>
					<option value="{email}">Email do contato - {email}</option>
					<option value="{telefone}">Telefone do contato - {telefone}</option>
				</select>
			</div>
            <?php if ($edicao):?>
				<input id="emails_adicionais" name="emails_adicionais" class="emails" placeholder="Insira Emails Adicionais (separado por ',')" value="<?php echo htmlspecialchars($dadosMensagem['emails_adicionais'] ?? ''); ?>"/>
				<input id="assunto" type="text" name="assunto" placeholder="Digite o Assunto do Email" value="<?php echo htmlspecialchars($dadosMensagem['assunto'] ?? ''); ?>"/>
				<div class="botoes">
					<button onclick="enviar()">Enviar</button>
				</div>
			</div>
			<div class="area_mensagem">
				<div class="variaveis">
					<label for="varCorpo">Inserir variável no corpo do email:</label>
					<select id="varCorpo" onchange="inserirVariavelCorpo(this.value); this.selectedIndex = 0;">
						<option value="">Escolha uma variável</option>
						<option value="{nome}">Nome do contato - {nome}</option>
						<option value="{email}">Email do contato - {email}</option>
						<option value="{telefone}">Telefone do contato - {telefone}</option>
					</select>
				</div>
				<textarea id="mensagem" name="mensagem" class="textarea" placeholder="Escreva Sua Mensagem">
                <?php echo $dadosMensagem['mensagem'] ?? ''; ?>
				</textarea>
			</div>
            <?php else:?>
            	<input id="emails_adicionais" name="emails_adicionais" class="emails" placeholder="Insira Emails Adicionais (separado por ',')" />
				<input id="assunto" type="text" name="assunto" placeholder="Digite o Assunto do Email"/>

				<div class="botoes">
					<button onclick="enviar()">Enviar</button>
				</div>
			</div>
			<div class="area_mensagem">
				<div class="variaveis">
					<label for="varCorpo">Inserir variável no corpo do email:</label>
					<select id="varCorpo" onchange="inserirVariavelCorpo(this.value); this.selectedIndex = 0;">
						<option value="">Escolha uma variável</option>
						<option value="{nome}">Nome do contato - {nome}</option>
						<option value="{email}">Email do contato - {email}</option>
						<option value="{telefone}">Telefone do contato - {telefone}</option>
					</select>
				</div>
				<textarea id="mensagem" name="mensagem" class="textarea" placeholder="Escreva Sua Mensagem">
				</textarea>
			</div>
            <?php endif;?>


		</div>

	</form>
		<iframe id="form_target" name="form_target" style="display:none"></iframe>
		<form id="uploadForm" action="postAcceptor.php" target="form_target" method="post" enctype="multipart/form-data" style="width:0px;height:0;overflow:hidden">
			<input name="image" type="file"  accept="image/*"  onchange="$('#uploadForm').submit();this.value='';">
		</form>
	</div>

</div>
<script>
	function inserirVariavelAssunto(variavel){
		if(!variavel) return;
		var campo = document.getElementById('assunto');
		var inicio = campo.selectionStart || campo.value.length;
		var fim = campo.selectionEnd || campo.value.length;
		campo.value = campo.value.substring(0, inicio) + variavel + campo.value.substring(fim);
		campo.focus();
	}

	function inserirVariavelCorpo(variavel){
		if(!variavel) return;
		if(typeof tinymce !== 'undefined' && tinymce.activeEditor){
			tinymce.activeEditor.execCommand('mceInsertContent', false, variavel);
		}else{
			var campo = document.getElementById('mensagem');
			campo.value += variavel;
		}
	}
</script>
<?php
	include "footer.php";
	?>
