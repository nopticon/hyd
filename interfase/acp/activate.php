<?php
// -------------------------------------------------------------
// $Id: _acp.activate.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$user_id = request_var('uid', 0);

if ($submit || $user_id)
{
	$username = request_var('username', '');
	$user_email = request_var('user_email', '');
	
	if ($user_id)
	{
		$sql = "SELECT *
			FROM _members
			WHERE user_id = " . (int) $user_id;
	}
	else if (!empty($username))
	{
		$username = get_username_base($username);
		
		$sql = "SELECT *
			FROM _members
			WHERE username_base = '" . $db->sql_escape($username) . "'";
	}
	else
	{
		$sql = "SELECT *
			FROM _members
			WHERE user_email = '" . $db->sql_escape($user_email) . "'";
	}
	
	$result = $db->sql_query($sql);
	
	$userdata = array();
	if (!$userdata = $db->sql_fetchrow($result))
	{
		exit();
	}
	$db->sql_freeresult($result);
	
	//
	$user_id = $userdata['user_id'];
	
	$sql = 'UPDATE _members
		SET user_type = ' . USER_NORMAL . '
		WHERE user_id = ' . (int) $user_id;
	$db->sql_query($sql);
	
	$sql = "DELETE FROM _crypt_confirm
		WHERE crypt_code = '" . $db->sql_escape($code) . "'
			AND crypt_userid = " . (int) $user_id;
	$db->sql_query($sql);
	
	// Unread
	$user->save_unread(UH_T, 288, 0, $user_id);
	$user->save_unread(UH_T, 2524, 0, $user_id);
	$user->save_unread(UH_T, 1455, 0, $user_id);
	$user->save_unread(UH_U, $user_id);
	
	require('./interfase/emailer.php');
	$emailer = new emailer();
	
	$emailer->from('info@rockrepublik.net');
	$emailer->use_template('user_welcome_confirm');
	$emailer->email_address($userdata['user_email']);
	
	$emailer->assign_vars(array(
		'USERNAME' => $userdata['username'])
	);
	$emailer->send();
	$emailer->reset();
	
	echo 'La cuenta de <strong>' . $userdata['username'] . '</strong> ha sido activada.<br /><br />';
}

?>

<form action="<?php echo $u; ?>" method="post">
Nombre de usuario: <input type="text" name="username" value="" /><br />
Correo: <input type="test" name="user_email" value="" /><br />
<input type="submit" name="submit" value="Cambiar" />
</form>

<br /><br /><br />

<?php

$sql = 'SELECT *
	FROM _members
	WHERE user_type = 1
	ORDER BY username';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<a href="/nucleo/acp.php?module=activate&amp;uid=' . $row['user_id'] . '">Activar</a> -- ' . $row['username'] . ' -- ' . $row['user_email'] . ' -- ' . $user->format_date($row['user_regdate']) . ' -- ' . $row['user_regip'] . '<br />';
}

?>