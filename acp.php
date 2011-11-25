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
	public $submit;
	public $url;
	public $tv = array();
	
	public function __construct() {
		return;
	}
	
	public function auth($a) {
		global $user;
		
		if (!$user->_team_auth($a)) {
			return fatal_error();
		}
		
		return true;
	}
}

class acp {
	public $module;
	
	public function __construct() {
		global $user;
		
		$user->init();
		$user->setup('control');
		
		if (!$user->data['is_member']) {
			if ($user->data['is_bot']) {
				redirect(s_link());
			}
			do_login();
		}
		
		if (!$user->_team_auth('all')) {
			fatal_error();
		}
	}
	
	public function run() {
		$this->module = request_var('module', '');
		
		if (empty($this->module) || !preg_match('#[a-z\_]+#i', $this->module)) {
			fatal_error();
		}
		
		$this->filepath = ROOT . 'interfase/acp/' . $this->module . '.php';
		
		if (!@file_exists($this->filepath)) {
			fatal_error();
		}
		
		require_once($this->filepath);
		
		$_object = '__' . $this->module;
		if (!class_exists($_object)) {
			fatal_error();
		}
		
		$module = new $_object();
		
		$module->submit = isset($_POST['submit']);
		$module->url = s_link('acp', $this->module);
		$module->alias = $this->module;
		
		$module->home();
		
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
		
		page_layout($this->module, $module->template, $local_tv);
		
		return true;
	}
}

$acp = new acp();
$acp->run();

?>