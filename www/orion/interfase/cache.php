<?php

namespace App;

class Cache {
    public $cache = array();
    public $use = true;

    public function __construct() {
        if (!defined('USE_CACHE')) {
            $this->use = false;
        }
    }

    public function config() {
        $sql = 'SELECT *
            FROM _application';
        $config = sql_rowset($sql, 'config_name', 'config_value');

        return $config;
    }

    public function get($var) {
        if (!$this->use) {
            return false;
        }

        $filename = ROOT . 'cache/' . $var . '.php';

        if (@file_exists($filename)) {
            if (!@include_once $filename) {
                $this->delete($var);
                return;
            }

            if (!empty($this->cache[$var])) {
                return $this->cache[$var];
            }

            return true;
        }

        return;
    }

    public function save($var, &$data) {
        global $config;

        if (!$this->use) {
            return;
        }

        $filename = ROOT . 'cache/' . $var . '.php';

        $fp = @fopen($filename, 'w');
        if ($fp) {
            $file_buffer = '<?php $' . 'this->cache[\'' . $var . '\'] = ' . ((is_array($data)) ? $this->format($data) : "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $data)) . "'") . '; ?>';

            @flock($fp, LOCK_EX);
            fputs($fp, $file_buffer);
            @flock($fp, LOCK_UN);
            fclose($fp);

            _chmod($filename, $config['mask']);
        }

        return $data;
    }

    public function delete($list) {
        if (!$this->use) {
            return;
        }

        foreach (w($list) as $var) {
            $cache_filename = ROOT . 'cache/' . $var . '.php';
            if (file_exists($cache_filename)) {
                _rm($cache_filename);
            }
        }

        return;
    }

    public function format($data) {
        $lines = w();
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $lines[] = "'$k'=>" . $this->format($v);
            } elseif (is_int($v)) {
                $lines[] = "'$k'=>$v";
            } elseif (is_bool($v)) {
                $lines[] = "'$k'=>" . (($v) ? 'true' : 'false');
            } else {
                $lines[] = "'$k'=>'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $v)) . "'";
            }
        }
        return 'array(' . implode(',', $lines) . ')';
    }
}
