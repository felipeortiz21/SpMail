<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	session_start();

	include_once("libs/config.php");
	include_once("libs/db.php");
	if(isset($_SESSION["usuarioNome"])==null){
		header("location:".$caminhoURL."index.php");
		exit;
	}
	$grupo = (int) ($_REQUEST['grupo'] ?? 0);

	const CSV_TAMANHO_MAXIMO = 5 * 1024 * 1024; // 5MB

  	//Upload File
	if (isset($_FILES['arquivoCSV'])) {
		if($_FILES['arquivoCSV']['error'] !== UPLOAD_ERR_OK){
			die("<div class='resultado-import'><span class='falha'>Não foi possível receber o arquivo.</span></div>");
		}
		if($_FILES['arquivoCSV']['size'] > CSV_TAMANHO_MAXIMO){
			die("<div class='resultado-import'><span class='falha'>Arquivo maior que 5MB - divida em arquivos menores.</span></div>");
		}
		if (!is_uploaded_file($_FILES['arquivoCSV']['tmp_name'])) {
			die("<div class='resultado-import'><span class='falha'>Arquivo não pôde ser enviado ao servidor.</span></div>");
		}

		//Import uploaded file to Database
		$handle = fopen($_FILES['arquivoCSV']['tmp_name'], "r");

		$total = 0;
		$sucesso = 0;
		$repetidos = 0;
		$invalidos = 0;
		$falhas = 0;

		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			$email = trim($data[0] ?? '');
			$nome = trim($data[1] ?? '');
			$telefone = trim($data[2] ?? '');

			if($email === '' && $nome === '' && $telefone === ''){
				continue; // linha em branco - não conta nem como erro
			}
			$total++;

			if(filter_var($email, FILTER_VALIDATE_EMAIL) === false){
				$invalidos++;
				continue;
			}

			$strRes = dbQuery($con, "SELECT email, aut FROM contatos WHERE email=? AND grupo=? LIMIT 1", "si", $email, $grupo);
			if(mysqli_num_rows($strRes) == 0){
				$result = dbQuery($con, "INSERT INTO contatos(email,nome,telefone,grupo,aut) VALUES(?,?,?,?,1)", "sssi", $email, $nome, $telefone, $grupo);
				if($result){
					$sucesso ++;
				}else{
					$falhas++;
				}
			}else{
				$repetidos++;
			}
		}
		fclose($handle);

		echo "<div class='resultado-import'>";
		echo "<p><b>Importação finalizada</b> - {$total} linha(s) processada(s)</p>";
		echo "<p><span class='ok'>{$sucesso} importado(s) com sucesso</span></p>";
		if($repetidos > 0){
			echo "<p>{$repetidos} já cadastrado(s) neste grupo (ignorado(s))</p>";
		}
		if($invalidos > 0){
			echo "<p><span class='falha'>{$invalidos} com email em formato inválido (ignorado(s))</span></p>";
		}
		if($falhas > 0){
			echo "<p><span class='falha'>{$falhas} falharam ao gravar no banco</span></p>";
		}
		echo "</div>";
	}else {
		echo "<div class='resultado-import'><span class='falha'>Nenhum arquivo recebido.</span></div>";
	}
?>
