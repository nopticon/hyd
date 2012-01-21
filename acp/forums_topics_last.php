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

class __forums_topics_last extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		$sql = 'SELECT topic_id, topic_title, topic_views, topic_replies
			FROM _forum_topics
			WHERE forum_id  NOT IN (38)
			ORDER BY topic_time DESC
			LIMIT 100';
		$result = sql_rowset($sql);
		
		foreach ($result as $i => $row) {
			$template->assign_block_vars('topics', array(
				'TOPIC_ID' => s_link('topic', $row['topic_id']),
				'TOPIC_TITLE' => $row['topic_title'],
				'TOPIC_VIEWS' => $row['topic_views'],
				'TOPIC_REPLIES' => $row['topic_replies'])
			);
		}
		
		return;
	}
}

?>