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

  	//Upload File
	if (isset($_FILES['arquivoCSV'])) {
		if (is_uploaded_file($_FILES['arquivoCSV']['tmp_name'])) {
			echo ("O arquivo foi enviado com sucesso.<br/>");
		}else{
			die ("Arquivo Não Pôde Ser Enviado ao Servidor.");
		}

		//Import uploaded file to Database
		$handle = fopen($_FILES['arquivoCSV']['tmp_name'], "r");

		$total = 0;
		$sucesso = 0;
		$erros = 0;
		$repetidos = 0;

		while (($data = fgetcsv($handle, 1000, ";")) !== FALSE) {
			$email = trim($data[0] ?? '');
			$nome = trim($data[1] ?? '');
			$telefone = trim($data[2] ?? '');

			if($email === ''){
				$erros++;
				$total++;
				continue;
			}

			$strRes = dbQuery($con, "SELECT email, aut FROM contatos WHERE email=? AND grupo=? LIMIT 1", "si", $email, $grupo);
			if(mysqli_num_rows($strRes) == 0){
				$result = dbQuery($con, "INSERT INTO contatos(email,nome,telefone,grupo,aut) VALUES(?,?,?,?,1)", "sssi", $email, $nome, $telefone, $grupo);
				if($result){
					$sucesso ++;
				}else{
					$erros++;
				}
			}else{
				$erros++;
				$repetidos++;
			}

			$total ++;
		}

		echo "Importação finalizada com:<br/> " ;
		if($sucesso > 0){
			echo $sucesso ." sucessos; <br/>";
		}
		if($erros > 0){
			echo $erros .  " erros";
			if($repetidos >0){
				echo ", sendo que " . $repetidos . " foram causados por emails repetidos";
			}
			echo ".<br/>";
		}

		fclose($handle);
		//view upload form
	}else {
		echo "Erro na Importação";
	}
?>
