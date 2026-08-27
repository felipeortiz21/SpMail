<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

include_once("config.php");

$con = mysqli_connect($host, $user, $pswd);
if (!$con) {
    die('Não foi possível conectar: ' . mysqli_connect_error());
}
mysqli_select_db($con, $dbname);
mysqli_set_charset($con, "utf8mb4");
?>
