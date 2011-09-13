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
	$password = request_var('password', '');
	
	$username = get_username_base($username);
	$password = user_password($password);
	
	$sql = 'SELECT user_id, username
		FROM _members
		WHERE username_base = ?';
	if (!$userdata = sql_fieldrow(sql_filter($sql, $username))) {
		exit;
	}
	
	$sql = 'UPDATE _members SET user_password = ?
		WHERE user_id = ?';
	sql_query(sql_filter($sql, $password, $userdata['user_id']));
	
	echo 'La contrase&ntilde;a de ' . $userdata['username'] . ' fue actualizada.';
	exit;
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="username" value="" />
<input type="password" name="password" value="" />
<input type="submit" name="submit" value="Cambiar" />
</form>