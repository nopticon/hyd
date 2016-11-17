<?php
namespace App;

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

        $file_content = @file('./template/exceptions/missing.htm');

        $matches = array(
            '<!--#echo var="HTTP_HOST" -->' => $_SERVER['HTTP_HOST'],
            '<!--#echo var="REQUEST_URI" -->' => $_SERVER['REQUEST_URI']
        );

        $orig = $repl = array();
        foreach ($matches as $row_k => $row_v) {
            $orig[] = $row_k;
            $repl[] = $row_v;
        }

        echo str_replace($orig, $repl, implode('', $file_content));
        exit;
    }
}
