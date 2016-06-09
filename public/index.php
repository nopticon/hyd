<?php

define('IN_APP', true);
define('BASE', '../');
define('APP', BASE . 'app/');
define('ROOT', APP . 'orion/');
define('VENDOR', BASE . 'vendor/');

require VENDOR . 'autoload.php';
require VENDOR . 'illuminate/support/Illuminate/Support/helpers.php';
require ROOT . 'interfase/common.php';

$controllersDirectory = APP . 'controllers';
$modelsDirectory      = APP . 'models';

Illuminate\Support\ClassLoader::register();
Illuminate\Support\ClassLoader::addDirectories(array($controllersDirectory, $modelsDirectory));

$app = new Illuminate\Container\Container;
Illuminate\Support\Facades\Facade::setFacadeApplication($app);

$app['app'] = $app;
$app['env'] = 'production';

with(new Illuminate\Events\EventServiceProvider($app))->register();
with(new Illuminate\Routing\RoutingServiceProvider($app))->register();

require APP . 'routes.php';

$request = Illuminate\Http\Request::createFromGlobals();

try {
	$response = $app['router']->dispatch($request);
    $response->send();
} catch(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $notFound) {
	\Illuminate\Http\Response::create('Oops! this page does not exists', 404, [])->send();
}