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

$auth = array(16 => 'radio', 17 => 'mod');

$sql = 'SELECT *
	FROM _members_unread
	WHERE element = 8
	ORDER BY user_id, element, item';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$delete = false;
	
	$t = search_topic($row['item']);
	if ($t !== false)
	{
		if (in_array($t['forum_id'], array(16, 17)))
		{
			$a = $user->_team_auth($auth[$t['forum_id']], $row['user_id']);
			if (!$a)
			{
				$delete = true;
			}
		}
	}
	else
	{
		$delete = true;
	}
	
	if ($delete)
	{
		$sql = 'DELETE LOW_PRIORITY FROM _members_unread
			WHERE user_id = ' . (int) $row['user_id'] . '
				AND element = 8
				AND item = ' . (int) $row['item'];
		$db->sql_query($sql);
		
		echo $row['user_id'] . '-' . $sql . '<br />';
		flush();
	}
}
$db->sql_freeresult($result);


die();

//
function search_topic($topic_id)
{
	global $db;
	
	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $topic_id;
	$result = $db->sql_query($sql);
	
	$result = false;
	if ($row = $db->sql_fetchrow($result))
	{
		$result = $row;
	}
	$db->sql_freeresult($result);
	
	return $result;
}

?>