<?php
// -------------------------------------------------------------
// $Id: events.php,v 1.0 2006/04/04 04:36:00 Psychopsia Exp $
//
// STARTED   : Tue Apr 04, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

include('./interfase/events.php');

$events = new _events(true);
$events->_lastevent();

?>