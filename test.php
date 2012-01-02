<?php

define('IN_NUCLEO', true);

if (!defined('ROOT')) {
	define('ROOT', './');
}

require_once(ROOT . 'interfase/common.php');

$user->init(false);
$user->setup();

_pre($_REQUEST);
_pre($_COOKIE, true);
_pre($user->cookie_data['u'] . '*');
_pre($user->session_id . '*', true);

?>