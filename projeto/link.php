<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	date_default_timezone_set('America/Sao_Paulo');
	include "libs/conexao.php";        //Conexão com o banco de dados.
	include "libs/db.php";
	include "functions.php";

	$destino = $caminhoURL; // fallback seguro caso o link não seja válido

	if(isset($_REQUEST["link"]) && isset($_REQUEST["email"]) && isset($_REQUEST["mensagem"])){
		$link = $_REQUEST["link"];
		$email = $_REQUEST["email"];
		$mensagem = $_REQUEST["mensagem"];
		$data_hora =  date("Y-m-d H:i:s");

		dbQuery($con, "INSERT INTO cliques VALUES(DEFAULT,?,?,?,?)", "ssss", $email, $mensagem, $data_hora, $link);

		$candidato = "http://" . $link;
		if(filter_var($candidato, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $candidato)){
			$destino = $candidato;
		}
	}

	header("location: " . $destino);
?>
