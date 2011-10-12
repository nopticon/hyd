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
	$userid = request_var('uid', 0);
	$username = request_var('username', '');
	$email = request_var('email', '');
	if (empty($username) && empty($email) && !$userid)
	{
		fatal_error();
	}
	
	if (!empty($email)) {
		$sql = 'SELECT *
			FROM _members
			WHERE user_email = ?';
		$sql = sql_filter($sql, $email);
	} else if ($userid) {
		$sql = 'SELECT *
			FROM _members
			WHERE user_id = ?';
		$sql = sql_filter($sql, $userid);
	} else {
		$sql = 'SELECT *
			FROM _members
			WHERE username_base = ?';
		$sql = sql_filter($sql, get_username_base($username));
	}
	
	if (!$userdata = sql_fieldrow($sql)) {
		fatal_error();
	}
	
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