<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/news.php';

$user->init();
$user->setup();

$news = new _news();
$news->run();

page_layout($news->get_title('NEWS'), $news->get_template('news'));
