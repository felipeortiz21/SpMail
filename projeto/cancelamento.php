<!DOCTYPE>
<?php if(isset($_REQUEST['email']) && isset($_REQUEST['acao'])):?>
<?php
	/*****************************
		SpMail
		Mantido por Spiral Soluções e Consultoria LTDA
		Baseado no projeto PortilloMail, iniciado por Rodrigo Portillo em 2015
		Distribuído sob Licença Mozilla Public License 2.0
		Contato: contato@spiralsolucoes.com
	******************************/
	include "libs/conexao.php";        //Conexão com o banco de dados.
	include "libs/db.php";
	include "functions.php";
	dbQuery($con, "UPDATE contatos SET aut='0' WHERE email=?", "s", $_REQUEST['email']);
	?>
	<center>
		<?php echo htmlspecialchars($_REQUEST['email']); ?> retirado de nossa base.
	</center>
<?php else:?>
	<center>
		<h2>Deseja realmente remover o seu email de nossa base de dados?</h2>
		<form action="#" method="post">
			<input type="hidden" name="acao" value='1'/>
			<input type="email" name="email" value="<?php echo htmlspecialchars($_REQUEST['email'] ?? ''); ?>" />
			<button type="submit">Confirmar</button>
		</form>
	</center>
<?php endif;?>
