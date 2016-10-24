<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/events.php';

$user->init();
$user->setup();

$events = new Events();
$events->run();

page_layout($events->getTitle('UE'), $events->getTemplate('events'));
