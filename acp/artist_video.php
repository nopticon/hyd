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

class __artist_video extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('artist');
	}
	
	public function _home() {
		global $config, $user, $comments;
		
		$this->_artist();
		
		if (_button()) {
			return $this->create();
		}
		
		if (_button('remove')) {
			return $this->remove();
		}
		
		$sql = 'SELECT *
			FROM _artists_video
			WHERE video_a = ?
			ORDER BY video_added DESC';
		$result = sql_rowset(sql_filter($sql, $this->object['ub']));

		foreach ($result as $i => $row) {
			if (!$i) _style('video');

			_style('video.row', array(
				'ID' => $row['video_id'],
				'CODE' => $row['video_code'],
				'NAME' => $row['video_name'],
				'TIME' => $user->format_date($row['video_added']))
			);
		}
		
		return;
	}
	
	private function create() {
		$code = request_var('code', '');
		$vname = request_var('vname', '');
		
		if (!empty($code)) {
			$sql = 'SELECT *
				FROM _artists_video
				WHERE video_a = ?
					AND video_code = ?';
			if (sql_fieldrow(sql_filter($sql, $this->object['ub'], $code))) {
				$code = '';
			}
		}
		
		if (!empty($code)) {
			$code = get_yt_code($code);
		}
		
		if (!empty($code)) {
			$insert = array(
				'video_a' => $this->object['ub'],
				'video_name' => $vname,
				'video_code' => $code,
				'video_added' => time()
			);
			$sql = 'INSERT INTO _artists_video' . sql_build('INSERT', $insert);
			sql_query($sql);
				$sql = 'UPDATE _artists SET a_video = a_video + 1
				WHERE ub = ?';
			sql_query(sql_filter($sql, $this->object['ub']));
		}
		
		return redirect(_page());
	}
	
	private function upload() {
		return;
	}
	
	private function remove() {
		_pre('TODO', true);
		return;
	}
}

?>