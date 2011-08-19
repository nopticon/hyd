<?php
// -------------------------------------------------------------
// $Id: _mcc.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('all');

$sql = 'SELECT user_id, username, username_base, user_points
	FROM _members
	WHERE user_points <> 0
	ORDER BY user_points DESC, username';
$result = $db->sql_query($sql);

echo '<ul>';
while ($row = $db->sql_fetchrow($result))
{
	echo '<li><a href="' . s_link('m', $row['username_base']) . '">' . $row['username'] . '</a> - ' . $row['user_points'] . '</li>';
}
$db->sql_freeresult($result);

echo '</ul>';

$db->sql_close();
exit();

?>