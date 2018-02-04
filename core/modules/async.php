<?php namespace App;

class Async {
    public $no_layout = true;

    public function run() {
        global $user;

        if (strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
            $module = request_var('module', '');

            if (!empty($module) && preg_match('#^([a-z\_]+)$#i', $module)) {
                $module_path = ROOT . 'modules/async/' . $module . '.php';

                if (@file_exists($module_path)) {
                    $user->init(false);
                    $user->setup();

                    @include_once $module_path;
                    return;
                }
            }
        }

        show_exception('missing');

        return;
    }
}
