<?php

if (!defined('IN_APP')) exit;

require_once(ROOT . 'objects/events.php');

$events = new _events(true);
$events->_lastevent();
