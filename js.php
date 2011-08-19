<?php
// -------------------------------------------------------------
// $Id: css.php,v 1.0 2008/01/07 02:21:00 Psychopsia Exp $
//
// STARTED   : Mon Jan 07, 2007
// COPYRIGHT : 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init(false);
$user->setup();

function etag($filename, $quote = true)
{
	if (!file_exists($filename) || !($info = stat($filename)))
	{
		return false;
	}
	$q = ($quote) ? '"' : '';
	return sprintf("$q%x-%x-%x$q", $info['ino'], $info['size'], $info['mtime']);
}

$filename = request_var('filename', '');
if (empty($filename) || !preg_match('#[a-z\_]+#i', $filename))
{
	fatal_error();
}

$filepath = './template/js/' . $filename . '.js';
if (!@file_exists($filepath))
{
	fatal_error();
}

// 304 Not modified response header
$last_modified = filemtime($filepath);
$f_last_modified = gmdate('D, d M Y H:i:s', $last_modified) . ' GMT';

$http_if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
$http_if_modified_since = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : '';

$etag_server = etag($filepath);
$etag_client = str_replace('-gzip', '', $http_if_none_match);

header('Last-Modified: ' . $f_last_modified);
header('ETag: ' . $etag_server);

if ($etag_client == $etag_server && $f_last_modified == $http_if_modified_since)
{
	header('HTTP/1.0 304 Not Modified');
	header('Content-Length: 0');
	exit;
}

require('./interfase/jsmin.php');

$is_firefox = (strstr($user->browser, 'Gecko')) ? true : false;
$is_ie = (strstr($user->browser, 'IE')) ? true : false;

if (strstr($user->browser, 'compatible') || $is_firefox)
{
	ob_start('ob_gzhandler');
}

// Headers
#header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
#header('Pragma: no-cache');
#header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60) . ' GMT');
header('Content-type: text/css; charset=utf-8');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + (60 * 60 * 24 * 30)) . ' GMT');

//$db->report(false);
$template->replace_vars = false;

$template->assign_vars(array(
	'FF' => $is_firefox,
	'IE' => $is_ie)
);

$template->set_filenames(array('body' => 'js/' . $filename . '.js'));
$template->assign_var_from_handle('EXT', 'body');
//$template->pparse('body');

$db->sql_close();

$code = JSMin::minify($template->vars['EXT']);
echo $code;
exit();

?>
