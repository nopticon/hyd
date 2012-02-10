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

class __user_ban extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache;
		
		if ($this->submit) {
			return false;
		}
		
		$username = request_var('username', '');
		if (empty($username)) {
			fatal_error();
		}
		
		$username = get_username_base($username);
		
		$sql = 'SELECT *
			FROM _members
			WHERE username_base = ?';
		if (!$userdata = sql_fieldrow(sql_filter($sql, $username))) {
			fatal_error();
		}
		
		$sql = 'SELECT *
			FROM _banlist
			WHERE ban_userid = ?';
		if (!$ban = sql_fieldrow(sql_filter($sql, $userdata['user_id']))) {
			$insert = array(
				'ban_userid' => (int) $userdata['user_id']
			);
			$sql = 'INSERT INTO _banlist' . sql_build('INSERT', $insert);
			sql_query($sql);
			
			$sql = 'DELETE FROM _sessions
				WHERE session_user_id = ?';
			sql_query(sql_filter($sql, $userdata['user_id']));
			
			echo 'El usuario ' . $userdata['username'] . ' fue bloqueado.';
		}
		
		return;
	}
}

?>