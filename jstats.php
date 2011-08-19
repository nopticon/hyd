<?php
// -------------------------------------------------------------
// $Id: jstats.php,v 1.2 2006/02/16 04:47:53 Psychopsia Exp $
//
// STARTED   : Wed Dec 21, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
define('IN_NUCLEO', true);
require('./interfase/common.php');

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
$result = $db->sql_query($sql);

$guest_count = $db->sql_fetchrow($result);
$db->sql_freeresult($result);

$sql = 'SELECT user_hideuser, COUNT(*) AS count
	FROM _members
	WHERE user_id <> ' . GUEST . '
		AND user_session_time >= ' . $timetoday . '
		AND user_session_time < ' . ( $timetoday + 86399 ) . '
	GROUP BY user_hideuser';
$result = $db->sql_query($sql);

while ($reg_count = $db->sql_fetchrow ($result))
{
	if (!$reg_count['user_hideuser'])
	{
		$logged_visible_today = $reg_count['count'];
	}
	else
	{
		$logged_hidden_today = $reg_count['count'];
	}
}
$db->sql_freeresult($result);

$sql = "UPDATE _site_stats
	SET reg = " . intval($logged_visible_today) . ", hidden = " . intval($logged_hidden_today) . ", guests = " . intval($guest_count['guests_today']) . "
	WHERE date = " . intval($hour_now);
$db->sql_query($sql);

if (!$db->sql_affectedrows())
{
	$db->sql_query("INSERT IGNORE INTO _site_stats (date, reg, hidden, guests) VALUES ('" . $hour_now . "', '" . $logged_visible_today . "', '" . $logged_hidden_today . "', '" . $guest_count['guests_today'] . "')");
}

?>