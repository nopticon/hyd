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
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$team = request_var('team', 0);
	$username = request_var('username', '');
	$username = get_username_base($username);
	$realname = request_var('realname', '');
	$ismod = request_var('ismod', 0);
	
	$sql = 'SELECT *
		FROM _team
		WHERE team_id = ' . (int) $team;
	$result = $db->sql_query($sql);
	
	if (!$teamd = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = "SELECT user_id, username
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username) . "'";
	$result = $db->sql_query($sql);
	
	$userdata = array();
	if (!$userdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = 'SELECT *
		FROM _team_members
		WHERE team_id = ' . (int) $team . '
			AND member_id = ' . (int) $userdata['user_id'];
	$result = $db->sql_query($sql);
	
	$insert = true;
	if ($row = $db->sql_fetchrow($result))
	{
		if ($ismod && !$row['member_mod'])
		{
			$sql = 'UPDATE _team_members SET member_mod = 1
				WHERE team_id = ' . (int) $team . '
					AND member_id = ' . (int) $userdata['user_id'];
			$db->sql_query($sql);
		}
		$insert = false;
	}
	$db->sql_freeresult($result);
	
	if ($insert)
	{
		$insert = array(
			'team_id' => $team,
			'member_id' => $userdata['user_id'],
			'real_name' => $realname,
			'member_mod' => $ismod
		);
		$sql = 'INSERT INTO _team_members' . $db->sql_build_array('INSERT', $insert);
		$db->sql_query($sql);
	}
	
	$cache->delete('team', 'team_all', 'team_members', 'team_mod', 'team_radio', 'team_colab');
	
	echo 'El usuario <strong>' . $userdata['username'] . '</strong> fue agregado al grupo <strong>' . $teamd['team_name'] . '</strong>.';
}

?>

<form action="<?php echo $u; ?>" method="post">
Equipo: <select name="team">
<?php

$sql = 'SELECT *
	FROM _team
	ORDER BY team_name';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['team_id'] . '">' . $row['team_name'] . '</option>';
}
$db->sql_freeresult($result);

?>
</select><br />
Usuario: <input type="text" name="username" value="" /><br />
Nombre real: <input type="text" name="realname" value="" /><br />
Moderador: <input type="checkbox" name="ismod" value="1" /><br />
<input type="submit" name="submit" value="Agregar usuario" />
</form>