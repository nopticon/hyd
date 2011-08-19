<?php
// -------------------------------------------------------------
// $Id: events.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

require('./interfase/events.php');
$events = new _events();

if ($events->_setup())
{
	$events->view();
	
	$pagehtml = 'events_view';
	$page_title = $user->lang['UE'] . ' | ' . $events->data['title'];
}
else
{
	$events->home();
	
	$pagehtml = 'events_body';
	$page_title = 'UE';
}

page_layout($page_title, $pagehtml);

?>