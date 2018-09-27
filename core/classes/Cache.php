<?php namespace App;

class Cache {
    public $cache = [];
    public $use   = true;

    public function __construct() {
        if (defined('CACHE_NO')) {
            $this->use = false;
        }
    }

    public function config() {
        $sql = 'SELECT *
            FROM _application';

        return sql_rowset($sql, 'config_name', 'config_value');
    }

    public function get($name, $default = []) {
        if (!$this->use) {
            return false;
        }

        $filename = config('cache_path') . $name . '.php';

        if (@file_exists($filename)) {
            ob_start();
            include_once $filename;

            $content = ob_get_contents();
            ob_end_clean();

            if ($content) {
                return json_decode($content, true);
            }
        }

        return $default;
    }

    public function save($name, &$data) {
        if (!$this->use) {
            return;
        }

        if (!file_exists(config('cache_path'))) {
            return;
        }

        $filename = config('cache_path') . $name . '.php';

        @file_put_contents($filename, json_encode($data));

        return $data;
    }

    public function delete($list, $default = []) {
        if (!$this->use) {
            return $default;
        }

        foreach (w($list) as $name) {
            $cache_filename = config('cache_path') . $name . '.php';
            if (file_exists($cache_filename)) {
                _rm($cache_filename);
            }
        }

        return $default;
    }
}
