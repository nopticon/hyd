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
	$t = request_var('news_id', 0);
	$f = request_var('cat_id', 0);
	
	if (!$f || !$t)
	{
		_die();
	}
	
	//
	$sql = 'SELECT *
		FROM _news
		WHERE news_id = ' . (int) $t;
	$result = $db->sql_query($sql);
	
	if (!$tdata = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'SELECT *
		FROM _news_cat
		WHERE cat_id = ' . (int) $f;
	$result = $db->sql_query($sql);
	
	if (!$fdata = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'UPDATE _news
		SET cat_id = ' . (int) $f . '
		WHERE news_id = ' . $t;
	$db->sql_query($sql);
		
	
	redirect(s_link('news', $t));
}


?>

<html>
<head>
<title>Move News</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
# Noticia: <input type="text" name="news_id" /><br /><br />
Categoria: <select name="cat_id">
<?php

$sql = 'SELECT cat_id, cat_name
	FROM _news_cat
	ORDER BY cat_id';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['cat_id'] . '">' . $row['cat_name'] . '</option>';
}
$db->sql_freeresult($result);

?>
</select>

<br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>