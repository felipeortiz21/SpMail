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
	include_once "libs/template.php";
	protegePagina();

	date_default_timezone_set('America/Sao_Paulo');
	$dt = new DateTime();
	if($horarioComercial_ini != ""){
		$horaAtual = $dt->format('H');
		if($horaAtual >= $horarioComercial_ini && $horaAtual <= $horarioComercial_fin){
			$emailsHora = $emailsHoraNaoComercial;
		}
	}
?>

<?php
if(isset($_REQUEST["id_men"])){
	$id_mensagem = (int) $_REQUEST["id_men"];
}else{
	$rs = dbQuery($con, "SELECT id FROM mensagens ORDER BY id DESC LIMIT 1", "");
	$row = mysqli_fetch_array($rs);
	$id_mensagem = $row[0] ?? 0;
}
?>


<div class="area" id="loadArea">
	<div class="wrap so_tabela">
		<style>
			.tabela h2{
				font-size: 1em;
				font-weight: normal;
				text-align: center;
				border-bottom: 1px solid #255ED1;
			}

			.tabela .pie{
				margin: 0 auto;
			}

			.tabela .tabs{
				display: flex;
				justify-content: space-between;

				margin-bottom: 40px;
			}

			.tabela .tabs .form{
				width: 400px;
			}

			.tabela .tabs .area{
				overflow: hidden;
				overflow-y: scroll;
				width: calc(100% - 500px);
				max-height: 400px;
			}

			.tabela .tabs .vermelho{
				background-color: #FCE3E3;
			}
			.tabela .tabs .verde{
				background-color: #DAFFF3;
			}
			.tabela .tabs .amarelo{
				background-color: #F8FFDD;
			}
			.tabela .tabs .apenas{
				background-color: #C4DCFC;
			}

			.tabela .tabs tr{
				border-bottom: white 1px solid;
			}

			.relato{
				display: flex;
				justify-content: space-between;
			}

			.relato input[type=text]{
				width: 30%;
			}

			.relato  div{
				display: flex;
				justify-content: space-between;
			}

			.relato div label,.relato div input{
				width: auto;
				padding: 0;
				margin: 0;
			}

			.relato div label{
				margin-left: 5px;
			}

			.relato div input{
				margin-left: 1.5em;
				margin-top: 3.5px;
			}
		</style>
		<h1>Dashboard</h1>
		<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
		<?php
			$mRs = dbQuery($con, "SELECT * FROM mensagens WHERE id=?", "i", $id_mensagem);
			while($mRow = mysqli_fetch_array($mRs)):
		?>
			<div class="area_tabela">
				<div class="tabela">
					<h2><a target="_blank" href="emails/<?php echo htmlspecialchars($mRow['url'])?>.html" title="Visualizar Email"><?php echo (int) $mRow["id"]; ?> - <?php echo htmlspecialchars($mRow["assunto"]); ?><?php
					if($mRow['status'] == 0){
						$status = '<img src="'.$caminhoURL.'assets/alerta.png" title="Não Enviado"/>';
						$class = 'nao_enviado';
					}else if($mRow['status'] == 1){
						$status = '<img src="'.$caminhoURL.'assets/enviando.png" title="Ainda Enviando"/>';
						$class = 'enviando';
					}else if($mRow['status'] == 2){
						$status = '<img src="'.$caminhoURL.'assets/enviado.png" title="Emails Enviados Com Sucesso"/>';
						$class = 'enviado';
					}else if($mRow['status'] == 3){
						$status = '<img src="'.$caminhoURL.'assets/alerta.png" title="Erro ao Enviar"/>';
						$class = 'erro';
					}

					//Verifica se o Processo foi interrompido por algum motivo
					$processoInterrompido = false;
					if($mRow['status'] == 1){
						$agora = date('d-m-Y H:i:s');
						$segundos = $envioAtrasoMaximo; // maior atraso possível configurado

						$proxAtualizacao = strtotime($mRow['data_atualizacao'] . ' +'. ($segundos).' second');
						$proxAtualizacao = date('Y-m-d', $proxAtualizacao);

						if($proxAtualizacao < $agora){
							$processoInterrompido = true;
							$status = '<img src="'.$caminhoURL.'assets/alerta.png" title="Envio dos Interrompidos pelo Servidor. Clique em Continuar Envios para prosseguir."/>';
							$class = "erro";
						}
					}
					?></a></h2>
				    <script type="text/javascript">
					  google.charts.load('current', {'packages':['corechart']});
					  google.charts.setOnLoadCallback(drawChart<?php echo (int) $mRow["id"]; ?>);
					  function drawChart<?php echo (int) $mRow["id"]; ?>() {
						  <?php
						  	$tRs = dbQuery(
						  		$con,
						  		"SELECT
						  			(SELECT count(id) FROM restantes WHERE mensagem = ? AND enviado='1') as total,
						  			(SELECT count(DISTINCT contato) FROM cliques WHERE mensagem = ?) as clicados,
						  			(SELECT count(DISTINCT contato) FROM cliques WHERE link LIKE '%cancelamento%' AND mensagem = ?) as cancelados,
						  			(SELECT count(DISTINCT contato) FROM views WHERE mensagem = ?) as visualizados",
						  		"iiii",
						  		$mRow["id"], $mRow["id"], $mRow["id"], $mRow["id"]
						  	);
							while($tRow = mysqli_fetch_array($tRs)){
								$total = (int) $tRow["total"];
								$clicados = (int) $tRow["clicados"];
								$cancelados = (int) $tRow["cancelados"];
								$visualizados = (int) $tRow["visualizados"];
							}
						  	// "cliques" já inclui o clique no link de cancelar inscrição, então
						  	// $clicados e $cancelados não são mutuamente exclusivos - sem
						  	// descontar isso aqui, o cancelamento entrava duas vezes na conta e
						  	// podia gerar valor negativo (o Google Charts rejeita fatia negativa).
						  	$clicadosSemCancelamento = max(0, $clicados - $cancelados);
						  	$apenasVisualizados = max(0, $visualizados - $clicadosSemCancelamento - $cancelados);
						  	$ignorados = max(0, $total - $visualizados);
						  ?>
						var data = google.visualization.arrayToDataTable([
						  ['Total de Emails:', '<?php echo $total ?>'],
						  ['Apenas Visualizados',     <?php echo $apenasVisualizados; ?> ],
						  ['Cancelados',  <?php echo $cancelados ?>],
						  ['Ignorados',  <?php echo $ignorados ?>],
						  ['Clicados',      <?php echo $clicadosSemCancelamento ?>]
						]);

						var options = {
						  title: 'Total de Emails: <?php echo $total ?>'
						};

						var chart = new google.visualization.PieChart(document.getElementById('piechart<?php echo (int) $mRow["id"]; ?>'));

						chart.draw(data, options);
					  }
					</script>
					<div class="relato">
					<input type="text" id="input<?php echo (int) $mRow["id"]; ?>" onkeyup="buscar(<?php echo (int) $mRow["id"]; ?>,0)" placeholder="Busque por Email ou Domínio">
					<div class="opcoes">
						<div>
							<input type="radio" name="rr<?php echo (int) $mRow["id"]; ?>" id="check<?php echo (int) $mRow["id"]; ?>" value="Clicou :D" onclick="filtrar(<?php echo (int) $mRow["id"]; ?>,2,'')"/>
							<label for="check<?php echo (int) $mRow["id"]; ?>">Abriu o Site</label>
						</div>
						<div>
							<input type="radio" name="rr<?php echo (int) $mRow["id"]; ?>" id="check<?php echo (int) $mRow["id"]; ?>a" value="Cancelou" onclick="filtrar('<?php echo (int) $mRow["id"]; ?>',2,'a')"/>
							<label for="check<?php echo (int) $mRow["id"]; ?>a">Cancelou</label>
						</div>
						<div>
							<input type="radio" name="rr<?php echo (int) $mRow["id"]; ?>" id="check<?php echo (int) $mRow["id"]; ?>b" value="Abriu Web" onclick="filtrar('<?php echo (int) $mRow["id"]; ?>',2,'b')"/>
							<label for="check<?php echo (int) $mRow["id"]; ?>b">Não conseguiu abrir no Email</label>
						</div>
						<div>
							<input type="radio" name="rr<?php echo (int) $mRow["id"]; ?>" id="check<?php echo (int) $mRow["id"]; ?>x" value="Apenas Visualizado" onclick="filtrar('<?php echo (int) $mRow["id"]; ?>',2,'x')"/>
							<label for="check<?php echo (int) $mRow["id"]; ?>x">Apenas Viu</label>
						</div>
						<div>
							<input type="radio" name="rr<?php echo (int) $mRow["id"]; ?>" id="check<?php echo (int) $mRow["id"]; ?>c" value="" onclick="filtrar('<?php echo (int) $mRow["id"]; ?>',2,'c')"/>
							<label for="check<?php echo (int) $mRow["id"]; ?>c">Todos</label>
						</div>
						</div>
				</div>
					<div class="tabs">
					<div class="form">
						<div id="piechart<?php echo (int) $mRow["id"]; ?>" style="width: 500px; height: 435px;" class="pie"></div>
					</div>
					<div class="area">
						<table id="tabela<?php echo (int) $mRow["id"]; ?>">
							<thead>
								<th>Email</th>
								<th>Link</th>
								<th>Status</th>
								<th>Data/Hora</th>
							</thead>
							<tbody>
								<?php
									$cRs = dbQuery($con, "SELECT contato, MAX(link) as link, MAX(data_hora) as data_hora FROM cliques WHERE mensagem = ? GROUP BY contato ORDER BY link", "i", $mRow["id"]);
									while($cRow = mysqli_fetch_array($cRs)):

									$estilo = "";
									$status = "";
									if (strpos($cRow["link"], 'cancelamento.php') !== false) {
										$estilo = "vermelho";
										$status = "Cancelou";
										$cRow["link"] = "-";
									}
									else if (strpos($cRow["link"], '/emails/') !== false) {
										$estilo = "amarelo";
										$status = "Abriu Web";
										$cRow["link"] = "-";
									}else{
										$estilo = "verde";
										$status = "Clicou :D";
									}
								?>
								<tr class="<?php echo $estilo?>">
									<td><?php echo htmlspecialchars($cRow["contato"]);?></td>
									<td><?php echo htmlspecialchars($cRow["link"]);?></td>
									<td><?php echo $status;?></td>
									<td><?php echo htmlspecialchars($cRow["data_hora"]);?></td>
								</tr>
								<?php endwhile; ?>
								<?php
									$cRs = dbQuery(
										$con,
										"SELECT v.contato as contato, MAX(v.data_hora) as data_hora FROM views v WHERE mensagem = ?
										 AND NOT EXISTS (SELECT 1 FROM cliques r WHERE v.contato = r.contato AND mensagem = ?)
										 GROUP BY v.contato",
										"ii",
										$mRow["id"], $mRow["id"]
									);
									while($cRow = mysqli_fetch_array($cRs)):
								?>
								<tr class="apenas">
									<td><?php echo htmlspecialchars($cRow["contato"]);?></td>
									<td> -- </td>
									<td>Apenas Visualizado</td>
									<td><?php echo htmlspecialchars($cRow["data_hora"]);?></td>
								</tr>
								<?php endwhile; ?>
								<?php
									$cRs = dbQuery(
										$con,
										"SELECT v.email as email FROM restantes v WHERE mensagem = ?
										 AND NOT EXISTS (SELECT 1 FROM cliques r WHERE v.email = r.contato AND mensagem = ?)
										 AND NOT EXISTS (SELECT 1 FROM views r WHERE v.email = r.contato AND mensagem = ?)
										 GROUP BY v.email",
										"iii",
										$mRow["id"], $mRow["id"], $mRow["id"]
									);
									while($cRow = mysqli_fetch_array($cRs)):
								?>
								<tr style="opacity: .85;">
									<td><?php echo htmlspecialchars($cRow["email"]);?></td>
									<td> -- </td>
									<td>Ignorados</td>
									<td> -- </td>
								</tr>
								<?php endwhile; ?>
							</tbody>
						</table>
					</div>
					</div>
				</div>
			</div>
		<?php
			endwhile;
		?>
	</div>
</div>
<script>
function buscar(id,col) {
  var input, filter, table, tr, td, i;
  input = document.getElementById("input"+id);
  filter = input.value.toUpperCase();
  table = document.getElementById("tabela"+id);
  tr = table.getElementsByTagName("tr");

  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[col];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
}

function filtrar(id,col,el) {
  var input, filter, table, tr, td, i;
  input = document.getElementById("check"+id+el);
  filter = input.value.toUpperCase();
  table = document.getElementById("tabela"+id);
  tr = table.getElementsByTagName("tr");

  for (i = 0; i < tr.length; i++) {
    td = tr[i].getElementsByTagName("td")[col];
    if (td) {
      if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = "";
      } else {
        tr[i].style.display = "none";
      }
    }
  }
}
</script>
<?php
	include "footer.php";
	?>
