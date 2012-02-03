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

class __artist_gallery extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('artist');
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
		
		$sql = 'SELECT g.*
			FROM _artists a, _artists_images g
			WHERE a.ub = ?
				AND a.ub = g.ub
			ORDER BY image ASC';
		if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
			_style('gallery');

			$tcol = 0;
			foreach ($result as $row) {
				if (!$tcol) _style('gallery.row');

				_style('gallery.row.col', array(
					'ITEM' => $row['image'],
					'URL' => s_link('a', array($this->data['subdomain'], 4, $row['image'], 'view')),
					'U_FOOTER' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'footer', 'image' => $row['image'])),
					'IMAGE' => SDATA . 'artists/' . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
					'RIMAGE' => get_a_imagepath(SDATA . 'artists/' . $this->data['ub'], $row['image'] . '.jpg', array('x1', 'gallery')),
					'WIDTH' => $row['width'],
					'HEIGHT' => $row['height'],
					'TFOOTER' => $row['image_footer'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'])
				);

				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
		} else {
			_style('empty', array(
				'MESSAGE' => $user->lang['CONTROL_A_GALLERY_EMPTY'])
			);
		}

		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'delete');

		v_style(array(
			'S_HIDDEN' => s_hidden($s_hidden),
			'ADD_IMAGE_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
		
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