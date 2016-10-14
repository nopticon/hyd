<?php

if (!defined('IN_APP')) exit;

require_once(ROOT . 'objects/artists.php');

$artists = new _artists();
$artists->sidebar();
