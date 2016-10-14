<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/events.php';

$user->init();
$user->setup();

$events = new _events();
$events->run();

page_layout($events->get_title('UE'), $events->get_template('events'));
