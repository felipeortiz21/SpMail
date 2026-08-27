<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	/**
	* Executa uma query preparada (protege contra SQL Injection).
	*
	* Uso:
	*   $rs = dbQuery($con, "SELECT * FROM contatos WHERE grupo = ? AND aut = ?", "ii", $grupo, $aut);
	*   while($row = mysqli_fetch_array($rs)){ ... } // mesma interface de sempre
	*
	* Para queries sem nenhum parâmetro (sem dado vindo de fora), pode-se
	* chamar sem $tipos/$params - nesse caso executa como query direta.
	*
	* @param mysqli $con
	* @param string $sql SQL com placeholders "?"
	* @param string $tipos String de tipos (i=int, d=double, s=string, b=blob), um por parâmetro
	* @param mixed ...$params Valores dos parâmetros, na mesma ordem dos "?"
	* @return mysqli_result|bool Resultado (SELECT) ou bool de sucesso (INSERT/UPDATE/DELETE)
	*/
	function dbQuery($con, $sql, $tipos = "", ...$params){
		if($tipos === ""){
			return mysqli_query($con, $sql);
		}

		$stmt = mysqli_prepare($con, $sql);
		if($stmt === false){
			return false;
		}

		mysqli_stmt_bind_param($stmt, $tipos, ...$params);
		mysqli_stmt_execute($stmt);

		$resultado = mysqli_stmt_get_result($stmt);
		// INSERT/UPDATE/DELETE não tem result set - devolve o sucesso da execução
		if($resultado === false && mysqli_stmt_error($stmt) === ''){
			$GLOBALS['dbUltimoInsertId'] = mysqli_stmt_insert_id($stmt);
			$GLOBALS['dbLinhasAfetadas'] = mysqli_stmt_affected_rows($stmt);
			mysqli_stmt_close($stmt);
			return true;
		}

		mysqli_stmt_close($stmt);
		return $resultado;
	}

	/**
	* Retorna o insert_id da última chamada dbQuery() que fez INSERT.
	* (mysqli_insert_id($con) também funciona normalmente, esta função existe
	* só como conveniência quando não se quer manter $con à mão.)
	*/
	function dbUltimoInsertId(){
		return $GLOBALS['dbUltimoInsertId'] ?? 0;
	}

	/**
	* Escapa um valor para uso seguro dentro de LIKE '%...%' quando usado
	* como parâmetro de prepared statement (protege contra abuso de curingas).
	*/
	function dbEscapaLike($valor){
		return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $valor);
	}
?>
