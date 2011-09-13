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
	$msg_id = request_var('msg_id', 0);
	
	$sql = 'SELECT *
		FROM _members_posts
		WHERE post_id = ?';
	if (!$d = sql_fieldrow(sql_filter($sql, $msg_id))) {
		exit;
	}
	
	$sql = 'DELETE FROM _members_posts
		WHERE post_id = ?';
	sql_query(sql_filter($sql, $msg_id));
	
	$sql = 'UPDATE _members SET userpage_posts = userpage_posts - 1
		WHERE user_id = ?';
	sql_query(sql_filter($sql, $d['userpage_id']));
	
	if (isset($_POST['user']))
	{
		$sql = 'SELECT ban_id
			FROM _banlist
			WHERE ban_userid = ?';
		if (!$row = sql_fieldrow(sql_filter($sql, $d['poster_id']))) {
			$sql_insert = array(
				'ban_userid' => $d['poster_id']
			);
			$sql = 'INSERT INTO _banlist' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
		}
	}
	
	if (isset($_POST['ip']))
	{
		$sql = 'SELECT ban_id
			FROM _banlist
			WHERE ban_ip = ?';
		if (!$row = sql_fieldrow(sql_filter($sql, $d['post_ip']))) {
			$sql_insert = array(
				'ban_ip' => $d['post_ip']
			);
			$sql = 'INSERT INTO _banlist' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
		}
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