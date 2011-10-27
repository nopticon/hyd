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
require_once(ROOT . 'interfase/common.php');

$user->init();
$user->setup();

$minutes = date('is', $current_time);
$hour_now = $current_time - (60 * ($minutes[0] . $minutes[1])) - ($minutes[2] . $minutes[3]);
$timetoday = $hour_now - (3600 * $user->format_date($current_time, 'H'));
$logged_visible_today = 0;
$logged_hidden_today = 0;

$sql = 'SELECT COUNT(DISTINCT session_ip) AS guests_today 
	FROM _sessions
	WHERE session_user_id = ' . GUEST . '
		AND session_time >= ' . $timetoday . '
		AND session_time < ' . ($timetoday + 86399);
$guests_today = sql_field(sql_filter($sql, $timetoday, ($timetoday + 86399)), 'guests_today', 0);

$sql = 'SELECT user_hideuser, COUNT(*) AS count
	FROM _members
	WHERE user_id <> ?
		AND user_session_time >= ?
		AND user_session_time < ?
	GROUP BY user_hideuser';
$reg_count = sql_rowset(sql_filter($sql, GUEST, $timetoday, ($timetoday + 86399)));

foreach ($reg_count as $row) {
	if (!$row['user_hideuser']) {
		$logged_visible_today = $row['count'];
	} else {
		$logged_hidden_today = $row['count'];
	}
}

$sql = 'UPDATE _site_stats
	SET reg = ?, hidden = ?, guests = ?
	WHERE date = ?';
sql_query(sql_filter($sql, $logged_visible_today, $logged_hidden_today, $guests_today, $hour_now));

if (!sql_affectedrows()) {
	$sql_insert = array(
		'date' => $hour_now,
		'reg' => $logged_visible_today,
		'hidden' => $logged_hidden_today,
		'guests' => $guests_today
	);
	
	$sql = 'INSERT IGNORE INTO _site_stats' . sql_build('INSERT', $sql_insert);
	sql_query($sql);
}

?>