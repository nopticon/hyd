<?php
namespace App;

require_once(ROOT . 'objects/events.php');

$events = new _events(true);
$events->_lastevent();
