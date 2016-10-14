<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/awards.php';

$user->init();
$user->setup();

$awards = new _awards();
$awards->run();

page_layout('AWARDS', 'awards');
