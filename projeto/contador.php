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

if(isset($_REQUEST["email"]) && isset($_REQUEST["mensagem"])){
	$email = $_REQUEST["email"];
	$mensagem = $_REQUEST["mensagem"];
	$data_hora =  date("Y-m-d H:i:s");

	dbQuery($con, "INSERT INTO views VALUES(DEFAULT,?,?,?,'')", "sss", $email, $mensagem, $data_hora);
}


$file = 'assets/contador.jpg';
$type = 'image/jpeg';
header('Content-Type:'.$type);
header('Content-Length: ' . filesize($file));
readfile($file);

?>
