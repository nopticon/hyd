<?php
// -------------------------------------------------------------
// $Id: community.php,v 1.3 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');
require('./interfase/cover.php');
require('./interfase/allies.php');

$user->init();
$user->setup();

page_layout('ALLIES', 'allies_body', false, false);

?>