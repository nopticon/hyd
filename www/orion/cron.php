<?php

require_once './interfase/common.php';

$user->init(false, true);

$module = request_var('module', '');

if (!empty($module) && preg_match('#^([a-z\_]+)$#i', $module)) {
    $module_path = ROOT . 'objects/cron/' . $module . '.php';

    if (@file_exists($module_path)) {
        $user->setup();

        @include_once $module_path;
        return;
    }
}

show_exception('missing');
