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

class __forums_topic_feature extends mac {
	private $id;
	private $object;
	
	public function __construct() {
		parent::__construct();
		
		$this->auth('mod');
	}
	
	public function _home() {
		global $config, $user, $cache;
		
		if (!$this->submit) {
			return;
		}
		
		$this->id = request_var('msg_id', 0);
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?';
		if (!$this->object = sql_fieldrow(sql_filter($sql, $this->id))) {
			fatal_error();
		}
		
		$this->object = (object) $this->object;
		
		$this->object->new_value = ($this->object->topic_featured) ? 0 : 1;
		topic_feature($this->id, $this->object->new_value);
		
		$sql_insert = array(
			'bio' => $user->d('user_id'),
			'time' => time(),
			'ip' => $user->ip,
			'action' => 'feature',
			'old' => $this->object->topic_featured,
			'new' => $this->object->new_value
		);
		$sql = 'INSERT INTO _log_mod' . sql_build('INSERT', $sql_insert);
		sql_query($sql);
		
		return redirect(s_link('topic', $this->id));
	}
}

?>