<?php
// -------------------------------------------------------------
// $Id: _mcc.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('all');

if ($submit)
{
	$username = request_var('username', '');
	$username = get_username_base($username);
	
	$sql = "SELECT user_id, username
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username) . "'";
	$result = $db->sql_query($sql);
	
	$userdata = array();
	if (!$userdata = $db->sql_fetchrow($result))
	{
		exit();
	}
	$db->sql_freeresult($result);
	
	$sql = "UPDATE _members
		SET user_color = '4D5358'
		WHERE user_id = " . (int) $userdata['user_id'];
	$db->sql_query($sql);
	
	//
	//
	//
	require('./interfase/comments.php');
	$comments = new _comments();
	
	$_conv = "Saludos %s,
	
	Tu color de usuario ha sido cambiado porque es muy claro para el color de fondo de la p&aacute;gina.
	Deber&aacute;s escoger uno m&aacute;s oscuro que sea legible.
	
	M&aacute;s informaci&oacute;n en: http://www.rockrepublik.net/help/57/
	
	Gracias.";
	$_conv = sprintf($_conv, $userdata['username']);
	
	$dc_id = $comments->store_dc('start', $userdata, $user->data, 'Rock Republik: Cambio de color de usuario', $_conv);
	
	echo 'El color de ' . $userdata['username'] . ' ha sido cambiado y fue notificado.';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="username" value="" />
<input type="submit" name="submit" value="Restaurar color" />
</form>