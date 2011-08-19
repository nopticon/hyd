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

require('./interfase/comments.php');
$comments = new _comments();

$sql = 'SELECT *
	FROM _members_unread
	WHERE element = ' . UH_T . '
	GROUP BY item';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$sql2 = 'SELECT topic_id
		FROM _forum_topics
		WHERE topic_id = ' . (int) $row['item'];
	$result2 = $db->sql_query($sql2);
	
	if (!$row2 = $db->sql_fetchrow($result2))
	{
		$user->delete_all_unread(UH_T, $row['item']);
		echo $row['item'] . '<br />';
	}
	$db->sql_freeresult($result2);
}
$db->sql_freeresult($result);

die();

?>