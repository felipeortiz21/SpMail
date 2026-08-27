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
	include_once "libs/icones.php";
	include "functions.php";
	protegePaginaAdmin();
?>
<?php
	//MANIPULAR ITENS
	$existe = 	isset($_REQUEST["nome"]) &&
				isset($_REQUEST['email']);

	if($existe){
		$nome = trim($_REQUEST['nome']);
		$email = trim($_REQUEST['email']);
		$papel = ($_REQUEST['setores'] ?? '') === PAPEL_ADMIN_GERAL ? PAPEL_ADMIN_GERAL : '';
		$ativo = isset($_REQUEST['ativo']) ? 1 : 0;
		$temSenha = isset($_REQUEST['senha']) && $_REQUEST['senha'] !== '';
		$temSenhaEmail = isset($_REQUEST['senha_email']) && $_REQUEST['senha_email'] !== '';

		if($_REQUEST['acao'] == '2'){
			$campos = "nome = ?, email = ?, setores = ?, ativo = ?";
			$tipos = "sssi";
			$params = [$nome, $email, $papel, $ativo];

			if($temSenha){
				$campos .= ", senha = ?";
				$tipos .= "s";
				$params[] = password_hash($_REQUEST['senha'], PASSWORD_DEFAULT);
			}
			if($temSenhaEmail){
				$campos .= ", senha_email = ?";
				$tipos .= "s";
				$params[] = criptografarSenhaEmail($_REQUEST['senha_email']);
			}

			$tipos .= "i";
			$params[] = (int) $_REQUEST['id'];

			$rsSql = dbQuery($con, "UPDATE usuarios SET $campos WHERE id = ?", $tipos, ...$params);
			$msg = "Usuário ".htmlspecialchars($nome)." atualizado com sucesso";
		}else if($_REQUEST['acao'] == '3'){
			$rsSql = dbQuery($con, "DELETE FROM usuarios WHERE id = ?", "i", (int) $_REQUEST['id']);
			$msg = "Usuário ".htmlspecialchars($nome)." foi excluído";
		}
		else if($_REQUEST['acao'] == '1'){
			$hashSenha = password_hash($_REQUEST['senha'] ?? '', PASSWORD_DEFAULT);
			$senhaEmailCifrada = criptografarSenhaEmail($_REQUEST['senha_email'] ?? '');

			$rsSql = dbQuery(
				$con,
				"INSERT INTO usuarios VALUES(DEFAULT,?,?,?,?,?,?)",
				"sssssi",
				$nome, $email, $papel, $hashSenha, $senhaEmailCifrada, $ativo
			);
			$msg = "Usuário ".htmlspecialchars($nome)." foi inserido";
		}
	}
?>
<?php
	//SELECIONAR ITENS PARA PREENCHER A GRID
	$rs = dbQuery($con, "SELECT * FROM usuarios ORDER BY nome");
?>
<div class="wrap grupos">
	<!--Crud-->
	<h1>Cadastro de Usuários de Emails</h1>
	<?php
		if(isset($rsSql) && $rsSql && isset($_REQUEST["id"])){
			echo "<div class='alert wrap'>$msg</div><br/>";
		}
	?>
	<div class="area_crud">
	<div class="crud">
		<form method="post" action="#" id="formulario">
			<input type="hidden" name="acao" id="acao" value="1"  />
			<input type="text" name="id" id="id" placeholder="ID" />
			<input type="text" name="nome"  id="nome" placeholder="Nome do Usuário" required="true"/>
			<input type="text" name="email" id="email" placeholder="Email de Envio" required="true"/>
            <input type="password" name="senha" id="senha" placeholder="Senha para Login do Sistema" />
            <input type="password" name="senha_email" id="senha_email" placeholder="Senha de Envio do Email (SMTP)"/>
            <label><input type="checkbox" name="setores" id="setores" value="<?php echo PAPEL_ADMIN_GERAL; ?>"/> Administrador Geral (acesso a Configurações)</label>
            <label><input type="checkbox" name="ativo" id="ativo" value="1" checked/> Usuário ativo</label>
			<div class="botoes">
				<button type="submit">Gravar</button>
				<button type="reset" onclick="limpar()">Novo</button>
			</div>
		</form>
		<div class='alert'>Obs. A senha de envio é criptografada antes de ser salva no banco de dados.</div>
	</div>
	<div class="area_tabela_crud">
		<div class="tabela">
	<table>
		<caption>Emails</caption>
		<thead>
			<th>ID</th>
			<th>Usuarios</th>
			<th>Email</th>
			<th>Papel</th>
			<th>Ativo</th>
			<th>Acao</th>

		</thead>
		<tbody>
			<?php
				while($row = mysqli_fetch_array($rs)):
			?>
			<tr>
				<td rel="id"><?php echo (int) $row['id']?></td>
				<td rel="nome"><?php echo htmlspecialchars($row['nome'])?></td>
				<td rel="email"><?php echo htmlspecialchars($row['email'])?></td>
				<td rel="setores"><?php echo $row['setores'] === PAPEL_ADMIN_GERAL ? 'Administrador Geral' : 'Usuário'; ?></td>
				<td rel="ativo"><?php echo ((int) $row['ativo']) === 1 ? 'Sim' : 'Não'; ?></td>
				<td>
					<?php echo icone('editar', 'Editar conta de Usuário', '', 'onclick="editar(event)"'); ?>
					<?php echo icone('excluir', 'Excluir Conta de Usuário', 'icone-excluir', 'onclick="excluir('.(int) $row['id'].',\''.htmlspecialchars($row['nome'], ENT_QUOTES).'\')"'); ?>
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
</div>
<script>
	function limpar(){
		$("#acao").val("1");
	}

	function editar(event){
		relacionar(event);
		$("#acao").val("2"); // Ação 2 = Editar
	}

	function excluir(id,nome){
		var r = confirm("Tem certeza que deseja excluir o usuário "+nome+"?");

		if (r == true) {
			$("#id").val(id); // Ação 3 = Excluir
			$("#nome").val(nome);
		    $("#acao").val("3"); // Ação 3 = Excluir
		    $("form#formulario").submit();
		} else {
		   alert("O usuário "+ nome +"  NÃO foi excluído.");
		}
	}

	function relacionar(event){
		var pai = $(event.target).parent().parent();
		//relacionar
		$(pai).find("td").each(function(){
			var campo = $(this).attr("rel");
			if(campo == "setores"){
				$("#setores").prop("checked", $(this).text() == "Administrador Geral");
			}else if(campo == "ativo"){
				$("#ativo").prop("checked", $(this).text() == "Sim");
			}else{
				$("form#formulario").find("#"+campo).val($(this).html());
			}
		});

		$("input[type=password]").val("");
	}

	$(".ajax").colorbox({width:"80%", height:"70%",className:"caixaBranca"});
</script>

<?php include "footer.php"; ?>
