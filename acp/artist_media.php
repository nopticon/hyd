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
		if ($result = sql_rowset(sql_filter($sql, $this->object['ub']))) {
			foreach ($result as $i => $row) {
				if (!$i) _style('media');
				
				_style('media.row', array(
					'ITEM' => $row['id'],
					'URL' => s_link('acp', array('artist_media', 'a' => $this->object['subdomain'], 'id' => $row['id'])),
					'POSTS_URL' => s_link('a', array($this->object['subdomain'], 9, $row['id'])) . '#dpf',
					'IMAGE_TYPE' => $downloads_type[$row['ud']],
					'DOWNLOAD_TITLE' => $row['title'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads']
				));
			}
		}
		
		return;
	}
	
	private function upload() {
		global $config, $user, $cache, $upload;
		
		$limit = set_time_limit(0);
		
		$filepath = $config['artists_path'] . $this->object['ub'] . '/';
		$filepath_1 = $filepath . 'media/';
		
		$f = $upload->process($filepath_1, 'create', 'mp3');
		
		if (!sizeof($upload->error) && $f !== false) {
			$a = sql_total('_dl');
			
			foreach ($f as $i => $row) {
				if (!$i) {
					require_once(ROOT . 'interfase/getid3/getid3.php');
					$getID3 = new getID3;
				}
				
				$filename = $upload->rename($row, $a);
				$tags = $getID3->analyze($filename);
				$a++;
				
				$mt = new stdClass();
				foreach (w('title genre album year') as $w) {
					$mt->$w = (isset($tags['tags']['id3v1'][$w][0])) ? htmlencode($tags['tags']['id3v1'][$w][0]) : '';
				}
				
				$sql_insert = array(
					'ud' => 1,
					'ub' => $this->object['ub'],
					'title' => $mt->title,
					'views' => 0,
					'downloads' => 0,
					'votes' => 0,
					'posts' => 0,
					'date' => time(),
					'filesize' => @filesize($filename),
					'duration' => $tags['playtime_string'],
					'genre' => $mt->genre,
					'album' => $mt->album,
					'year' => $mt->year
				);
				$sql = 'INSERT INTO _dl' . sql_build('INSERT', $sql_insert);
				sql_query($sql);
			}
			
			$sql = 'UPDATE _artists SET um = um + ??
				WHERE ub = ?';
			sql_query(sql_filter($sql, count($f), $a_id));
			
			$cache->delete('downloads_list');
			
			redirect(s_link('a', $this->object['subdomain']));
		}
		
		_style('error', array(
			'MESSAGE' => parse_error($upload->error))
		);
	
		return;
	}
	
	private function remove() {
		return;
	}
}

?>