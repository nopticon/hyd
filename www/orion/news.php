<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/news.php';

$user->init();
$user->setup();

$news = new News();
$news->run();

page_layout($news->getTitle('NEWS'), $news->getTemplate('news'));
