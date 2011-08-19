<?php
// -------------------------------------------------------------
// $Id: merge.php,v 1.7 2006/08/24 02:34:54 Psychopsia Exp $
//
// STARTED   : Sat Nov 19, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

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