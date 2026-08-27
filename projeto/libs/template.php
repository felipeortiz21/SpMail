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
?>
