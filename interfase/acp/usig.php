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
		SET user_sig = ''
		WHERE user_id = " . (int) $userdata['user_id'];
	$db->sql_query($sql);
	
	echo 'La firma de ' . $userdata['username'] . ' ha sido borrada.';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="username" value="" />
<input type="submit" name="submit" value="Borrar firma" />
</form>