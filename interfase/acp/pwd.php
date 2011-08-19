<?php
// -------------------------------------------------------------
// $Id: pwd.php,v 1.5 2008/03/23 02:47:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$username = request_var('username', '');
	$password = request_var('password', '');
	
	$username = get_username_base($username);
	$password = user_password($password);
	
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
		SET user_password = '" . $db->sql_escape($password) . "'
		WHERE user_id = " . (int) $userdata['user_id'];
	$db->sql_query($sql);
	
	echo 'La contrase&ntilde;a de ' . $userdata['username'] . ' fue actualizada.';
	die();
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="username" value="" />
<input type="password" name="password" value="" />
<input type="submit" name="submit" value="Cambiar" />
</form>