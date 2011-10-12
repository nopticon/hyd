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

// submission
if ($submit)
{
	$username = request_var('username', '');
	if (empty($username))
	{
		die();
	}
	
	$username = get_username_base($username);
	
	$sql = 'SELECT user_id
		FROM _members
		WHERE username_base = ?';
	if (!$row = sql_fieldrow(sql_filter($sql, $username))) {
		die();
	}
	
	$sql = 'DELETE FROM _members_unread
		WHERE user_id = ?
			AND element <> ?';
	sql_query(sql_filter($sql, $row['user_id'], 16));

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