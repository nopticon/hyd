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
	$forum_id = request_var('fid', 0);
	$forum_alias = request_var('falias', '');
	
	$sql = "UPDATE _forums
		SET forum_alias = '" . $forum_alias . "'
		WHERE forum_id = " . (int) $forum_id;
	$db->sql_query($sql);
	
	echo $forum_id . ' > ' . $forum_alias . '<br />';
}

?>
<html>
<head>
<title>Forum Alias</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
<select name="fid">
<?php

$sql = 'SELECT forum_id, forum_name
	FROM _forums
	ORDER BY forum_order';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['forum_id'] . '">' . $row['forum_name'] . '</option>';
}
$db->sql_freeresult($result);

?></select><br />


Alias: <input type="text" name="falias" size="100" /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>