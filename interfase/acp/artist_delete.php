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

class __artist_delete extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function home() {
		global $config, $user, $cache, $template;
		
		if ($this->submit) {
			$name = request_var('name', '');
			
			$sql = 'SELECT *
				FROM _artists
				WHERE name = ?';
			if (!$a_data = sql_fieldrow(sql_filter($sql, $name))) {
				fatal_error();
			}
			
			$mods = array();
			
			$sql = 'SELECT m.user_id, m.user_email
				FROM _artists_auth a, _members m
				WHERE a.ub = ?
					AND a.user_id = m.user_id';
			$result = sql_rowset(sql_filter($sql, $a_data['ub']));
			
			foreach ($result as $row) {
				$mods[] = $row['user_id'];
			}
			
			if (count($mods)) {
				foreach ($mods as $i => $each) {
					$sql = 'SELECT COUNT(user_id) AS total
						FROM _artists_auth
						WHERE user_id = ?';
					$total = sql_field(sql_filter($sql, $each), 'total', 0);
					
					if ($total > 1) {
						unset($mods[$i]);
					}
				}
			}
			
			if (count($mods)) {
				$sql = 'UPDATE _members SET user_auth_control = 0
					WHERE user_id IN (??)';
				$d_sql[] = sql_filter($sql, implode(',', $mods));
			}
			
			$d_sql = array();
			
			$ary_sql = array(
				'DELETE FROM _artists WHERE ub = ?',
				'DELETE FROM _artists_auth WHERE ub = ?',
				'DELETE FROM _artists_fav WHERE ub = ?',
				'DELETE FROM _artists_images WHERE ub = ?',
				'DELETE FROM _artists_log WHERE ub = ?',
				'DELETE FROM _artists_lyrics WHERE ub = ?',
				'DELETE FROM _artists_posts WHERE post_ub = ?',
				'DELETE FROM _artists_stats WHERE ub = ?',
				'DELETE FROM _artists_viewers WHERE ub = ?',
				'DELETE FROM _artists_voters WHERE ub = ?',
				'DELETE FROM _artists_votes WHERE ub = ?',
				'DELETE FROM _forum_topics WHERE topic_ub = ?',
				'DELETE FROM _dl WHERE ub = ?'
			);
			
			foreach ($ary_sql as $row) {
				$d_sql[] = sql_filter($sql, $a_data['ub']);
			}
			
			$sql = 'SELECT topic_id
				FROM _forum_topics
				WHERE topic_ub = ?';
			$topics = sql_rowset(sql_filter($sql, $a_data['ub']), false, 'topic_id');
			
			if (count($topics)) {
				$d_sql[] = sql_filter('DELETE FROM _forum_posts
					WHERE topic_id IN (??)', implode(',', $topics));
			}
			
			$sql = 'SELECT id
				FROM _dl
				WHERE ub = ?';
			if ($downloads = sql_rowset(sql_filter($sql, $a_data['ub']), false, 'id')) {
				$s_downloads = implode(',', $downloads);
				
				$ary_sql = array(
					'DELETE FROM _dl_fav WHERE dl_id IN (??)',
					'DELETE FROM _dl_posts WHERE download_id IN (??)',
					'DELETE FROM _dl_vote WHERE ud IN (??)',
					'DELETE FROM _dl_voters WHERE ud IN (??)'
				);
				
				foreach ($ary_sql as $row) {
					$d_sql[] = sql_filter($row, $s_downloads);
				}
			}
			
			if (!$this->s_dir($config['artists_path'] . $a_data['ub'])) {
				_pre('Error al eliminar directorio de artista.', true);
			}
			
			sql_query($d_sql);
			
			// Cache
			$cache->delete('ub_list', 'a_last_images');
			
			_pre('La banda fue eliminada.', true);
		}
	}
	
	private function s_dir($path) {
		if (!@file_exists($path)) {
			echo 'No folder ' . $path;
			return false;
		}
		
		$fp = @opendir($path);
		while ($file = @readdir($fp)) {
			if ($file == '.' || $file == '..') {
				continue;
			}
			
			$current_full_path = $path . '/' . $file;
			
			if (is_dir($current_full_path)) {
				$this->s_dir($current_full_path);
				continue;
			}
			
			if (!unlink($current_full_path)) {
				return false;
			}
		}
		@closedir($fp);
		
		if (!rmdir($path)) {
			return false;
		}
		
		return true;
	}
}

?>