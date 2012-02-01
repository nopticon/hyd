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

class __forums_topic_case extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('mod');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		if (!$this->submit) {
			return false;
		}
		
		$topic_id = request_var('topic_id', 0);
		
		if (!$topic_id) {
			fatal_error();
		}
	
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?';
		if (!$data = sql_fieldrow(sql_filter($sql, $topic_id))) {
			fatal_error();
		}
		
		$title = ucfirst(strtolower($data['topic_title']));
		
		$sql = 'UPDATE _forum_topics SET topic_title = ?
			WHERE topic_id = ?';
		sql_query(sql_filter($sql, $title, $topic_id));
		
		return _pre($data['topic_title'] . ' > ' . $title, true);
	}
}

?>