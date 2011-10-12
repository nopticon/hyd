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
	$name = request_var('name', '');
	
	$sql = 'SELECT *
		FROM _artists
		WHERE name = ?';
	if (!$a_data = sql_fieldrow(sql_filter($sql, $name))) {
		fatal_error();
	}
	
	$sql = 'SELECT v.*, u.user_id, u.username, u.username_base, u.user_color
		FROM _artists_fav v, _members u
		WHERE v.ub = ?
			AND v.user_id = u.user_id
		ORDER BY u.username';
	$result = sql_rowset(sql_filter($sql, $a_data['ub']));
	
	echo '<ul type="1">';
	
	foreach ($result as $row) {
		echo '<li>' . $row['username'] . '</li>';
	}
	
	echo '</ul>';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="name" value="" />
<input type="submit" name="submit" value="Consultar artista" />
</form>