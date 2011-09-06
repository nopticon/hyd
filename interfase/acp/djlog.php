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

$sql = 'SELECT d.*, m.username, m.username_base
	FROM _radio_dj_log d, _members m
	WHERE d.log_uid = m.user_id
	ORDER BY log_time DESC';
$result = $db->sql_query($sql);

echo '<ul>';

while ($row = $db->sql_fetchrow($result))
{
	echo '<li><a href="' . s_link('m', $row['username_base']) . '">' . $row['username'] . '</a> - ' . $user->format_date($row['log_time']) . '</li>';
}
$db->sql_freeresult($result);

echo '</ul>';

?>