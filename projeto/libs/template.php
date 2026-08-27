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
	* Sorteia o atraso (em segundos) antes do próximo envio, dentro da faixa
	* configurada manualmente em Configurações - evita um intervalo
	* perfeitamente constante entre envios (reconhecível como bot pelos
	* provedores de email). Sempre retorna um valor sensato mesmo se os
	* campos vierem zerados/invertidos (proteção contra configuração ruim).
	*
	* @param int $atrasoMinimo Segundos - menor atraso possível
	* @param int $atrasoMaximo Segundos - maior atraso possível
	* @return int Segundos a esperar antes do próximo envio, sempre >= 1
	*/
	function segundosAleatoriosEntreEnvios($atrasoMinimo, $atrasoMaximo){
		$min = max(1, (int) $atrasoMinimo);
		$max = max($min, (int) $atrasoMaximo);
		return mt_rand($min, $max);
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
