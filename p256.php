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
define('IN_APP', true);
require_once('./interfase/common.php');
require_once(ROOT . 'objects/board.php');

$user->init();
$user->setup();

$sql = 'SELECT user_id, username, user_password
	FROM _members
	WHERE user_id <> 1
	ORDER BY user_id';
$members = sql_rowset($sql);

foreach ($members as $row) {
	if (strlen($row['user_password']) == 128) {
		continue;
	}
	
	$_password = HashPassword($row['user_password'], true);
	
	$sql = 'UPDATE _members SET user_password = ?
		WHERE user_id = ?';
	sql_query(sql_filter($sql, $_password, $row['user_id']));
	
	echo $row['username'] . ' * ' . $_password . '<br />';
}

?>