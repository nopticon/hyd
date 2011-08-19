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

//
// Banners
$banner_end = mktime(23, 59, 0, $d['mon'], $d['mday'], $d['year']);

$sql = 'SELECT *
	FROM _banners
	WHERE banner_end > ' . (int) $_end . '
	ORDER BY banner_end';
$result = $db->sql_query($sql);

$deleted = array();
while ($row = $db->sql_fetchrow($result))
{
	$deleted[] = $row['banner_id'];
}
$db->sql_freeresult($result);

if (count($deleted))
{
	$sql = 'DELETE FROM _banners
		WHERE banner_id IN (' . implode(',', $deleted) . ')';
	$db->sql_query($sql);
	
	$cache->delete('banners');
}

//
// Optimize
set_config('board_disable', 1);

$sql = 'SHOW TABLES';
$result = $db->sql_query($sql);

$tables = array();
while ($row = $db->sql_fetchrow($result))
{
	$tables[] = $row[0];
}
$db->sql_freeresult($result);

$sql = 'OPTIMIZE TABLE ' . implode(', ', $tables);
$db->sql_query($sql);

set_config('board_disable', 0);

_die('Done.');

?>