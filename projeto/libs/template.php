<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	/**
	* Substitui as variáveis {nome}, {email} e {telefone} pelo dado real do
	* contato, tanto no assunto quanto no corpo do email.
	*
	* Quando o contato não tem nome cadastrado, o token {nome} é removido e o
	* espaço/pontuação órfã que sobrar ao redor é limpo (ex: "Olá {nome}!"
	* vira "Olá!" em vez de "Olá !").
	*
	* @param string $texto Assunto ou corpo do email, com os tokens {nome}/{email}/{telefone}
	* @param array $contato Array associativo com pelo menos as chaves 'nome', 'email', 'telefone'
	* @return string
	*/
	function substituirVariaveis($texto, $contato){
		$nome = isset($contato['nome']) ? trim($contato['nome']) : '';

		if($nome !== ''){
			$texto = str_replace('{nome}', $nome, $texto);
		}else{
			$texto = str_replace('{nome}', '', $texto);
			// Limpa a pontuação/espaço órfão que sobra quando o nome está vazio
			$texto = preg_replace('/[ \t]{2,}/', ' ', $texto);
			$texto = str_replace([' !', ' ,', ' .', ' ?'], ['!', ',', '.', '?'], $texto);
		}

		$texto = str_replace('{email}', $contato['email'] ?? '', $texto);
		$texto = str_replace('{telefone}', $contato['telefone'] ?? '', $texto);

		return $texto;
	}

	/**
	* Calcula o intervalo base (em segundos) entre um envio e outro, a partir
	* do limite de emails por hora configurado. Nunca divide por zero: um
	* valor <= 0 (ex: campo "emails por hora fora do horário comercial"
	* deixado em branco, que vira 0) cai para um valor de segurança em vez de
	* travar o PHP 8 com DivisionByZeroError.
	*
	* @param int $emailsPorHora
	* @return float Segundos entre um envio e o próximo
	*/
	function calcularSegundosEntreEnvios($emailsPorHora){
		$taxa = (int) $emailsPorHora;
		if($taxa <= 0){
			$taxa = 60; // segurança - equivale a 1 email/minuto
		}
		return 3600 / $taxa;
	}

	/**
	* Aplica uma variação aleatória (jitter) ao intervalo entre envios, pra
	* evitar um padrão perfeitamente constante (reconhecível como bot pelos
	* provedores de email). A média continua batendo com a taxa configurada,
	* já que a variação é simétrica em torno do valor base.
	*
	* @param float $segundosBase
	* @param int $variacaoPercentual Ex: 30 = varia entre 70% e 130% do valor base
	* @return int Segundos a esperar, sempre >= 1
	*/
	function aplicarVariacaoAleatoria($segundosBase, $variacaoPercentual){
		$variacao = max(0, (int) $variacaoPercentual) / 100;
		$fatorAleatorio = 1 + (mt_rand(-100, 100) / 100) * $variacao;
		return (int) max(1, round($segundosBase * $fatorAleatorio));
	}

	/**
	* Configura a assinatura DKIM num objeto PHPMailer, se estiver ativada e
	* com todos os dados preenchidos. A versão do PHPMailer usada aqui só
	* aceita a chave privada como um ARQUIVO em disco (não como string), então
	* a chave (já descriptografada) é escrita num arquivo temporário fora da
	* pasta pública do site, com permissão restrita.
	*
	* Quem chama esta função é responsável por apagar o arquivo temporário
	* retornado depois do Send() (com @unlink) - inclusive em caso de erro.
	*
	* @return string|null Caminho do arquivo temporário criado, ou null se o DKIM não foi configurado
	*/
	function configurarDkim($mail, $dkimAtivo, $dkimDominio, $dkimSelector, $dkimChavePrivada, $emailRemetente){
		if(!$dkimAtivo || $dkimDominio === '' || $dkimSelector === '' || $dkimChavePrivada === ''){
			return null;
		}

		$arquivoTemp = tempnam(sys_get_temp_dir(), 'spmail_dkim_');
		if($arquivoTemp === false){
			return null;
		}
		file_put_contents($arquivoTemp, $dkimChavePrivada);
		chmod($arquivoTemp, 0600);

		$mail->DKIM_domain = $dkimDominio;
		$mail->DKIM_selector = $dkimSelector;
		$mail->DKIM_private = $arquivoTemp;
		$mail->DKIM_passphrase = '';
		$mail->DKIM_identity = $emailRemetente;

		return $arquivoTemp;
	}
?>
