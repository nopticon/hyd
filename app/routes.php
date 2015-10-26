<?php

$app['router']->get('/', 'HomeController@start');

// 
// assets/filename.extension
// 
$app['router']->get('assets/{filename}.{extension}', 'AssetsController@deliver')->where([
	'filename' => '[0-9a-zA-Z\-\_]+',
	'extension' => 'css|js'
]);

// 
// today/(task|fault|attends)/id
// 
$app['router']->any('{controller}/{mode}/{id}', function($controller, $mode, $id) {
	$_REQUEST['mode'] = $mode;
	$_REQUEST['id'] = $id;

	$controllerName = ucfirst($controller) . 'Controller';
	$controller = new $controllerName;
	
	return $controller->start();
})->where([
	'controller' => 'today',
	'mode' => 'task|fault|attends',
	'id' => '\d+'
]);

// 
// m/(member)/(mode)
// 
$app['router']->any('m/{member}/{mode?}', function($member, $mode) {
	$_REQUEST['member'] = $member;
	$_REQUEST['mode'] = $mode;

	$controller = new UserpageController;
	return $controller->start();
})->where([
	'member' => '[a-z0-9\-]+',
	'mode' => 'main|friend|friends|messages|ban'
]);

// 
// my/(profile)
// 
$app['router']->any('my/profile', 'UserpageController@start');

// 
// my/dc/start/(member)
// 
$app['router']->any('my/dc/start/{member?}', function($member) {
	$_REQUEST['page'] = 'dc';
	$_REQUEST['action'] = 'start';
	$_REQUEST['member'] = $member;

	$controller = new UserpageController;
	return $controller->start();
})->where([
	'member' => '[0-9a-zA-Z\-\_]+'
]);

// 
// my/dc/(mode)/(id)
// 
$app['router']->any('my/dc/{mode}/{id}', function($mode, $id) {
	$_REQUEST['page'] = 'dc';
	$_REQUEST['mode'] = $mode;
	$_REQUEST['id'] = $id;
	
	$controller = new UserpageController;
	return $controller->start();
})->where([
	'mode' => '[0-9a-zA-Z\-\_]+',
	'id' => '\d+'
]);

// 
// my/dc/s(offset?)
// 
$app['router']->any('my/dc/{offset?}', function($offset = 0) {
	$_REQUEST['page'] = 'dc';
	$_REQUEST['offset'] = (int) str_replace('s', '', $offset);

	$controller = new UserpageController;
	return $controller->start();
})->where([
	'offset' => 's(\d+)'
]);

// 
// RewriteRule ^(events)/g(\d+)/$ orion/$1.php?gallery_offset=$2 [nc]
// 
$app['router']->any('events/{offset?}', function($offset) {
	$_REQUEST['offset'] = (int) str_replace('g', '', $offset);

	$controller = new EventsController;
	return $controller->start();
})->where([
	'offset' => 'g(\d+)'
]);

// 
// events/alias/id/reply
// 
$app['router'] ->any('events/{alias}/{id}/reply', function($alias, $id) {
	$_REQUEST['alias'] = $alias;
	$_REQUEST['p'] = $id;
	$_REQUEST['reply'] = 1;

	$controller = new EventsController;
	return $controller->start();
})->where([
	'alias' => '[0-9a-z\-]+',
	'id' => '\d+',
]);

// 
// events/alias/download_id/mode
// 
$app['router'] ->any('events/{alias}/{download_id}/{mode}', function($alias, $download_id, $mode) {
	$_REQUEST['alias'] = $alias;
	$_REQUEST['download_id'] = $download_id;
	$_REQUEST['mode'] = $mode;

	$controller = new EventsController;
	return $controller->start();
})->where([
	'alias' => '[0-9a-z\-]+',
	'download_id' => '\d+',
	'mode' => 'view|save|fav|rsvp',
]);

// 
// RewriteRule ^(events)/([0-9a-z\-]+)/?(s(\d+)/)?(ps(\d+)/)?$ orion/$1.php?alias=$2&offset=$4&ps=$6 [nc]
// 
$app['router'] ->any('events/{alias}/{offset?}/{ps?}', function($alias, $offset, $ps) {
	$_REQUEST['alias'] = $alias;
	$_REQUEST['offset'] = (int) str_replace('s', '', $offset);
	$_REQUEST['ps'] = (int) str_replace('ps', '', $ps);;

	$controller = new EventsController;
	return $controller->start();
})->where([
	'alias' => '[0-9a-z\-]+',
	'offset' => 's(\d+)',
	'ps' => 'ps(\d+)',
]);

// 
// news/alias/ps(offset?)
// 
$app['router']->any('news/{alias}/{offset?}', function($alias, $offset = 0) {
	$_REQUEST['alias'] = $alias;
	$_REQUEST['offset'] = (int) str_replace('ps', '', $offset);

	$controller = new NewsController;
	return $controller->start();
})->where([
	'alias' => '[0-9a-zA-Z\-\_]+',
	'offset' => 'ps(\d+)'
]);

// 
// (board|emoticons|comments|ssv|tos|acp|help|today|events|news)
// 
$app['router']->any('{controller}', function($controller) {
	$controllerName = ucfirst($controller) . 'Controller';

	if (@class_exists($controllerName)) {
		$controllerObject = new $controllerName;
	} else {
		$controllerObject = new GeneralController;
	}
	
	return $controllerObject->start($controller);
})->where([
	'controller' => 'board|emoticons|comments|ssv|tos|acp|help|today|events|news'
]);

// 
// forum/alias/s(offset)?
// 
$app['router']->any('forum/{alias}/{offset?}', function($alias, $offset = 0) {
	$_REQUEST['f'] = $alias;
	$_REQUEST['offset'] = (int) str_replace('s', '', $offset);

	$controller = new BoardController;
	return $controller->topics();
})->where([
	'alias' => '[a-z0-9\-]+',
	'offset' => 's(\d+)'
]);

// 
// topic/id/s(offset)?
// 
$app['router']->any('topic/{alias}/{offset?}', function($alias, $offset = 0) {
	$_REQUEST['t'] = $alias;
	$_REQUEST['offset'] = (int) str_replace('s', '', $offset);
	
	$controller = new BoardController;
	return $controller->topic();
})->where([
	'alias' => '[a-z0-9]+',
	'offset' => 's(\d+)'
]);

// 
// post/id/reply?
// 
$app['router']->get('post/{post_id}/{reply?}', function($post_id, $reply) {
	$_REQUEST['p'] = $post_id;

	if ($reply) $_REQUEST['reply'] = 1;

	$controller = new BoardController;
	
	return $controller->topic();
})->where([
	'post_id' => '\d+',
	'reply' => 'reply'
]);

// 
// sign(up|r)/code
// 
$app['router']->any('sign{mode}/{code}', function($mode, $code) {
	$_REQUEST['mode'] = $mode;
	$_REQUEST['code'] = $code;

	do_login();
})->where([
	'mode' => 'up|r',
	'code' => '[a-z0-9]+'
]);

// 
// sign(in|out|up|r)
// 
$app['router']->any('sign{mode}', function($mode) {
	$_REQUEST['mode'] = $mode;

	do_login();
})->where([
	'mode' => 'in|out|up|r'
]);

// 
// (acp|async|cron|news)/module/args
// 
$app['router']->get('{controller}/{module}/{args?}', function($controller, $module, $args) {
	$_REQUEST['module'] = $module;
	$_REQUEST['args'] = $args;


	$controllerName = ucfirst($controller) . 'Controller';
	$controller = new $controllerName;
	
	return $controller->start();
})->where([
	'controller' => 'acp|async|cron|news',
	'module' => '[a-z\_]+',
	'args' => '[0-9a-z\_\.\-\:]+'
]);