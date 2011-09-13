<?php
/*
<Orion, a web development framework for RK.>
Copyright (C) <2011>  <Orion>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('IN_NUCLEO')) exit;

class cache {
	public $cache = array();
	public $use = true;
	
	public function __construct() {
		if (!defined('USE_CACHE')) {
			$this->use = false;
		}
	}
	
	public function config() {
		$sql = 'SELECT *
			FROM _config';
		$config = sql_rowset($sql, 'config_name', 'config_value');
		
		$config['request_method'] = strtolower($_SERVER['REQUEST_METHOD']);
		
		return $config;
	}
	
	public function get($var) {
		if (!$this->use) {
			return false;
		}
		
		$filename = './cache/' . /*md5*/($var) . '.php';
		
		if (@file_exists($filename)) {
			if (!@include($filename)) {
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
		if (!$this->use) {
			return;
		}
		
		$filename = './cache/' . /*md5*/($var) . '.php';
		
		$fp = @fopen($filename, 'w');
		if ($fp) {
			$file_buffer = '<?php $' . 'this->cache[\'' . $var . '\'] = ' . ((is_array($data)) ? $this->format($data) : "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $data)) . "'") . '; ?>';
			
			@flock($fp, LOCK_EX);
			fputs($fp, $file_buffer);
			@flock($fp, LOCK_UN);
			fclose($fp);
			
			@chmod($filename, 0777);
		}
		
		return;
	}
	
	public function delete() {
		if (!$this->use) {
			return;
		}
		
		foreach (func_get_args() as $var) {
			$cache_filename = './cache/' . /*md5*/($var) . '.php';
			if (file_exists($cache_filename)) {
				@unlink($cache_filename);
			}
		}
		
		return;
	}
	
	//
	// Borrowed from phpBB 2.2 : acm_file.php
	//
	public function format($data) {
		$lines = array();
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$lines[] = "'$k'=>" . $this->format($v);
			} elseif (is_int($v)) {
				$lines[] = "'$k'=>$v";
			} elseif (is_bool($v)) {
				$lines[] = "'$k'=>" . (($v) ? 'TRUE' : 'FALSE');
			} else {
				$lines[] = "'$k'=>'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $v)) . "'";
			}
		}
		return 'array(' . implode(',', $lines) . ')';
	}
}

?>