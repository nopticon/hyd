<?php
// -------------------------------------------------------------
// $Id: downloads.php,v 1.5 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Thr Dec 15, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$filename = request_var('filename', '');
if (empty($filename) || !preg_match('#([a-z0-9\.])#is', $filename))
{
	fatal_error();
}

$filepath = '../home/downloads/' . $filename;
if (!@file_exists($filepath))
{
	fatal_error();
}

$sql = "UPDATE _downloads
	SET download_count = download_count + 1
	WHERE download_filename = '" . $db->sql_escape($filename) . "'";
$result = $db->sql_query($sql);

if (!$db->sql_affectedrows())
{
	$insert = array(
		'download_filename' => $filename,
		'download_count' => 1
	);
	$sql = 'INSERT INTO _downloads' . $db->sql_build_array('INSERT', $insert);
	$db->sql_query($sql);
}
$db->sql_freeresult($result);

//
require('./interfase/downloads.php');
$downloads = new downloads();

$downloads->filename = $filename;
$downloads->filepath = substr($filepath, 3);
$downloads->dl_file();

?>