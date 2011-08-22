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
define('ROOT', './../');

require_once('./../interfase/common.php');
require_once('./../interfase/community.php');

$user->init(false);
$user->setup();

$comm = new community();
$comm->recent_members();
$current_time = time();

//
// Online
//
$sql = 'SELECT u.user_id, u.username, u.username_base, u.user_type, u.user_hideuser, u.user_color, s.session_ip
	FROM _members u, _sessions s
	WHERE s.session_time >= ' . ($current_time - (5 * 60)) . '
		AND u.user_id = s.session_user_id
	ORDER BY u.username ASC, s.session_ip ASC';
$comm->online($sql, 'online', 'MEMBERS_ONLINE');

//
// Today Online
//
$minutes = date('is');
$timetoday = ($current_time - (60 * intval($minutes[0].$minutes[1])) - intval($minutes[2].$minutes[3])) - (3600 * $user->format_date($current_time, 'H'));

$sql = 'SELECT user_id, username, username_base, user_color, user_hideuser, user_type
	FROM _members
	WHERE user_type <> ' . USER_IGNORE . '
		AND user_lastvisit >= ' . $timetoday . '
		AND user_lastvisit < ' . ($timetoday + 86399) . ' 
	ORDER BY username';
$comm->online($sql, 'online', 'MEMBERS_TODAY', 'MEMBERS_VISIBLE');

$template->set_filenames(array(
	'body' => 'community_online.htm')
);
$template->pparse('body');

?>