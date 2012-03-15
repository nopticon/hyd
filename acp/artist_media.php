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

class __artist_media extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('artist');
	}
	
	public function _home() {
		global $config, $user, $comments;
		
		$this->_artist();
		
		if (_button()) {
			return $this->upload();
		}
		
		if (_button('remove')) {
			return $this->remove();
		}
		
		$sql = 'SELECT *
			FROM _dl
			WHERE ub = ?
			ORDER BY title';
		if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
			$downloads_type = array(
				1 => '/net/icons/browse.gif',
				2 => '/net/icons/store.gif'
			);

			foreach ($result as $row) {
				if (!$tcol) _style('media');

				_style('media.row', array(
					'ITEM' => $row['id'],
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'edit', 'd' => $row['id'])),
					'POSTS_URL' => s_link('a', array($this->data['subdomain'], 9, $row['id'])) . '#dpf',
					'IMAGE_TYPE' => $downloads_type[$row['ud']],
					'DOWNLOAD_TITLE' => $row['title'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'],
					'POSTS' => $row['posts'])
				);

				$tcol = ($tcol == 2) ? 0 : $tcol + 1;
			}
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