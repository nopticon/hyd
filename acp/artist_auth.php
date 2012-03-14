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
if (!defined('IN_APP')) exit;

class __artist_auth extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('artist');
	}
	
	private function _show($rowset, $unique = false) {
		global $user, $comments;

		$total = count($rowset);

		foreach ($rowset as $i => $row) {
			if (!$i) _style('members');

			$prof = $comments->user_profile($row);

			_style('members.row', array(
				'USER_ID' => $prof['user_id'],
				'PROFILE' => $prof['profile'],
				'USERNAME' => $prof['username'],
				'COLOR' => $prof['user_color'],
				'AVATAR' => $prof['user_avatar'],
				'DELETE' => $unique || ($total > 1 && $prof['user_id'] != $user->d('user_id')) || ($user->is('founder') && $prof['user_id'] != $user->d('user_id')),
				'CHECK' => ($total == 1 && $unique))
			);
		}

		return;
	}
	
	public function _home() {
		global $config, $user, $cache;
		
		$this->_artist();
		
		if (_button()) {
			return $this->upload();
		}
		
		if (_button('remove')) {
			return $this->remove();
		}
		
		$sql = 'SELECT u.user_id, u.user_type, u.username, u.username_base, u.user_color, u.user_avatar
			FROM _artists_auth a, _members u
			WHERE a.ub = ?
				AND a.user_id = u.user_id
			ORDER BY u.username';
		if ($result = sql_rowset(sql_filter($sql, $this->object['ub']))) {
			$this->_show($result);
		}

		return;
	}
	
	private function upload() {
		return;
	}
	
	private function remove() {
		return;
	}
}

?>