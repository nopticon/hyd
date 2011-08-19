<?php
// -------------------------------------------------------------
// $Id: _acp.ban.php,v 1.0 2007/06/26 08:22:00 Psychopsia Exp $
//
// STARTED   : Tue Jun 22, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$username = request_var('username', '');
	if (empty($username))
	{
		fatal_error();
	}
	
	$username = get_username_base($username);
	
	$sql = "SELECT *
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username) . "'";
	$result = $db->sql_query($sql);
	
	if (!$userdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = 'SELECT *
		FROM _banlist
		WHERE ban_userid = ' . (int) $userdata['user_id'];
	$result = $db->sql_query($sql);
	
	if (!$ban = $db->sql_fetchrow($result))
	{
		$insert = array(
			'ban_userid' => (int) $userdata['user_id']
		);
		$sql = 'INSERT INTO _banlist' . $db->sql_build_array('INSERT', $insert);
		$db->sql_query($sql);
		
		$sql = 'DELETE FROM _sessions
			WHERE session_user_id = ' . (int) $userdata['user_id'];
		$db->sql_query($sql);
		
		echo 'El usuario ' . $userdata['username'] . ' fue bloqueado.';
	}
	$db->sql_freeresult($result);
}

?>
<html>
<head>
<title>Ban users</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Nombre de usuario: <input type="text" name="username" size="100" /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>