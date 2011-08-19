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
	$topic = request_var('topic', 0);
	$title = request_var('title', '');
	
	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $topic;
	$result = $db->sql_query($sql);
	
	$topicdata = array();
	if (!$topicdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = "UPDATE _forum_topics
		SET topic_title = '" . $db->sql_escape($title) . "'
		WHERE topic_id = " . (int) $topic;
	$db->sql_query($sql);
	
	echo 'El titulo del tema <strong>' . $topicdata['topic_title'] . '</strong> ha sido cambiado por <strong>' . $title . '</strong>.';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="topic" value="" size="5" />
<input type="text" name="title" value="" size="50" />
<input type="submit" name="submit" value="Cambiar titulo" />
</form>