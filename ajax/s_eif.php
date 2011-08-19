<?php
// -------------------------------------------------------------
// $Id: s_coverf.php,v 1.1 2006/03/23 00:04:37 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
define('ROOT', './../');
require('./../interfase/common.php');

$user->init(false);
$user->setup();

$event_id = request_var('event_id', 0);
$image_id = request_var('image_id', 0);

$sql = 'SELECT *
	FROM _events_images
	WHERE event_id = ' . (int) $event_id . '
		AND image = ' . (int) $image_id;
$result = $db->sql_query($sql);

if (!$imaged = $db->sql_fetchrow($result))
{
	fatal_error();
}
$db->sql_freeresult($result);

$image_footer = request_var('image_footer', '', true);

$sql = "UPDATE _events_images
	SET image_footer = '" . $db->sql_escape($image_footer) . "'
	WHERE event_id = " . (int) $event_id . '
		AND image = ' . (int) $image_id;
$db->sql_query($sql);

echo $image_footer;

$db->sql_close();
die();

?>