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

require('./interfase/comments.php');
$comments = new _comments();

$sql = 'SELECT *
	FROM _members_unread
	WHERE element = ' . UH_T . '
	GROUP BY item';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$sql2 = 'SELECT topic_id
		FROM _forum_topics
		WHERE topic_id = ' . (int) $row['item'];
	$result2 = $db->sql_query($sql2);
	
	if (!$row2 = $db->sql_fetchrow($result2))
	{
		$user->delete_all_unread(UH_T, $row['item']);
		echo $row['item'] . '<br />';
	}
	$db->sql_freeresult($result2);
}
$db->sql_freeresult($result);

die();

?>