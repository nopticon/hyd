<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/help.php';

$user->init();
$user->setup();

$help = new Help();
$help->run();

page_layout($help->getTitle('HELP'), $help->getTemplate('help'));
