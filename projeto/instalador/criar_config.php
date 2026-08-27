<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
ini_set('display_errors', 0);

if(is_file(__DIR__ . "/.instalado")){
	die("<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>Este SpMail já foi instalado. Por segurança, o instalador não roda de novo.</div>");
}

// Remove quebras de linha - um valor com quebra de linha quebraria o formato do .env
function linhaUnica($valor){
	return str_replace(["\r", "\n"], '', $valor);
}

$host = linhaUnica(trim($_REQUEST["host"] ?? ''));
$user = linhaUnica(trim($_REQUEST["user"] ?? ''));
$pswd = linhaUnica(trim($_REQUEST["pswd"] ?? ''));
$dbname = linhaUnica(trim($_REQUEST["dbname"] ?? ''));

//Testar conexão
$con = mysqli_connect($host, $user, $pswd);
if (!$con) {
	echo "<div style='background-color: #FFFF99;border: 2px solid #EFAD40;color: #5C5013;text-align: center;padding: .5em 1em;box-sizing: border-box;border-radius: 10px;margin: 0 auto; margin-top:10px;max-width:800px; width:80%;'>Não foi possível conectar. Por favor, verifique as configuraçoes para conexão ao Banco de Dados.</div>";
	include_once("index.php");
	exit;
}

mysqli_select_db($con, $dbname);
mysqli_set_charset($con, "utf8mb4");

// Preserva uma APP_KEY já existente (ex: gerada pelo install.sh) - só gera uma
// nova se ainda não houver nenhuma, para não invalidar segredos já cifrados.
$envAtual = @file_get_contents(__DIR__ . "/../.env");
$appKey = null;
if($envAtual !== false && preg_match('/^APP_KEY=(.+)$/m', $envAtual, $m)){
	$appKey = trim($m[1]);
}
if($appKey === null || $appKey === ''){
	$appKey = base64_encode(random_bytes(32));
}

$envConteudo = "DB_HOST={$host}\n";
$envConteudo .= "DB_USER={$user}\n";
$envConteudo .= "DB_PASS={$pswd}\n";
$envConteudo .= "DB_NAME={$dbname}\n";
$envConteudo .= "APP_KEY={$appKey}\n";

$fp = fopen(__DIR__ . '/../.env', 'w');
fwrite($fp, $envConteudo);
fclose($fp);
@chmod(__DIR__ . '/../.env', 0640);

// Importa o schema (arquivo estático, sem dado vindo de fora - seguro rodar direto)
$templine = '';
$lines = file(__DIR__ . "/modelo_banco.sql");

foreach ($lines as $line)
{
	if (substr(trim($line), 0, 2) == '--' || trim($line) == '')
		continue;

	$templine .= $line;

	if (substr(trim($line), -1, 1) == ';')
	{
		mysqli_query($con, $templine);
		$templine = '';
	}
}
echo('<META http-equiv="refresh" content="1;URL=passo2.php">');
?>
