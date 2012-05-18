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
		$result = sql_rowset(sql_filter($sql, $this->object['ub']));
		
		foreach ($result as $i => $row) {
			if (!$i) _style('gallery');
			
			_style('gallery.row', array(
				'ITEM' => $row['image'],
				'URL' => s_link('a', array($this->object['subdomain'], 4, $row['image'], 'view')),
				'U_FOOTER' => s_link('acp', array('artist_gallery', 'a' => $this->object['subdomain'], 'footer' => $row['image'])),
				'IMAGE' => SDATA . 'artists/' . $this->object['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
				'RIMAGE' => get_a_imagepath(SDATA . 'artists/' . $this->object['ub'], $row['image'] . '.jpg', array('x1', 'gallery')),
				'WIDTH' => $row['width'],
				'HEIGHT' => $row['height'],
				'TFOOTER' => $row['image_footer'],
				'VIEWS' => $row['views'],
				'DOWNLOADS' => $row['downloads'])
			);
		}

		return;
	}
	
	private function upload() {
		if (isset($_POST['submit'])) {
			$filepath = '..' . SDATA . 'artists/' . $this->data['ub'] . '/';
			$filepath_1 = $filepath . 'x1/';
			$filepath_2 = $filepath . 'gallery/';
			$filepath_3 = $filepath . 'thumbnails/';

			$f = $upload->process($filepath_1, 'add_image', 'jpg');

			if (!sizeof($upload->error) && $f !== false) {
				$sql = 'SELECT MAX(image) AS total
					FROM _artists_images
					WHERE ub = ?';
				$img = sql_field(sql_filter($sql, $this->data['ub']), 'total', 0);

				$a = 0;
				foreach ($f as $row) {
					$img++;

					$xa = $upload->resize($row, $filepath_1, $filepath_1, $img, array(600, 400), false, false, true);
					if ($xa === false) {
						continue;
					}

					$xb = $upload->resize($row, $filepath_1, $filepath_2, $img, array(300, 225), false, false);
					$xc = $upload->resize($row, $filepath_2, $filepath_3, $img, array(100, 75), false, false);

					$insert = array(
						'ub' => (int) $this->data['ub'],
						'image' => (int) $img,
						'width' => $xa['width'],
						'height' => $xa['height']
					);
					$sql = 'INSERT INTO _artists_images' . sql_build('INSERT', $insert);
					sql_query($sql);

					$a++;
				}

				if ($a) {
					$sql = 'UPDATE _artists SET images = images + ??
						WHERE ub = ?';
					sql_query(sql_filter($sql, $a, $this->data['ub']));
				}

				redirect(s_link('acp', array('artist_gallery', 'a' => $this->data['subdomain'])));
			} else {
				_style('error', array(
					'MESSAGE' => parse_error($upload->error))
				);
			}
		}

		return;
	}
	
	private function remove() {
		$error = false;
		if (isset($_POST['submit'])) {
			$s_images = request_var('ls_images', array(0));
			if (sizeof($s_images)) {
				$affected = array();

				$common_path = './..' . SDATA . 'artists/' . $this->data['ub'] . '/';
				$path = array(
					$common_path . 'x1/',
					$common_path . 'gallery/',
					$common_path . 'thumbnails/',
				);

				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub = ?
						AND image IN (??)
					ORDER BY image';
				$result = sql_rowset(sql_filter($sql, $this->data['ub'], implode(',', $s_images)));

				foreach ($result as $row) {
					foreach ($path as $path_row) {
						$filepath = $path_row . $row['image'] . '.jpg';
						_rm($filepath);
					}
					$affected[] = $row['image'];
				}

				if (count($affected)) {
					$sql = 'DELETE FROM _artists_images
						WHERE ub = ?
							AND image IN (??)';
					sql_query(sql_filter($sql, $this->data['ub'], implode(',', $affected)));

					$sql = 'UPDATE _artists SET images = images - ??
						WHERE ub = ?';
					sql_query(sql_filter($sql, sql_affectedrows(), $this->data['ub']));
				}
			}
		}

		redirect(s_link('acp', array('artist_gallery', 'a' => $this->data['subdomain'])));
		
		return;
	}
	
	private function footer() {
		$a = request_var('image', '');
		$t = request_var('value', '');

		$sql = 'SELECT *
			FROM _artists_images
			WHERE ub = ?
				AND image = ?';
		if (!$row = sql_fieldrow(sql_filter($sql, $this->data['ub'], $a))) {
			fatal_error();
		}

		$sql = 'UPDATE _artists_images SET image_footer = ?
			WHERE ub = ?
				AND image = ?';
		sql_query(sql_filter($sql, $t, $this->data['ub'], $a));

		$this->e($t);
	}
}

?>