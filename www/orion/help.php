<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/help.php';

$user->init();
$user->setup();

$help = new _help();
$help->run();

page_layout($help->get_title('HELP'), $help->get_template('help'));
