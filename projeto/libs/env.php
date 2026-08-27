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
?>
