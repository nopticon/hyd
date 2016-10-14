<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/home.php';
require_once ROOT . 'objects/artists.php';
require_once ROOT . 'objects/events.php';

$user->init();
$user->setup();

srand((double) microtime() * 1000000);

$home    = new _home();
$artists = new Artists();
$events  = new _events(true);

$home->news();
$home->board_general();
$home->board_events();
$home->poll();

$artists->getData();
$artists->thumbnails();

$events->_nextevent();

page_layout('HOME', 'home', false, false);
