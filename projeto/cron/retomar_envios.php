<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com

		Watchdog de envio: o disparo de cada campanha (enviar.php) processa um
		contato por vez e se auto-reagenda via um curl em segundo plano. Se
		esse elo da corrente falhar por qualquer motivo (rede, exec()
		bloqueado, processo do PHP-FPM morto, servidor reiniciado no meio),
		a campanha fica parada em "Enviando" pra sempre, exigindo alguém
		clicar manualmente em "Continuar Envios" no painel.

		Este script encontra campanhas nesse estado e retoma sozinho, sem
		depender de ninguém estar olhando o painel. Pensado pra rodar via
		cron a cada 2 minutos (ver o exemplo de crontab no README).

		Só roda via linha de comando (CLI) - não é acessível pelo navegador.
	******************************/
	// Exemplo de linha de crontab (fora deste comentário de bloco de propósito -
	// um "*/" dentro de um comentário /* */ fecharia o comentário sem querer):
	// */2 * * * * php /var/www/SpMail/projeto/cron/retomar_envios.php >/dev/null 2>&1

	if(php_sapi_name() !== 'cli'){
		http_response_code(403);
		die("Acesso restrito a linha de comando (cron).\n");
	}

	chdir(__DIR__ . '/..'); // pra includes relativos funcionarem igual as páginas web

	require_once 'libs/config.php';
	require_once 'libs/seguranca.php';
	require_once 'libs/db.php';
	require_once 'libs/template.php';

	date_default_timezone_set('America/Sao_Paulo');

	// Mesma margem usada no painel (emails.php) pra decidir se uma campanha
	// está "travada": o maior atraso possível configurado + folga.
	$margemSegundos = $envioAtrasoMaximo + 30;

	// A chamada de retomada é interna (o próprio servidor chamando a si
	// mesmo) - não pode depender da URL pública quando há port-forward
	// externo (VirtualBox NAT, Docker, etc), já que a porta externa só
	// existe "de fora pra dentro". Configurável via PORTA_INTERNA no .env;
	// se não definida, tenta a porta da própria URL pública (funciona
	// quando NÃO há port-forward).
	$portaInterna = envVar('PORTA_INTERNA', '');
	if($portaInterna === ''){
		$portaInterna = parse_url($caminhoURL, PHP_URL_PORT) ?: '80';
	}
	$baseInterna = "http://127.0.0.1:{$portaInterna}/";
	$pastaLimpaInterna = trim($pastaURL, '/');
	$baseInterna .= $pastaLimpaInterna !== '' ? $pastaLimpaInterna . '/' : '';

	$rs = dbQuery($con, "SELECT id, data_atualizacao FROM mensagens WHERE status = '1'", "");

	$retomadas = 0;
	$agora = time();

	while($row = mysqli_fetch_array($rs)){
		$proxAtualizacaoEsperada = strtotime($row['data_atualizacao'] . " +{$margemSegundos} second");

		if($proxAtualizacaoEsperada !== false && $proxAtualizacaoEsperada < $agora){
			$id = (int) $row['id'];

			// "Reivindica" a mensagem atualizando data_atualizacao antes de
			// disparar - reduz (não elimina 100%) a chance de duas execuções
			// do cron tentarem retomar a mesma mensagem ao mesmo tempo.
			dbQuery($con, "UPDATE mensagens SET data_atualizacao = ? WHERE id = ? AND status = '1'", "si", date('Y-m-d H:i:s'), $id);

			$local = $baseInterna . "enviar.php?id={$id}&acao=1&continuar=1&token=" . tokenContinuacaoEnvio($id);
			$local_escapado = escapeshellarg($local);
			exec("setsid nohup curl --request GET {$local_escapado} > /dev/null 2>&1 &");

			$retomadas++;
			echo "[" . date('Y-m-d H:i:s') . "] Retomando envio travado da mensagem #{$id}\n";
		}
	}

	if($retomadas === 0){
		echo "[" . date('Y-m-d H:i:s') . "] Nenhuma campanha travada encontrada.\n";
	}
?>
