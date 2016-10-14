<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/topics.php';

$user->init();
$user->setup();

$topics = new topics();
$topics->run();

page_layout($topics->get_title(), $topics->get_template());
