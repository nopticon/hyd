<?php

define('IN_NUCLEO', true);

if (!defined('ROOT')) {
	define('ROOT', './');
}

require_once(ROOT . 'interfase/common.php');

$user->init(false);
$user->setup();

_pre($_REQUEST);

echo '<br /><br />';

_pre($_COOKIE, true);

echo '<br /><br />';

_pre($user->cookie_data['u'] . '*');

echo '<br /><br />';

_pre($user->session_id . '*', true);

?>