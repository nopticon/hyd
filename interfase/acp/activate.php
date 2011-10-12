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

$user_id = request_var('uid', 0);

if ($submit || $user_id)
{
	$username = request_var('username', '');
	$user_email = request_var('user_email', '');
	
	if ($user_id) {
		$sql = 'SELECT *
			FROM _members
			WHERE user_id = ';
		$sql = sql_filter($sql, $user_id);
	} else if (!empty($username)) {
		$username = get_username_base($username);
		
		$sql = 'SELECT *
			FROM _members
			WHERE username_base = ?';
		$sql = sql_filter($sql, $username);
	} else {
		$sql = 'SELECT *
			FROM _members
			WHERE user_email = ?';
		$sql = sql_filter($sql, $user_email);
	}
	
	if (!$userdata = sql_fieldrow($sql)) {
		exit;
	}
	
	//
	$user_id = $userdata['user_id'];
	
	$sql = 'UPDATE _members SET user_type = ?
		WHERE user_id = ?';
	sql_query(sql_filter($sql, USER_NORMAL, $user_id));
	
	$sql = 'DELETE FROM _crypt_confirm WHERE crypt_code = ?
			AND crypt_userid = ?';
	sql_query(sql_filter($sql, $code, $user_id));
	
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
$result = sql_rowset($sql);

foreach ($result as $row) {
	echo '<a href="/nucleo/acp.php?module=activate&amp;uid=' . $row['user_id'] . '">Activar</a> -- ' . $row['username'] . ' -- ' . $row['user_email'] . ' -- ' . $user->format_date($row['user_regdate']) . ' -- ' . $row['user_regip'] . '<br />';
}

?>