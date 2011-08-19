<?php
// -------------------------------------------------------------
// $Id: tos.php,v 1.2 2007/07/05 23:49:00 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

page_layout('PRIVACY_POLICY', 'privacy_body');

?>