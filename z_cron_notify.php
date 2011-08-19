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

$sql = 'DELETE FROM _members_unread
	WHERE element = ' . UH_T . '
		AND datetime < ' . (int) $start_1 . '
		AND item NOT IN (
			SELECT topic_id
			FROM _forum_topics
			WHERE topic_announce = 1
		)';
$db->sql_query($sql);

$sql = 'DELETE FROM _members_unread
	WHERE element = ' . UH_N . '
		AND datetime < ' . (int) $start_2;
$db->sql_query($sql);

_die('Done.');

?>