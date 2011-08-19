<?php
// -------------------------------------------------------------
// $Id: mass_email.php,v 1.12 2006/11/02 00:20:00 Psychopsia Exp $
//
// STARTED   : Mon Oct 23, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$sql = 'SELECT topic_id, topic_title, topic_views, topic_replies
	FROM _forum_topics
	WHERE forum_id  NOT IN (38)
	ORDER BY topic_time DESC
	LIMIT 100';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<div><a href="/topic/' . $row['topic_id'] . '/">' . $row['topic_title'] . '</a> (' . $row['topic_views'] . 'v, ' . $row['topic_replies'] . 'm)</div>';
}
$db->sql_freeresult($result);

?>