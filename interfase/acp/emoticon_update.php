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

class __emoticon_update extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		if ($this->submit) {
			$folder = request_var('folder', '');
			$list = request_var('list', '');
			
			//
			// Folder
			if (!empty($folder)) {
				$emoticon_path = $config['assets_path'] . 'emoticon/' . $folder;
				
				$images = array();
				
				$fp = @opendir($emoticon_path);
				while ($file = @readdir($fp)) {
					if (preg_match('#([a-z0-9]+)\.(gif|png)#is', $file, $split)) {
						$images[] = $split;
					}
				}
				@closedir($fp);
				
				$emots = array();
				$skip = array();
				$process = array();
				
				$sql = 'SELECT *
					FROM _smilies
					ORDER BY code';
				$emots = sql_rowset($sql, 'code');
				
				foreach ($images as $each) {
					$code = ':' . $each[1] . ':';
					
					if (isset($emots[$code])) {
						$skip[] = $code;
						continue;
					}
					
					$path = $folder . '/' . $each[0];
					
					$insert = array(
						'code' => $code,
						'smile_url' => $path
					);
					$sql = 'INSERT INTO _smilies' . sql_build('INSERT', $insert);
					sql_query($sql);
					
					$process[] = $insert;
				}
				
				$cache->delete('smilies');
				
				_pre($process);
				_pre($skip, true);
			}
		}
	}
}

?>