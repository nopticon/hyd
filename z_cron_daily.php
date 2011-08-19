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
require('./interfase/common.php');

$user->init(false, true);

$d = getdate();
$start_1 = mktime(0, 0, 0, $d['mon'], ($d['mday'] - 7), $d['year']);
$start_2 = mktime(0, 0, 0, $d['mon'], ($d['mday'] - 14), $d['year']);

//
// Banners
$banner_end = mktime(23, 59, 0, $d['mon'], $d['mday'], $d['year']);

$sql = 'SELECT *
	FROM _banners
	WHERE banner_end > ' . (int) $_end . '
	ORDER BY banner_end';
$result = $db->sql_query($sql);

$deleted = array();
while ($row = $db->sql_fetchrow($result))
{
	$deleted[] = $row['banner_id'];
}
$db->sql_freeresult($result);

if (count($deleted))
{
	$sql = 'DELETE FROM _banners
		WHERE banner_id IN (' . implode(',', $deleted) . ')';
	$db->sql_query($sql);
	
	$cache->delete('banners');
}

//
// Optimize
set_config('board_disable', 1);

$sql = 'SHOW TABLES';
$result = $db->sql_query($sql);

$tables = array();
while ($row = $db->sql_fetchrow($result))
{
	$tables[] = $row[0];
}
$db->sql_freeresult($result);

$sql = 'OPTIMIZE TABLE ' . implode(', ', $tables);
$db->sql_query($sql);

set_config('board_disable', 0);

_die('Done.');

?>