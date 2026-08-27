<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	include "../libs/seguranca.php";
	include_once "../libs/db.php";
	include "../functions.php";
	protegePagina();

	$idGrupo = (int) $_REQUEST["id_grupo"];
?>
<?php
	$rs = dbQuery(
		$con,
		"SELECT cont.id,cont.email,cont.nome,cont.telefone,cont.grupo, g.titulo AS titulo_grupo FROM contatos cont LEFT JOIN grupos g ON g.id = cont.grupo WHERE cont.aut = 1 AND cont.grupo = ?",
		"i",
		$idGrupo
	);
?>
	<table>
		<caption>Contatos</caption>
		<thead>
			<th>ID</th>
			<th>Email</th>
			<th>Nome</th>
			<th>Telefone</th>
		</thead>
		<tbody>
			<?php
				while($row = mysqli_fetch_array($rs)):
			?>
			<tr>
				<td rel="id"><?php echo (int) $row['id']?></td>
				<td rel="email"><?php echo htmlspecialchars($row['email'])?></td>
				<td rel="nome"><?php echo htmlspecialchars($row['nome'])?></td>
				<td rel="telefone"><?php echo htmlspecialchars($row['telefone'])?></td>
			</tr>
			<?php
				endwhile;
			?>
		</tbody>
	</table>
