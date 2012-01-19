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

class __artist_download_create extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function home() {
		global $config, $user, $cache, $template;
		
		$limit = set_time_limit(0);
		$error = array();
		
		if ($this->submit) {
			require_once(ROOT . 'interfase/upload.php');
			$upload = new upload();
			
			$artist_id = request_var('artist', 0);
			
			$sql = 'SELECT ub, subdomain
				FROM _artists
				WHERE ub = ?';
			if (!$artist_data = sql_fieldrow(sql_filter($sql, $artist_id))) {
				fatal_error();
			}
			
			//$filepath = artist_path($artist_data['subdomain'], $artist_data['ub'], true, true);
			
			$filepath = $config['artists_path'] . $artist_data['ub'] . '/';
			$filepath_1 = $filepath . 'media/';
			
			$f = $upload->process($filepath_1, $_FILES['add_dl'], 'mp3');
			
			if (!sizeof($upload->error) && $f !== false) {
				require_once(ROOT . 'interfase/id3/getid3/getid3.php');
				$getID3 = new getID3;
				
				$sql = 'SELECT MAX(id) AS total
					FROM _dl';
				$a = sql_field($sql, 'total', 0);
				
				$proc = 0;
				foreach ($f as $row) {
					$a++;
					$proc++;
					
					$filename = $upload->rename($row, $a);
					$tags = $getID3->analyze($filename);
					
					$mt = new stdClass();
					
					foreach (w('title genre album year') as $i) {
						$mt->$i = (isset($tags['tags']['id3v1'][$i][0])) ? htmlencode($tags['tags']['id3v1'][$i][0]) : '';
					}
					
					$insert_media = array(
						'ud' => 1,
						'ub' => $artist_id,
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
					$sql = 'INSERT INTO _dl' . sql_build('INSERT', $insert_media);
					sql_query($sql);
				}
				
				$sql = 'UPDATE _artists SET um = um + ??
					WHERE ub = ?';
				sql_query(sql_filter($sql, $proc, $a_id));
				
				$cache->delete('downloads_list');
				
				redirect(s_link('a', $artist_data['subdomain']));
			}
			
			$template->assign_block_vars('error', array(
				'MESSAGE' => parse_error($upload->error))
			);
		}
		
		$sql = 'SELECT *
			FROM _artists
			ORDER BY name';
		$result = sql_rowset($sql);
		
		foreach ($result as $i => $row) {
			if (!$i) $template->assign_block_vars('artists', array());
			
			$template->assign_block_vars('artists.row', array(
				'ARTIST_ID' => $row['ub'],
				'ARTIST_NAME' => $row['name'])
			);
		}
		
		return;
	}
}

?>