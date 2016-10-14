<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/topic.php';

$user->init();
$user->setup();

$topic = new topic();
$topic->run();

page_layout($topic->get_title(), $topic->get_template());
