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

class __event_artist_delete extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache;
		
		if (!$this->submit) {
			$sql = 'SELECT *
				FROM _events
				WHERE date > ??
				ORDER BY date DESC';
			$result = sql_rowset(sql_filter($sql, time()));
			
			foreach ($result as $i => $row) {
				if (!$i) _style('events');
				
				_style('events.row', array(
					'ID' => $row['id'],
					'TITLE' => $row['title'],
					'DATE' => $user->format_date($row['date']))
				);
			}
			
			return;
		}
		
		$event = request_var('event', 0);
		$artist = request_var('artist', '');
		
		if (!$event || empty($artist)) {
			_pre('Debe ingresar toda la informacion.', true);
		}
		
		$sql = 'SELECT *
			FROM _events
			WHERE id = ?';
		if (!$row = sql_fieldrow(sql_filter($sql, $event))) {
			_pre('El evento no existe.', true);
		}
		
		$e_artist = explode(nr(), $artist);
		foreach ($e_artist as $row) {
			$subdomain = get_subdomain($row);
			
			$sql = 'SELECT *
				FROM _artists
				WHERE subdomain = ?';
			if ($a_row = sql_fieldrow(sql_filter($sql, $subdomain))) {
				$sql = 'DELETE FROM _artists_events
					WHERE a_artist = ?
						AND a_event = ?';
				sql_query(sql_filter($sql, $a_row['ub'], $event));
			}
		}
		
		return redirect(s_link('events', $row['event_alias']));
	}
}

?>