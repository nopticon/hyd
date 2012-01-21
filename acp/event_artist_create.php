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

class __event_artist_create extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		if (!$this->submit) {
			$sql = 'SELECT *
				FROM _events
				WHERE date > ??
				ORDER BY date DESC';
			$events = sql_rowset(sql_filter($sql, time()));
			
			foreach ($events as $i => $row) {
				if (!$i) _style('events');
				
				_style('events.row', array(
					'ID' => $row['id'],
					'TITLE' => $row['title'],
					'DATE' => $user->format_date($row['date']))
				);
			}
			
			return;
		}
		
		$request = _request(array('event' => 0, 'artist' => ''));
		
		if (_empty($request)) {
			_pre('Debe completar la informacion.', true);
		}
		
		$sql = 'SELECT id, event_alias
			FROM _events
			WHERE id = ?';
		if (!$event = sql_fieldrow(sql_filter($sql, $request->event))) {
			_pre('El evento no existe.', true);
		}
		
		$e_artist = explode("\n", $request->artist);
		foreach ($e_artist as $row) {
			$subdomain = get_subdomain($row);
			
			$sql = 'SELECT ub
				FROM _artists
				WHERE subdomain = ?';
			if ($a_ub = sql_field(sql_filter($sql, $subdomain), 'ub', 0)) {
				$sql_insert = array(
					'a_artist' => $a_ub,
					'a_event' => $event['id']
				);
				$sql = 'INSERT INTO _artists_events' . sql_build('INSERT', $sql_insert);
				sql_query($sql);
			}
		}
		
		return redirect(s_link('events', $event['event_alias']));
	}
}

?>