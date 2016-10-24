<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/userpage.php';

$user->init();
$user->setup();

$userpage = new Userpage();
$userpage->run();

page_layout($userpage->getTitle(), $userpage->getTemplate());
