<?php

require_once './interfase/common.php';
require_once ROOT . 'objects/acp.php';

$user->init();
$user->setup();

$acp = new _acp();
$acp->run();

page_layout($acp->get_title('ACP'), $acp->get_template('acp'));
