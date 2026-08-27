<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/

	session_start();

	include_once("libs/config.php");
	if(!isset($_SESSION["usuarioNome"])){
		header("location:".$caminhoURL."index.php");
		exit;
	}

	// Extensões permitidas para upload de imagem (whitelist) - nunca confiar
	// na extensão enviada pelo cliente sem checar contra uma lista fechada,
	// para não permitir upload de um .php executável no servidor.
	$extensoesPermitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

	if(isset($_FILES['image'])){
		$image = $_FILES['image'];

		if($image['error'] !== UPLOAD_ERR_OK){
			die('Erro no upload.');
		}

		$partesNome = explode('.', $image['name']);
		$ext = strtolower(array_pop($partesNome));

		if(!in_array($ext, $extensoesPermitidas, true)){
			die('Tipo de arquivo não permitido. Envie apenas imagens (jpg, png, gif, webp).');
		}

		// Confirma que o conteúdo é realmente uma imagem, não só a extensão
		$infoImagem = @getimagesize($image['tmp_name']);
		if($infoImagem === false){
			die('Arquivo enviado não é uma imagem válida.');
		}

		$path = $_SERVER['DOCUMENT_ROOT'] . $pastaURL . 'images/';
		$tmpName = $image['tmp_name'];
		$name = hash("crc32b", str_replace(' ','-',$image['name'])) . '_' . bin2hex(random_bytes(4));
		move_uploaded_file($tmpName, $path . $name . '.' . $ext);
		$weburl = $caminhoURL.'images/'.$name.'.'.$ext;
		echo "<script>top.$('.mce-btn.mce-open').parent().find('.mce-textbox').val('". $weburl ."').closest('.mce-window').find('.mce-primary').click();</script>";
	}
?>
