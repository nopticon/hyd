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

class __forums_post_modify extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		if (!$this->submit) {
			return false;
		}
		
		$post_id = request_var('pid', '');
		$post_message = request_var('post_text', '', true);
		
		if (empty($post_id) || empty($post_message)) {
			fatal_error();
		}
		
		$sql = 'SELECT *
			FROM _forum_posts
			WHERE post_id = ?';
		if (!$postdata = sql_fieldrow(sql_filter($sql, $post_id))) {
			fatal_error();
		}
		
		//
		require_once(ROOT . 'interfase/comments.php');
		$comments = new _comments();
		
		$post_message = $comments->prepare($post_message);
		
		//
		$sql = 'UPDATE _forum_posts SET post_text = ?
			WHERE post_id = ?';
		sql_query(sql_filter($sql, $post_message, $post_id));
		
		return redirect(s_link('post', $post_id));
	}
}

?>