<?php
// -------------------------------------------------------------
// $Id: _acp.cron_news.php,v 1.0 2007/07/02 21:11:00 Psychopsia Exp $
//
// STARTED   : Mon Jul 02, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------
define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init(false, true);

$d = getdate();
$start_1 = mktime(0, 0, 0, $d['mon'], ($d['mday'] - 7), $d['year']);
$start_2 = mktime(0, 0, 0, $d['mon'], ($d['mday'] - 14), $d['year']);

$sql = 'DELETE FROM _members_unread
	WHERE element = ' . UH_T . '
		AND datetime < ' . (int) $start_1 . '
		AND item NOT IN (
			SELECT topic_id
			FROM _forum_topics
			WHERE topic_announce = 1
		)';
$db->sql_query($sql);

$sql = 'DELETE FROM _members_unread
	WHERE element = ' . UH_N . '
		AND datetime < ' . (int) $start_2;
$db->sql_query($sql);

_die('Done.');

?>