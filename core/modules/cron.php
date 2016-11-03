<?php
namespace App;

class Cron {
    public $no_layout = true;

    public function run() {
        $module = request_var('module', '');

        if (!empty($module) && preg_match('#^([a-z\_]+)$#i', $module)) {
            $module_path = ROOT . 'modules/cron/' . $module . '.php';

            if (@file_exists($module_path)) {
                $user->setup();

                @include_once $module_path;
                return;
            }
        }

        show_exception('missing');

        return;
    }
}
