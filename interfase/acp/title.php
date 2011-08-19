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

$submit = isset($HTTP_POST_VARS['submit']);

// submission
if ($submit)
{
	$topic_id = request_var('topic_id', 0);
	
	if (!$topic_id)
	{
		die();
	}

	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $topic_id;
	$result = $db->sql_query($sql);
	
	if (!$data = $db->sql_fetchrow($result))
	{
		die('NO FROM TOPIC: ' . $sql);
	}
	$db->sql_freeresult($result);
	
	$title = ucfirst(strtolower($data['topic_title']));
	
	$sql = "UPDATE _forum_topics
		SET topic_title = '" . $db->sql_escape($title) . "'
		WHERE topic_id = " . (int) $topic_id;
	$db->sql_query($sql);
	
	echo $data['topic_title'] . '<br /><br />';
	echo $title . '<br /><br />';
}
/* */

?><html>
<head>
<title>Topic title case</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Tema a cambiar: <input type="text" name="topic_id" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>