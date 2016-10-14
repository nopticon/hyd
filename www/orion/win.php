<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/win.php';

$user->init();
$user->setup();

$win = new _win();
$win->run();

page_layout($win->get_title('WIN'), $win->get_template('win'));
