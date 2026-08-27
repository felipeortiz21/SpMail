<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	/**
	* Lê um arquivo .env simples (KEY=VALOR, uma por linha) e popula
	* getenv()/$_ENV com os valores encontrados. Não sobrescreve variáveis
	* de ambiente já definidas fora do arquivo (ex: por systemd/docker).
	*
	* Suporta comentários (linhas iniciadas com #), linhas em branco e
	* valores entre aspas simples ou duplas.
	*
	* @param string $caminho Caminho completo para o arquivo .env
	* @return bool true se o arquivo foi lido, false se não existe
	*/
	function carregarEnv($caminho){
		if(!is_file($caminho) || !is_readable($caminho)){
			return false;
		}

		$linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach($linhas as $linha){
			$linha = trim($linha);

			if($linha === '' || $linha[0] === '#'){
				continue;
			}

			if(strpos($linha, '=') === false){
				continue;
			}

			list($chave, $valor) = explode('=', $linha, 2);
			$chave = trim($chave);
			$valor = trim($valor);

			// Remove aspas envolvendo o valor, se houver
			if(strlen($valor) >= 2){
				$primeiro = $valor[0];
				$ultimo = $valor[strlen($valor) - 1];
				if(($primeiro === '"' && $ultimo === '"') || ($primeiro === "'" && $ultimo === "'")){
					$valor = substr($valor, 1, -1);
				}
			}

			// Não sobrescreve valores já definidos no ambiente real (systemd/docker/etc)
			if(getenv($chave) === false){
				putenv($chave . '=' . $valor);
				$_ENV[$chave] = $valor;
			}
		}

		return true;
	}

	/**
	* Lê uma variável de ambiente com valor padrão de fallback.
	*
	* @param string $chave
	* @param mixed $padrao
	* @return mixed
	*/
	function envVar($chave, $padrao = ''){
		$valor = getenv($chave);
		return ($valor === false || $valor === '') ? $padrao : $valor;
	}

	/**
	* Lê e decodifica a APP_KEY do ambiente (.env). Retorna null se ausente.
	*/
	function obterChaveApp(){
		$chaveBase64 = getenv('APP_KEY');
		if($chaveBase64 === false || $chaveBase64 === ''){
			return null;
		}

		$chave = base64_decode($chaveBase64, true);
		return ($chave === false || strlen($chave) < 32) ? null : $chave;
	}

	/**
	* Criptografa um segredo qualquer (senha SMTP, chave privada DKIM, etc) para
	* guardar no banco. Usa AES-256-GCM com a chave APP_KEY (definida no .env).
	* Se APP_KEY não estiver configurada, devolve o valor em texto plano
	* (comportamento antigo) e registra um aviso no log do servidor - não quebra
	* instalações que ainda não migraram para o .env.
	*
	* @param string $valorPlano
	* @return string Valor pronto para gravar no banco
	*/
	function criptografarSegredo($valorPlano){
		$chave = obterChaveApp();
		if($chave === null){
			trigger_error('APP_KEY não configurada - segredo será salvo sem criptografia. Configure APP_KEY no .env.', E_USER_WARNING);
			return $valorPlano;
		}

		if($valorPlano === ''){
			return '';
		}

		$iv = random_bytes(12);
		$tag = '';
		$cifrado = openssl_encrypt($valorPlano, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

		return base64_encode($iv . $tag . $cifrado);
	}

	/**
	* Descriptografa um segredo gravado com criptografarSegredo(). Se o valor
	* armazenado não for um pacote cifrado válido (ex: registro antigo em texto
	* plano, gravado antes desta versão), devolve o valor como está - migração
	* silenciosa.
	*
	* @param string $valorArmazenado
	* @return string Valor em texto plano
	*/
	function descriptografarSegredo($valorArmazenado){
		if($valorArmazenado === ''){
			return '';
		}

		$chave = obterChaveApp();
		if($chave === null){
			return $valorArmazenado;
		}

		$binario = base64_decode($valorArmazenado, true);
		// iv (12) + tag (16) + pelo menos 1 byte de conteúdo cifrado
		if($binario === false || strlen($binario) <= 28){
			return $valorArmazenado; // Não parece um pacote cifrado - trata como legado em texto plano
		}

		$iv = substr($binario, 0, 12);
		$tag = substr($binario, 12, 16);
		$cifrado = substr($binario, 28);

		$decifrado = openssl_decrypt($cifrado, 'aes-256-gcm', $chave, OPENSSL_RAW_DATA, $iv, $tag);

		// Falha de autenticação (chave errada ou não era realmente um pacote cifrado) -> assume texto plano legado
		return $decifrado === false ? $valorArmazenado : $decifrado;
	}

	/**
	* @deprecated use criptografarSegredo() - mantida para não quebrar chamadas existentes
	*/
	function criptografarSenhaEmail($senhaPlana){
		return criptografarSegredo($senhaPlana);
	}

	/**
	* @deprecated use descriptografarSegredo() - mantida para não quebrar chamadas existentes
	*/
	function descriptografarSenhaEmail($valorArmazenado){
		return descriptografarSegredo($valorArmazenado);
	}
?>
