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
?>
<?php
	$grupoSelecionado = 0;
	$msg = false;

	if(isset($_REQUEST["grupos"])){
		$grupoSelecionado = (int) $_REQUEST["grupos"];
	}
	//MANIPULAR ITENS
	if(isset($_REQUEST['acao'])){
		$grupoSelecionado = (int) $_REQUEST["grupo"];
		$emailContato = trim($_REQUEST['email'] ?? '');
		$nomeContato = trim($_REQUEST['nome'] ?? '');
		$telefoneContato = trim($_REQUEST['telefone'] ?? '');

		if($_REQUEST['acao'] == '2'){
			dbQuery(
				$con,
				"UPDATE contatos SET email=?, nome=?, telefone=?, grupo=? WHERE id=?",
				"sssii",
				$emailContato, $nomeContato, $telefoneContato, $grupoSelecionado, (int) $_REQUEST['id']
			);
			$msg = "Contato ".htmlspecialchars($emailContato)." atualizado com sucesso.";
		}else if($_REQUEST['acao'] == '3'){
			dbQuery($con, "DELETE FROM contatos WHERE id=? AND grupo=?", "ii", (int) $_REQUEST['id'], $grupoSelecionado);
			$msg = "Contato ".htmlspecialchars($emailContato)." foi excluído com sucesso deste grupo (".$grupoSelecionado.").";
		}
		else if($_REQUEST['acao'] == '1'){
			$strRes = dbQuery($con, "SELECT email, aut FROM contatos WHERE email=? AND grupo=? LIMIT 1", "si", $emailContato, $grupoSelecionado);
			if(mysqli_num_rows($strRes) == 0){
				dbQuery(
					$con,
					"INSERT INTO contatos VALUES(DEFAULT,?,?,?,?,'1')",
					"sssi",
					$emailContato, $nomeContato, $telefoneContato, $grupoSelecionado
				);
				$msg = "Contato ".htmlspecialchars($emailContato)." foi inserido com sucesso.";
			}else{
				$msg = "Email já cadastrado neste grupo.";
				$aut = 1;
				while($row = mysqli_fetch_array($strRes)){
					$aut = $row['aut'];
				}
				if($aut != 1){
					$msg = "Contato se descadastrou e não deseja mais receber emails";
				}
			}
		}
	}
?>
<?php
	if(isset($_REQUEST["grupos"]) || isset($_REQUEST['grupo'])){
		//SELECIONAR ITENS PARA PREENCHER A GRID
		$rs = dbQuery(
			$con,
			"SELECT MIN(cont.id) as id, cont.email, MAX(cont.nome) as nome, MAX(cont.telefone) as telefone, cont.grupo, MAX(g.titulo) AS titulo_grupo, MAX(cont.aut) as aut FROM contatos cont LEFT JOIN grupos g ON g.id = cont.grupo WHERE cont.grupo=? GROUP BY cont.email, cont.grupo ORDER BY aut DESC",
			"i",
			$grupoSelecionado
		);
	}
?>
<?php
	//SELECIONAR ITENS PARA PREENCHER OS GRUPOS
	$strSQLGrupos = "SELECT * FROM grupos ORDER BY titulo ASC";
	$rsGrupos = mysqli_query($con,$strSQLGrupos);
?>
<?php
	if($msg){
		echo "<div class='alert wrap'>" . htmlspecialchars($msg) . "</div>";
	}
?>
<div class="wrap contatos">
	<!--Crud-->
	<h1>Cadastro de Contatos</h1>
	<div class="area_crud">
		<div class="crud">
			<form method="post" action="#" id="formulario">
				<input type="hidden" name="acao" id="acao" value="1"  />
				<input type="text" name="id" id="id" placeholder="ID" />
				<input type="email" name="email" id="email" placeholder="Email" required="true"/>
				<input type="text" name="nome" id="nome" placeholder="Nome" required="true"/>
				<input type="text" name="telefone" id="telefone" placeholder="Telefone"/>
				<select name="grupo" id="grupo">
					<option value="0" selected="selected">Selecione o Grupo</option>
				<?php
					while($row = mysqli_fetch_array($rsGrupos)):
				?>
					<option value="<?php echo (int) $row['id']?>"><?php echo htmlspecialchars($row['titulo'])?></option>
				<?php
					endwhile;
				?>
				</select>

				<div class="botoes">
					<button type="submit">Gravar</button>
					<button type="reset" onclick="limpar()">Novo</button>
				</div>
			</form>
			<?php $rsGrupos = mysqli_query($con,$strSQLGrupos);?>

			<h3>Importar Arquivo CSV para os Contatos</h3>
			<form class="importar" id="formCSV">
				<select name="grupo" id="grupoImportacao">
					<option value="0" selected="selected" required>Selecione o Grupo</option>
					<?php
						while($row = mysqli_fetch_array($rsGrupos)):
					?>
					<option value="<?php echo (int) $row['id']?>"><?php echo htmlspecialchars($row['titulo'])?></option>
					<?php
						endwhile;
					?>
				</select>
				<input type="file" name="arquivoCSV" id="arquivoCSV" accept="text/*"/>
				<button type="submit">Importar</button>
				<h4 class="retorno" id="retornoDados"></h4>
				<div class="instrucoes">
					<p><b>Instruções:</b></p>
					<p>Para importar corretamente os dados, importe o arquivo CSV separado por ";" (ponto e vírgula).</p>
					<p>Utilize o programa de planilhas de sua preferência (como Calc ou Excel) para facilitar a organização das colunas do seu arquivo CSV.</p>
					<p>
						Coloque as colunas nas seguintes ordens:<br/>
						<b>email;nome;telefone</b>
					</p>
					<p>Quando você importa os emails, automaticamente todos estarão autorizados para envio e disponíveis no grupo selecionado acima. Caso você queira utilizar o email em mais de um grupo, faça uma nova importação no grupo correspondente.
					</p>
					<p>
						Exemplo do Documento:
						<p class="pre">contato@exemplo.com.br;Nome do Contato;81 986xx-xxxx<br/>outro@exemplo.com.br;Outro Nome;</p>
					</p>
					<p class="obs">
						Obs. Deixe vazio o campo que você não possua o dado. A importação passa por uma verificaçao de email, para que não sejam importados emails repetidos. Por isso, a importação pode demorar um pouco.
					</p>
					<p class="alert">
						NÃO IMPORTE DADOS ENQUANTO AINDA ESTIVER EM PROCESSO DE ENVIO DE EMAILS.<br/>
						Dependendo das configurações de sua hospedagem, ou servidor, isso pode deixa-lo lento ou cair.
					</p>
				</div>
			</form>
		</div>
		<div class="area_tabela_crud ">
			<form class="filtro" method="post" id="filtro">
				<label for="grupos">Filtrar por Grupos</label>
				<select name="grupos">
					<option value="0" selected="selected">Selecione o Grupo</option>
					<?php
						// $rsGrupos já foi percorrido nos dois selects acima - precisa
						// re-consultar antes de percorrer de novo (um mysqli_result só
						// itera uma vez).
						$rsGrupos = mysqli_query($con,$strSQLGrupos);
						while($row = mysqli_fetch_array($rsGrupos)):
					?>
						<option value="<?php echo (int) $row['id']?>"><?php echo htmlspecialchars($row['titulo'])?></option>
					<?php
						endwhile;
					?>
				</select>
				<button type="submit">Filtrar</button>
			</form>
		<?php if(isset($_REQUEST["grupos"]) || isset($_REQUEST["grupo"])):?>
			<?php
				$rsCont = dbQuery($con, "SELECT count(aut) as aut, (SELECT count(id) FROM contatos) as tot FROM contatos WHERE aut = '1'", "");
				while($rowC = mysqli_fetch_array($rsCont)){
					echo "<p>Total de Emails Cadastrados Neste Grupo: ". (int) $rowC["tot"] .".";
					echo "Desses, ". ((int) $rowC["tot"] - (int) $rowC["aut"]) ." desautorizaram o envio de emails</p>";
				}
			?>
			<div class="tabela">
			<table>
				<caption>Contatos</caption>
				<thead>
					<th>ID</th>
					<th>Email</th>
					<th>Nome</th>
					<th>Telefone</th>
					<th>Grupo</th>
					<th>Ação</th>
				</thead>
				<tbody>
					<?php
						while($row = mysqli_fetch_array($rs)):
					?>
					<tr <?php if($row['aut'] != '1'):?> style="opacity:.5; cursor:help" title="Este contato desautorizou o recebimento de emails." <?php endif; ?> >
						<td rel="id"><?php echo (int) $row['id']?></td>
						<td rel="email"><?php echo htmlspecialchars($row['email'])?></td>
						<td rel="nome"><?php echo htmlspecialchars($row['nome'])?></td>
						<td rel="telefone"><?php echo htmlspecialchars($row['telefone'])?></td>
						<td rel="grupo" id="<?php echo (int) $row['grupo']?>"><?php echo htmlspecialchars($row['titulo_grupo'])?></td>
						<td>
							<img onclick="editar(event)" src="<?php echo $caminhoURL; ?>assets/editar.png" title="Editar Contato"/>
							<img src="<?php echo $caminhoURL; ?>assets/delete.png" title="Exluir Contato" onclick="excluir(<?php echo (int) $row['id']?>,'<?php echo htmlspecialchars($row['email'], ENT_QUOTES)?>',<?php echo (int) $row['grupo']?>)"/>
						</td>
					</tr>
					<?php
						endwhile;
					?>
				</tbody>
			</table>

			</div>

		<?php endif;?>
		</div>
	</div>
</div>
<script>
	function limpar(){
		$("#acao").val("1");
	}

	function editar(event){
		relacionar(event);
		$("#acao").val("2"); // Ação 2 = Editar
	}

	function excluir(id,email,grupo){
		var r = confirm("Tem certeza que deseja excluir o contato "+email+"?");

		if (r == true) {
			$("#id").val(id);
			$("#email").val(email);
			$("#grupo").val(grupo);
		    $("#acao").val("3"); // Ação 3 = Excluir
		    $("form#formulario").submit();
		} else {
		   alert("O contato " + email + " não foi excluído deste grupo.")
		}
	}

	function relacionar(event){
		var pai = $(event.target).parent().parent();
		//relacionar
		$(pai).find("td").each(function(){
			var campo = $(this).attr("rel");
			//AdicionarValor
			$("form#formulario").find("#"+campo).val($(this).html());
		});
         $("form#formulario").find("#grupo").val($(pai).find("td[rel=grupo]").attr("id"));
	}

	$("#formCSV").submit(function(){
		if($("#grupoImportacao").val() == 0 || $("#arquivoCSV").val() == ''){
			alert("Selecione um Grupo e um Arquivo para Prosseguir");
		}else{
			var formData = new FormData(this);
			formData.set("grupo", $("#grupoImportacao").val());
			$("#retornoDados").html( "Enviando Dados. Por favor Aguarde." );

			$.ajax({
			  method: "POST",
			  url: "importCSV.php",
			  data: formData,
				cache: false,
				contentType: false,
				processData: false
			})
			.done(function( msg ) {
				$("#retornoDados").html( msg );
				$("#formCSV")[0].reset();
			});
		}
		return false;
	});
</script>

<?php include "footer.php"; ?>
