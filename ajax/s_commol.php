<?php
// -------------------------------------------------------------
// $Id: s_commol.php,v 1.1 2006/03/23 00:04:37 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
define('ROOT', './../');
require('./../interfase/common.php');
require('./../interfase/community.php');

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