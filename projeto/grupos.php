<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	include "header.php";
	include "libs/seguranca.php";        //Conexão com o banco de dados.
	include_once "libs/db.php";
	include "functions.php";
	protegePagina();
?>
<?php
	//MANIPULAR ITENS
	$existe = 	isset($_REQUEST["titulo"]) &&
				isset($_REQUEST['descricao']);
	$rsSql = false;
	$msg = "";

	if($existe){
		$titulo = $_REQUEST['titulo'];
		$descricao = $_REQUEST['descricao'];

		if($_REQUEST['acao'] == '2'){
			$rsSql = dbQuery($con, "UPDATE grupos SET titulo=?, descricao=? WHERE id=?", "ssi", $titulo, $descricao, (int) $_REQUEST['id']);
			$msg = "Grupo ".htmlspecialchars($titulo)." Atualizado com Sucesso";
		}else if($_REQUEST['acao'] == '3'){
			$rsSql = dbQuery($con, "DELETE FROM grupos WHERE id=?", "i", (int) $_REQUEST['id']);
			$msg = "Grupo ".htmlspecialchars($titulo)." foi excluído";
		}
		else if($_REQUEST['acao'] == '1'){
			$rsSql = dbQuery($con, "INSERT INTO grupos VALUES(DEFAULT,?,?)", "ss", $titulo, $descricao);
			$msg = "Grupo ".htmlspecialchars($titulo)." foi inserido";
		}
	}
?>
<?php
	//SELECIONAR ITENS PARA PREENCHER A GRID
	$rs = dbQuery($con, "SELECT * FROM grupos ORDER BY titulo");
?>
<?php
	if($rsSql && isset($_REQUEST["id"])){
		echo "<div class='alert wrap'>$msg</div>";
	}
?>
<div class="wrap grupos">
	<!--Crud-->
	<h1>Cadastro de Grupos de Emails</h1>
	<div class="area_crud">
		<div class="crud">
			<form method="post" action="#" id="formulario">
				<input type="hidden" name="acao" id="acao" value="1"  />
				<input type="text" name="id" id="id" placeholder="ID" />
				<input type="text" name="titulo"  id="titulo" placeholder="Nome do Grupo" required="true"/>
				<input type="text" name="descricao" id="descricao" placeholder="Descrição" required="true"/>
				<div class="botoes">
					<button type="submit">Salvar</button>
					<button type="reset" onclick="limpar()">Novo Grupo</button>
				</div>
			</form>

		</div>
		<div class="area_tabela_crud ">
			<table>
				<caption>Grupos</caption>
				<thead>
					<th>ID</th>
					<th>Grupo</th>
					<th>Descrição</th>
					<th>Ação</th>
				</thead>
				<tbody>
					<?php
						while($row = mysqli_fetch_array($rs)):
					?>
					<tr>
						<td rel="id"><?php echo (int) $row['id']?></td>
						<td rel="titulo"><?php echo htmlspecialchars($row['titulo'])?></td>
						<td rel="descricao"><?php echo htmlspecialchars($row['descricao'])?></td>
						<td>
							<img onclick="editar(event)" src="<?php echo $caminhoURL; ?>assets/editar.png" title="Editar Grupo">
							<img onclick="excluir(event)" src="<?php echo $caminhoURL; ?>assets/delete.png" title="Excluir Grupo">
						</td>
					</tr>
					<?php
						endwhile;
					?>
				</tbody>
			</table>
		</div>
    </div>
</div>
<script>
	function limpar(){
		$("#acao").val("1");
	}

	function editar(event){
		relacionar();
		$("#acao").val("2"); // Ação 2 = Editar
	}

	function excluir(event){
		var titulo = $(event.target).parent().parent().find("td[rel='titulo']").html();
		var r = confirm("Tem certeza que deseja excluir o grupo "+titulo+"?");

		if (r == true) {
			relacionar();
		    $("#acao").val("3"); // Ação 3 = Excluir
		    $("form#formulario").submit();
		} else {
		   //NADA
		}
	}

	function relacionar(){
		var pai = $(event.target).parent().parent();
		//relacionar
		$(pai).find("td").each(function(){
			var campo = $(this).attr("rel");
			//AdicionarValor
			$("form#formulario").find("#"+campo).val($(this).html());
		});
	}

	function visualizar(event){
		var pai = $(event.target).parent().parent();


	}

	$(".ajax").colorbox({width:"80%", height:"70%",className:"caixaBranca"});
</script>

<?php include "footer.php"; ?>
