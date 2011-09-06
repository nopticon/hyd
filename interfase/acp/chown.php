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
	$username1 = request_var('username1', '');
	$username2 = request_var('username2', '');
	if (empty($username1) || empty($username2))
	{
		_die();
	}
	
	$username_base1 = get_username_base($username1);
	$username_base2 = get_username_base($username2);
	
	$sql = "SELECT *
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username_base1) . "'";
	$result = $db->sql_query($sql);
	
	if (!$userdata = $db->sql_fetchrow($result))
	{
		_die('El usuario no existe.');
	}
	$db->sql_freeresult($result);
	
	$sql = "SELECT *
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username_base2) . "'";
	$result = $db->sql_query($sql);
	
	if ($void = $db->sql_fetchrow($result))
	{
		_die('El usuario ya existe.');
	}
	$db->sql_freeresult($result);
	
	//
	$sql = "UPDATE _members
		SET username = '" . $db->sql_escape($username2) . "', username_base = '" . $db->sql_escape($username_base2) . "'
		WHERE user_id = " . (int) $userdata['user_id'];
	$db->sql_query($sql);
	
	require('./interfase/emailer.php');
	$emailer = new emailer();
	
	$emailer->from('info@rockrepublik.net');
	$emailer->use_template('username_change', $config['default_lang']);
	$emailer->email_address($userdata['user_email']);
	
	$emailer->assign_vars(array(
		'USERNAME' => $userdata['username'],
		'NEW_USERNAME' => $username2,
		'U_USERNAME' => s_link('m', $username_base2))
	);
	$emailer->send();
	$emailer->reset();
	
	redirect(s_link('m', $username_base2));
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="username1" value="" /><br />
<input type="text" name="username2" value="" /><br />
<input type="submit" name="submit" value="Cambiar" />
</form>