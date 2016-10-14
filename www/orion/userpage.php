<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/userpage.php';

$user->init();
$user->setup();

$userpage = new userpage();
$userpage->run();

page_layout($userpage->get_title(), $userpage->get_template());
