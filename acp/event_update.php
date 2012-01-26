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

require_once(ROOT . 'interfase/upload.php');

class __event_update extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('colab');
	}
	
	public function _home() {
		global $config, $user, $template;
		
		if ($this->submit) {
			$upload = new upload();
			
			$event_id = request_var('event_id', 0);
			
			$sql = 'SELECT *
				FROM _events
				WHERE id = ?';
			if (!$event_data = sql_fieldrow(sql_filter($sql, $event_id))) {
				fatal_error();
			}
			
			$filepath_1 = $config['events_path'] . 'future/';
			$filepath_2 = $config['events_path'] . 'future/thumbnails/';
			
			$f = $upload->process($filepath_1, $_FILES['event_image'], w('jpg jpeg'));
			
			if (!sizeof($upload->error) && $f !== false) {
				foreach ($f as $row) {
					$xa = $upload->resize($row, $filepath_1, $filepath_1, $event_id, array(600, 400), false, false, true);
					if ($xa === false) {
						continue;
					}
					$xb = $upload->resize($row, $filepath_1, $filepath_2, $event_id, array(100, 75), false, false);
				}
				
				$sql = 'UPDATE _events SET event_update = ?
					WHERE id = ?';
				sql_query(sql_filter($sql, time(), $event_id));
				
				redirect(s_link('events', $event_data['event_alias']));
			}
			
			$template->assign_block_vars('error', array(
				'MESSAGE' => parse_error($upload->error))
			);
		}
		
		$sql = 'SELECT *
			FROM _events
			WHERE date > ?
			ORDER BY date DESC';
		$result = sql_rowset(sql_filter($sql, time()));
		
		foreach ($result as $row) {
			$template->assign_block_vars('event_list', array(
				'EVENT_ID' => $row['id'],
				'EVENT_TITLE' => $row['title'],
				'EVENT_DATE' => $user->format_date($row['date']))
			);
		}
		
		return;
	}
}

?>