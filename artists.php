<?php
// -------------------------------------------------------------
// $Id: artists.php,v 1.5 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

require('./interfase/artists.php');
$artists = new _artists();

if ($artists->_setup())
{
	include('./interfase/comments.php');
	$artists->msg = new _comments();
		
	$method = 'panel';
	$page_title = $artists->data['name'];
	$pagehtml = 'artists_panel';
}
else
{
	$method = 'list';
	$page_title = 'UB';
	$pagehtml = 'artists_body';
}

$artists->{'_' . $method}();
page_layout($page_title, $pagehtml, false, $artists->ajx);

?>