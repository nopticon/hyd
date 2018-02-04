<?php

$sentryClient = new \Raven_Client($env_config['sentry'], [
    'tags' => [
        'php_version' => phpversion()
    ]
]);

$error_handler = new \Raven_ErrorHandler($sentryClient);
$error_handler->registerExceptionHandler();
$error_handler->registerErrorHandler(true, E_ALL);
$error_handler->registerShutdownFunction();
