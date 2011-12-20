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

class common {
	public $mode;
	public $manage;
	public $control;
	public $auth;
	
	public function import_control() {
		global $control;
		
		$this->control = $control;
		return;
	}
	
	public function export_control() {
		global $control;
		
		$control = $this->control;
		return;
	}
	
	public function auth_access($member) {
		global $user;
		
		if ($user->data['is_founder']) {
			return true;
		}
		
		if ($user->data['user_type'] == USER_ARTIST && $this->control->module == 'a') {
			$sql = 'SELECT *
				FROM _artists_auth
				WHERE user_id = ?';
			$access = false;
			if (sql_fieldrow(sql_filter($sql, $user->data['user_id']))) {
				$access = true;
			}
			
			return $access;
		}
		
		return false;
	}
	
	public function check_method() {
		if (!in_array($this->mode, array_keys($this->methods))) {
			$this->mode = 'home';
		}
	}
	
	public function check_manage() {
		if (empty($this->methods[$this->mode]) || /*!method_exists($this, $this->manage) || */!in_array($this->manage, $this->methods[$this->mode])) {
			$this->manage = 'home';
		}
	}
	
	public function call_method() {
		return $this->{'_' . $this->mode . '_' . $this->manage}();
	}
	
	public function e($msg = '') {
		global $user;
		
		// GZip
		if (!isset($this->config['ob_gz'])) {
			if (strstr($user->browser, 'compatible') || strstr($user->browser, 'Gecko')) {
				ob_start('ob_gzhandler');
				$this->config['ob_gz'] = true;
			}
		}
		
		// Headers
		header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		//
		if (isset($user->lang[$msg])) {
			$msg = $user->lang[$msg];
		}
		sql_close();
		
		echo $msg;
		exit;
	}
}

?>