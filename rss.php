<?php
// -------------------------------------------------------------
// $Id: rss.php,v 1.0 2007/06/30 21:26:00 Psychopsia Exp $
//
// STARTED   : Sat Jun 30, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$mode = request_var('mode', '');
if (empty($mode))
{
	fatal_error();
}

require('./interfase/rss.php');
$rss = new _rss();

$method = '_' . $mode;
if (!method_exists($rss, $method))
{
	fatal_error();
}

$rss->smode($mode);
$rss->$method();
$rss->output();
	
?>