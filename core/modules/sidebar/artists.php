<?php
namespace App;

require_once(ROOT . 'objects/artists.php');

$artists = new _artists();
$artists->sidebar();
