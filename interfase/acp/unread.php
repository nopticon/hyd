<?php
// -------------------------------------------------------------
// $Id: merge.php,v 1.7 2006/08/24 02:34:54 Psychopsia Exp $
//
// STARTED   : Sat Nov 19, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

// submission
if ($submit)
{
	$username = request_var('username', '');
	if (empty($username))
	{
		die();
	}
	
	$username = get_username_base($username);
	
	$sql = "SELECT user_id
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username) . "'";
	$result = $db->sql_query($sql);
	
	if (!$row = $db->sql_fetchrow($result))
	{
		die();
	}
	$db->sql_freeresult($result);
	
	$user_id = $row['user_id'];

	$sql = "DELETE FROM _members_unread
		WHERE user_id = " . (int) $user_id . '
			AND element <> 16';
	$db->sql_query($sql);

	echo 'Deleted';
}
/* */

?><html>
<head>
<title>Delete message center</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Usuario: <input type="text" name="username" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>