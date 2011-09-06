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

$sql = 'SELECT u.item, t.topic_ub
	FROM _members_unread u, _forum_topics t
	WHERE u.element = ' . UH_N . '
		AND u.item = t.topic_id
	GROUP BY item';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$sql = 'DELETE FROM _members_unread
		WHERE element = ' . UH_N . '
			AND item = ' . (int) $row['item'] . '
			AND user_id NOT IN (SELECT user_id FROM _artists_fav WHERE ub = ' . $row['topic_ub'] . ')';
	$db->sql_query($sql);
	
	$a = $db->sql_affectedrows();
	$total += $a;
	
	flush();
	echo $sql . ' * ' . $a . '<br />';
	flush();
}
$db->sql_freeresult($result);

echo '<br /><br /><br />Total: ' . $total;
die();

/*
$sql = 'SELECT *
	FROM _members
	WHERE user_type NOT IN (' . USER_FOUNDER . ', ' . USER_IGNORE . ', ' . USER_INACTIVE . ')
	ORDER BY user_id';
$result = $db->sql_query($sql);

$total = 0;
while ($row = $db->sql_fetchrow($result))
{
	$sql = 'SELECT u.item, u.user_id, t.topic_ub
		FROM _members_unread u, _forum_topics t
		WHERE u.element = ' . UH_N . '
			AND u.item = t.topic_id
			AND u.user_id = ' . (int) $row['user_id'];
	$result2 = $db->sql_query($sql);
	
	while ($row2 = $db->sql_fetchrow($result2))
	{
		$sql = 'DELETE FROM _members_unread
			WHERE element = ' . UH_N . '
				AND item = ' . (int) $row2['item'] . '
				AND user_id NOT IN (SELECT user_id FROM _artists_fav WHERE ub = ' . $row2['topic_ub'] . ')';
		$db->sql_query($sql);
		
		$total++;
		
		flush();
		echo $sql . ' * ' . $db->sql_affectedrows() . '<br />';
		flush();
		
		$sql = 'SELECT user_id
			FROM _artists_fav
			WHERE user_id = ' . (int) $row2['user_id'] . '
				AND ub = ' . (int) $row2['topic_ub'];
		$result3 = $db->sql_query($sql);
		
		if (!$row3 = $db->sql_fetchrow($result3))
		{
			$sql = 'DELETE FROM _members_unread
				WHERE element = ' . UH_N . '
					AND item = ' . (int) $row2['item'] . '
					AND user_id = ' . (int) $row['user_id'];
			$db->sql_query($sql);
			$total++;
			
			flush();
			echo $sql . ' * ' . $db->sql_affectedrows() . '<br />';
			flush();
		}
		$db->sql_freeresult($result3);
		
	}
	$db->sql_freeresult($result2);
}
$db->sql_freeresult($result);

echo '<br /><br /><br />Total: ' . $total;
*/

?>