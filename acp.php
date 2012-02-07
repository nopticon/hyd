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
define('IN_NUCLEO', true);
require_once('./interfase/common.php');

class mac {
	public $_access;
	public $submit;
	public $url;
	public $tv = array();
	
	protected $object;
	
	public function __construct() {
		return;
	}
	
	public function auth($name) {
		global $user;
		
		if (!$user->is($name)) {
			if (defined('_ACP')) {
				$this->_access = false;
				
				return false;
			}
			return fatal_error();
		}
		
		$this->_access = true;
		return true;
	}
	
	public function can() {
		return $this->_access;
	}
	
	public function _artist() {
		global $user;
		
		if ($user->is('artist')) {
			$sql = 'SELECT a.ub
				FROM _artists_auth t 
				INNER JOIN _artists a ON a.ub = t.ub
				WHERE t.user_id = ?';
			if ($artist_ary = sql_rowset(sql_filter($sql, $user->d('user_id')), false, 'ub')) {
				$sql_where = sql_filter('WHERE ub IN (??)', implode(',', array_map('intval', $mod_ary)));
			}
		}
		
		
		
		
		$artist = request_var('a', '');
		
		if (empty($artist)) {
			redirect(s_link('acp', array('artist_select', 'r' => 'artist_gallery')));
		}
		
		if (!$this->object = get_artist($artist)) {
			fatal_error();
		}
	}
}

class acp {
	private $module;
	
	public function __construct() {
		global $user;
		
		$user->init();
		$user->setup('control');
		
		if (!$user->is('member')) {
			do_login();
		}
		
		if ($arg = request_var('args', '')) {
			foreach (explode('.', $arg) as $str_pair) {
				$pair = explode(':', $str_pair);
				
				if (isset($pair[0]) && isset($pair[1]) && !empty($pair[0])) {
					$_REQUEST[$pair[0]] = $pair[1];
				}
			}
		}
		
		return;
	}
	
	public function run() {
		$this->module = request_var('module', '');
		
		if (empty($this->module)) {
			return $this->rights();
		}
		
		if (!preg_match('#[a-z\_]+#i', $this->module)) {
			fatal_error();
		}
		
		$this->filepath = ROOT . 'acp/' . $this->module . '.php';
		
		if (!@file_exists($this->filepath)) {
			fatal_error();
		}
		
		require_once($this->filepath);
		
		$_object = '__' . $this->module;
		if (!class_exists($_object)) {
			fatal_error();
		}
		
		$module = new $_object();
		
		$module->submit = _button();
		$module->url = s_link() . substr($_SERVER['REQUEST_URI'], 1);
		$module->alias = $this->module;
		
		$module->_home();
		
		if (!isset($module->template)) {
			$module->template = 'acp/' . $this->module;
		}
		
		$local_tv = array(
			'MODULE_URL' => $module->url,
			
			'UPLOAD_MAXSIZE' => upload_maxsize(),
			'UPLOAD_MAXSIZE_MB' => (upload_maxsize() / 1024 / 1024)
		);
		
		if (isset($module->tv)) {
			$local_tv = array_merge($local_tv, $module->tv);
		}
		
		return page_layout($this->module, $module->template, $local_tv);
	}
	
	private function rights() {
		$acp_dir = ROOT . 'acp/';
		
		$i = 0;
		
		$fp = @opendir($acp_dir);
		while ($row = @readdir($fp)) {
			if (!preg_match('#([a-z\_]+).php#i', $row, $part) || $row == '_template.php') {
				continue;
			}
			
			require_once($acp_dir . $row);
			
			$acp_alias = $part[1];
			$acp_upper = strtoupper($acp_alias);
			$object_name = '__' . $acp_alias;
			
			if (!class_exists($object_name)) {
				continue;
			}
			
			if (!defined('_ACP')) {
				define('_ACP', true);
			}
			
			$object = new $object_name();
			
			if ($object->can()) {
				if (!$i) _style('acp_list');
				
				_style('acp_list.row', array(
					'URL' => s_link('acp', $acp_alias),
					'NAME' => lang('ACP_' . $acp_alias, $acp_alias),
					'IMAGE' => $acp_alias)
				);
				
				$i++;
			}
		}
		@closedir($fp);
		
		return page_layout('ACP', 'acp');
	}
}

$acp = new acp();
$acp->run();

?>