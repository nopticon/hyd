<?php
// -------------------------------------------------------------
// $Id: artists.php,v 1.6 2006/02/06 08:01:47 Psychopsia Exp $
//
// STARTED   : Thr Aug 05, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

include('./interfase/artists.php');

$artists = new _artists();
$artists->a_sidebar();

?>