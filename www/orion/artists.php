<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/artists.php';

$user->init();
$user->setup();

$artists = new Artists();
$artists->run();

page_layout($artists->getTitle('UB'), $artists->getTemplate('artists'), false, $artists->ajax());
