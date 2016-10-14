<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/community.php';

$user->init();
$user->setup();

$community = new community();
$community->run();

page_layout('COMMUNITY', 'community', false, false);
