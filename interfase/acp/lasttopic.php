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

$sql = 'SELECT topic_id, topic_title, topic_views, topic_replies
	FROM _forum_topics
	WHERE forum_id  NOT IN (38)
	ORDER BY topic_time DESC
	LIMIT 100';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<div><a href="/topic/' . $row['topic_id'] . '/">' . $row['topic_title'] . '</a> (' . $row['topic_views'] . 'v, ' . $row['topic_replies'] . 'm)</div>';
}
$db->sql_freeresult($result);

?>