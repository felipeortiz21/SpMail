<?php

		/*****************************
			SpMail
			Mantido por Spiral Soluções e Consultoria LTDA
			Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
			Distribuído sob Licença Mozilla Public License 2.0
			Contato: contato@spiralsolucoes.com
		******************************/

		include_once(__DIR__ . "/env.php");
		carregarEnv(__DIR__ . "/../.env");

		//Dados globais para configuração do sistema de emails.
		$currentURL = "";
		$pastaURL = "";
		$caminhoURL = "";
		$nomeEmpresa = "";

		//Caso use SMTP, coloque como true, caso contrário, usará a função mail nativa. O SMTP é provido pelo projeto PHPMAiler: https://github.com/PHPMailer/PHPMailer
		$usarSMTP = true;
		$charset = "UTF-8";
		$smtp = "";
		$porta = "";
		$seguranca = "";
		$autenticacao = true;

		$emailResposta = "";
		$nomeEmailResposta = "";

		$emailsHora = 0; //Valor aproximado, pois o resultado final vai ser convertido
		$emailsHoraNaoComercial = 0;
		$horarioComercial_ini = 0;
		$horarioComercial_fin = 0;
		$envioAtrasoMinimo = 2; // segundos - atraso aleatório mínimo entre um envio e outro
		$envioAtrasoMaximo = 5; // segundos - atraso aleatório máximo entre um envio e outro

		$dkimAtivo = false;
		$dkimDominio = "";
		$dkimSelector = "";
		$dkimChavePrivada = ""; // já descriptografada, pronta pra uso

		// Credenciais do banco - vêm do .env (gerado pelo instalador). Os valores
		// abaixo só são usados como fallback se o .env ainda não existir.
		$host	= envVar("DB_HOST", "localhost"); // IP do Banco
		$user 	= envVar("DB_USER", ""); // Usuário
		$pswd 	= envVar("DB_PASS", ""); // Senha
		$dbname	= envVar("DB_NAME", ""); // Banco
		$con 	= null; // Conexão


		$con = mysqli_connect($host, $user, $pswd);
		if (!$con) {
			die("Não foi possível conectar: " . mysqli_connect_error());
		}
		mysqli_select_db($con, $dbname);
		mysqli_set_charset($con, "utf8mb4");

		//Preencher Configurações Globais
		$rsConfig = mysqli_query($con, "SELECT * FROM config LIMIT 1");
		while($rConfig = mysqli_fetch_array($rsConfig)){
			//Dados globais para configuração do sistema de emails.
			$currentURL = rtrim($rConfig["url"], "/");
			$pastaLimpa = trim($rConfig["pasta"], "/");
			$pastaURL = $pastaLimpa !== "" ? "/".$pastaLimpa."/" : "/";
			$caminhoURL = $currentURL . $pastaURL;
			$nomeEmpresa = $rConfig["nome_empresa"];

			//Caso use SMTP, coloque como true, caso contrário, usará a função mail nativa. O SMTP é provido pelo projeto PHPMAiler: https://github.com/PHPMailer/PHPMailer
			$smtp = $rConfig["smtp"];
			$porta = $rConfig["porta"];
			$seguranca = $rConfig["seguranca"];
			$autenticacao = $rConfig["autenticacao"];

			$emailResposta = $rConfig["email_resposta"];
			$nomeEmailResposta = $rConfig["nome_email_resposta"];

			$emailsHora = $rConfig["emails_por_hora"]; //Valor aproximado, pois o resultado final vai ser convertido
			$emailsHoraNaoComercial = $rConfig["emails_por_hora_nao_comercial"];
			$horarioComercial_ini = $rConfig["horario_comercial_ini"];
			$horarioComercial_fin = $rConfig["horario_comercial_fin"];

			// Campos novos (podem não existir ainda em bancos antigos até rodar o ALTER TABLE)
			$envioAtrasoMinimo = (int) ($rConfig["envio_atraso_minimo_segundos"] ?? 2);
			$envioAtrasoMaximo = (int) ($rConfig["envio_atraso_maximo_segundos"] ?? 5);
			$dkimAtivo = !empty($rConfig["dkim_ativo"] ?? false);
			$dkimDominio = $rConfig["dkim_dominio"] ?? "";
			$dkimSelector = $rConfig["dkim_selector"] ?? "";
			$dkimChavePrivada = !empty($rConfig["dkim_chave_privada"]) ? descriptografarSegredo($rConfig["dkim_chave_privada"]) : "";
		}
	?>
