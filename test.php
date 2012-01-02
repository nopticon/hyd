<?php

define('IN_NUCLEO', true);

if (!defined('ROOT')) {
	define('ROOT', './');
}

require_once(ROOT . 'interfase/common.php');

$user->init(false);
$user->setup();

$a = @is_readable($config['news_path']);

var_dump($a);

echo '&&&&&&&<br />';

var_dump(REQC);
echo '<br />';

_pre($user->ip);
_pre($_SERVER['HTTP_X_FORWARDED_FOR']);

echo '<br /><br />';

_pre($_REQUEST);

echo '<br /><br />';

_pre($_COOKIE);

echo '<br /><br />';

_pre($user->cookie_data['u'] . '*');

echo '<br /><br />';

_pre($user->session_id . '*');

echo '<br /><br />';

_pre($user->ip . '*');
_pre($user->page);

echo '<br /><br />';

_pre($user->data);

echo '<br /><br />';

_pre($_SERVER, true);



?>