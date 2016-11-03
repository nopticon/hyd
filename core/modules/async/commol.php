<?php
namespace App;

$user->setup();

$comm = new Community();
$comm->recent_members();
$current_time = time();

//
// Online
//
$sql = 'SELECT u.user_id, u.username, u.username_base, u.user_type, u.user_hideuser, s.session_ip
    FROM _members u, _sessions s
    WHERE s.session_time >= ??
        AND u.user_id = s.session_user_id
    ORDER BY u.username ASC, s.session_ip ASC';
$comm->online(sql_filter($sql, ($current_time - 300)), 'online', 'MEMBERS_ONLINE');

//
// Today Online
//
$minutes = date('is');

$minutes_1 = 60 * intval($minutes[0] . $minutes[1]);
$minutes_2 = intval($minutes[2] . $minutes[3]);
$minutes_3 = 3600 * $user->format_date($current_time, 'H');

$timetoday = ($current_time - $minutes_1 - $minutes_2) - $minutes_3;

$sql = 'SELECT user_id, username, username_base, user_hideuser, user_type
    FROM _members
    WHERE user_type <> ?
        AND user_lastvisit >= ?
        AND user_lastvisit < ?
    ORDER BY username';
$sql = sql_filter($sql, USER_INACTIVE, $timetoday, ($timetoday + 86399));

$comm->online($sql, 'online', 'MEMBERS_TODAY', 'MEMBERS_VISIBLE');

$template->set_filenames(
    array(
        'body' => 'community.online.htm'
    )
);
$template->pparse('body');
