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
	$msg_id = request_var('msg_id', 0);
	
	$sql = 'SELECT *
		FROM _members_posts
		WHERE post_id = ' . (int) $msg_id;
	$result = $db->sql_query($sql);
	
	if (!$d = $db->sql_fetchrow($result))
	{
		exit();
	}
	$db->sql_freeresult($result);
	
	$sql = 'DELETE FROM _members_posts
		WHERE post_id = ' . (int) $msg_id;
	$db->sql_query($sql);
	
	$sql = 'UPDATE _members
		SET userpage_posts = userpage_posts - 1
		WHERE user_id = ' . (int) $d['userpage_id'];
	$db->sql_query($sql);
	
	if (isset($_POST['user']))
	{
		$sql = 'SELECT ban_id
			FROM _banlist
			WHERE ban_userid = ' . (int) $d['poster_id'];
		$result = $db->sql_query($sql);
		
		if (!$row = $db->sql_fetchrow($result))
		{
			$sql = 'INSERT INTO _banlist (ban_userid) VALUES (' . (int) $d['poster_id'] . ')';
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);
	}
	
	if (isset($_POST['ip']))
	{
		$sql = "SELECT ban_id
			FROM _banlist
			WHERE ban_ip = '" . $db->sql_escape($d['post_ip']) . "'";
		$result = $db->sql_query($sql);
		
		if (!$row = $db->sql_fetchrow($result))
		{
			$sql = "INSERT INTO _banlist (ban_ip) VALUES ('" . $db->sql_escape($d['post_ip']) . "')";
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);
	}
	
	echo '<pre>';
	print_r($d);
	echo '</pre>';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="msg_id" value="" /><br /><br />
<strong>Bloquear:</strong><br />
<input type="checkbox" name="user" value="1" /> Usuario<br />
<input type="checkbox" name="ip" value="1" /> IP<br /><br />
<input type="submit" name="submit" value="Borrar" />
</form>