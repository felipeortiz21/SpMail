<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com

		Observação: este arquivo continha um conjunto de funções auxiliares
		(incluir/atualizar/excluir/novoGrupo/novoUsuario/etc) baseadas em
		mysql_query(), API removida do PHP desde a versão 7 - nenhuma delas
		era chamada em nenhuma tela do sistema (confirmado por busca no
		código-fonte), então foram removidas. Mantida apenas criarSlug(),
		que é usada em confirma.php.
	******************************/

	function criarSlug($url)
	{
	    # Prep string with some basic normalization
	    $url = strtolower($url);
	    $url = strip_tags($url);
	    $url = stripslashes($url);
	    $url = html_entity_decode($url);

	    # Remove quotes (can't, etc.)
	    $url = str_replace('\'', '', $url);

	    # Replace non-alpha numeric with hyphens
	    $match = '/[^a-z0-9]+/';
	    $replace = '-';
	    $url = preg_replace($match, $replace, $url);

	    $url = trim($url, '-');

	    return $url;
	}
?>
