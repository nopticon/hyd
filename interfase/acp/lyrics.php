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
	$request = array('ub' => 0, 'title' => '', 'author' => '', 'text' => '');
	foreach ($request as $k => $v)
	{
		$request[$k] = request_var($k, $v);
	}
	
	$sql = 'SELECT *
		FROM _artists
		WHERE ub = ?';
	if (!$ad = sql_fieldrow(sql_filter($sql, $request['ub']))) {
		fatal_error();
	}
	
	$sql = 'INSERT INTO _artists_lyrics' . sql_build('INSERT', $request);
	sql_query($sql);
	
	$sql = 'UPDATE _artists SET lirics = lirics + 1
		WHERE ub = ?';
	sql_query(sql_filter($sql, $request['ub']));
	
	redirect(s_link('a', $ad['subdomain']));
}

?>

<form action="<?php echo $u; ?>" method="post">
Banda: <select name="ub"><?php

$sql = 'SELECT ub, name
	FROM _artists
	ORDER BY name';
$result = sql_rowset($sql);

foreach ($result as $row) {
	echo '<option value="' . $row['ub'] . '">' . $row['name'] . '</option>';
}

?></select><br />
T&iacute;tulo: <input type="text" name="title" value="" /><br />
Autor: <input type="text" name="author" value="" /><br />
Letra: <textarea name="text" cols="50" rows="20"></textarea><br />
<input type="submit" name="submit" value="Agregar Letra" />
</form>