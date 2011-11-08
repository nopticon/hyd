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
define('IN_NUCLEO', true);
require_once('./interfase/common.php');

$sql = 'SELECT id, title
	FROM _events
	ORDER BY id';
$events = sql_rowset($sql);

foreach ($events as $row) {
	$sql = 'UPDATE _events SET event_alias = ?
		WHERE id = ?';
	sql_query(sql_filter($sql, friendly($row['title']), $row['id']));
	
	echo $row['id'] . ' > ' . friendly($row['title']) . '<br />';
}

?>