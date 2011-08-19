<?php
// -------------------------------------------------------------
// $Id: _acp.activate.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$sql = 'SELECT *
	FROM _forum_topics_nopoints
	ORDER BY exclude_topic';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$sql = 'UPDATE _forum_topics
		SET topic_points = 0
		WHERE topic_id = ' . (int) $row['exclude_topic'];
	$db->sql_query($sql);
	
	echo $row['exclude_topic'] . '<br />';
}
$db->sql_freeresult($result);

?>