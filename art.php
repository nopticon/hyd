<?php
// -------------------------------------------------------------
// $Id: art.php,v 1.6 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

require('./interfase/art.php');
$art = new _art();

if ($art->_setup()) {
	$mode = request_var('mode', '');
	
	if (!in_array($mode, array('save', 'fav'))) {
		$mode = 'view';
		$pagehtml = 'art_view';
		$page_title = $user->lang['ART'] . ' | ' . $art->data['title'];
	}
} else {
	$mode = 'home';
	$pagehtml = 'art_body';
	$page_title = 'ART';
}

$art->$mode();
page_layout($page_title, $pagehtml);

?>