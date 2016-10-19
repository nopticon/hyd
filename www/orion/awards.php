<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/awards.php';

$user->init();
$user->setup();

$awards = new Awards();
$awards->run();

page_layout('AWARDS', 'awards');
