<?php
/*
<Orion, a web development framework for RK.>
Copyright (C) <2011>  <Orion>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('IN_NUCLEO')) exit;

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