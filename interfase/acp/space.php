<?php
// -------------------------------------------------------------
// $Id: hidden_eu.php,v 1.0 2006/05/24 00:00:00 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$sql = 'SELECT *
	FROM _forum_posts
	WHERE post_id = 125750';
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$a_post = str_replace("\r", '', $row['post_text']);
	
	$sql = "UPDATE _forum_posts
		SET post_text = '" . $db->sql_escape($a_post) . "'
		WHERE post_id = " . (int) $row['post_id'];
	$db->sql_query($sql);
}
$db->sql_freeresult($result);

?>