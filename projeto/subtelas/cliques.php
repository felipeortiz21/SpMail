<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	include "../libs/seguranca.php";
	include "../libs/db.php";
	include "../functions.php";
	protegePagina();

	$id = (int) $_REQUEST["mensagem"];
?>
<?php
	$rs = dbQuery($con, "SELECT * FROM cliques WHERE mensagem = ?", "i", $id);
?>
	<table>
		<caption>Contatos que Clicaram em Algum Link do Email</caption>
		<thead>
			<th>ID</th>
			<th>Email</th>
			<th>Link</th>
			<th>Horário</th>
		</thead>
		<tbody>
			<?php
				while($row = mysqli_fetch_array($rs)):
			?>
			<tr>
				<td rel="id"><?php echo (int) $row['id']?></td>
				<td rel="email"><?php echo htmlspecialchars($row['contato'])?></td>
				<td rel="link"><?php echo htmlspecialchars($row['link'])?></td>
				<td rel="horario"><?php echo date('d/m/Y H:i',strtotime($row['data_hora']))?></td>
			</tr>
			<?php
				endwhile;
			?>
		</tbody>
	</table>
