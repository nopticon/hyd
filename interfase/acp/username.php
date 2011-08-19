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
	$userid = request_var('uid', 0);
	$username = request_var('username', '');
	$email = request_var('email', '');
	if (empty($username) && empty($email) && !$userid)
	{
		fatal_error();
	}
	
	if (!empty($email))
	{
		$sql = "SELECT *
			FROM _members
			WHERE user_email = '" . $db->sql_escape($email) . "'";
	}
	else if ($userid)
	{
		$sql = 'SELECT *
			FROM _members
			WHERE user_id = ' . (int) $userid;
	}
	else
	{
		$username = get_username_base($username);
		
		$sql = "SELECT *
			FROM _members
			WHERE username_base = '" . $db->sql_escape($username) . "'";
	}
	
	$result = $db->sql_query($sql);
	
	if (!$userdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	foreach ($userdata as $k => $void)
	{
		if (preg_match('#\d+#is', $k))
		{
			unset($userdata[$k]);
		}
	}
	
	echo '<pre>';
	print_r($userdata);
	echo '</pre>';
}

?>
<html>
<head>
<title>Query users</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
UID: <input type="text" name="uid" size="8" /><br />
Nombre de usuario: <input type="text" name="username" size="100" /><br />
Email: <input type="text" name="email" size="100" /><br />
<input type="submit" name="submit" value="Consultar" />
</form>
</body>
</html>