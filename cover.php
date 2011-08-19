<?php
// -------------------------------------------------------------
// $Id: cover.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

srand((double)microtime()*1000000);

require('./interfase/cover.php');
require('./interfase/artists.php');
require('./interfase/events.php');

$cover = new cover();
$artists = new _artists();
$events = new _events(true);

$cover->news();
$cover->banners();
$cover->board();
$cover->poll();
$cover->twitter();

$artists->get_data();
$artists->thumbnails();

$events->_nextevent();
$events->_lastevent();

page_layout('HOME', 'cover_body', false, false);

?>