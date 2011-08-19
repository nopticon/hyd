<?php
// -------------------------------------------------------------
// $Id: community.php,v 1.3 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');
require('./interfase/cover.php');
require('./interfase/community.php');

$user->init();
$user->setup();

$cover = new cover();
$comm = new community();

//
// Team | Members
//
$cover->founders();
$comm->team();
$comm->recent_members();
$comm->vars();

//
// Online
//
$sql = 'SELECT u.user_id, u.username, u.username_base, u.user_type, u.user_hideuser, u.user_color, s.session_ip
	FROM _members u, _sessions s
	WHERE s.session_time >= ' . ($user->time - (5 * 60)) . '
		AND u.user_id = s.session_user_id
	ORDER BY u.username ASC, s.session_ip ASC';
$comm->online($sql, 'online', 'MEMBERS_ONLINE');

//
// Today Online
//
$minutes = date('is', time());
$timetoday = ($user->time - (60 * intval($minutes[0].$minutes[1])) - intval($minutes[2].$minutes[3])) - (3600 * $user->format_date($user->time, 'H'));

$sql = 'SELECT user_id, username, username_base, user_color, user_hideuser, user_type
	FROM _members
	WHERE user_type NOT IN (' . USER_IGNORE . ', ' . USER_INACTIVE . ')
		AND user_lastvisit >= ' . $timetoday . '
		AND user_lastvisit < ' . ($timetoday + 86399) . ' 
	ORDER BY username';
$comm->online($sql, 'online', 'MEMBERS_TODAY', 'MEMBERS_VISIBLE');

page_layout('COMMUNITY', 'community_body', false, false);

?>