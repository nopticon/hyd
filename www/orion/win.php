<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/win.php';

$user->init();
$user->setup();

$win = new Win();
$win->run();

page_layout($win->getTitle('WIN'), $win->getTemplate('win'));
