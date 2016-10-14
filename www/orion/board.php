<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/board.php';

$user->init();
$user->setup();

$board = new board();
$board->run();

page_layout('FORUM_INDEX', 'board');
