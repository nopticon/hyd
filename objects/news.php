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

require_once(ROOT . 'interfase/comments.php');

class _news {
	public $data = array();
	public $news = array();
	
	public function __construct() {
		return;
	}
	
	public function _setup() {
		$news_id = request_var('id', 0);
		if (!$news_id) {
			return false;
		}
		
		$news_field = (!is_numb($news_id)) ? 'news_alias' : 'news_id';
		
		$sql = 'SELECT n.*, c.*
			FROM _news n, _news_cat c
			WHERE n.?? = ?
				AND n.cat_id = c.cat_id';
		if (!$this->data = sql_fieldrow(sql_filter($sql, $news_field, $news_id))) {
			fatal_error();
		}
		
		return true;
	}
	
	public function action($mode) {
		global $config, $user, $cache, $template;
		
		switch ($mode) {
			case 'create':
				$submit = (isset($_REQUEST['submit'])) ? true : false;
				
				if ($submit) {
					$cat_id = request_var('cat_id', 0);
					$news_active = 0;
					$news_alias = '';
					$news_subject = '';
					$news_text = '';
					$news_desc = '';
					
					$sql_insert = array(
						'news_fbid' => 0,
						'cat_id' => '',
						'news_active' => $mews_active,
						'news_alias' => $news_alias,
						'post_reply' => 0,
						'post_type' => 0,
						'poster_id' => $user->d('user_id'),
						'post_subject' => $news_subject,
						'post_text' => $news_text,
						'post_desc' => $news_desc,
						'post_views' => 0,
						'post_replies' => 0,
						'post_time' => time(),
						'post_ip' => $user->ip,
						'image' => ''
					);
					$sql = 'INSERT INTO _news' . sql_build('INSERT', $sql_insert);
					$news_id = sql_query_nextid($sql);
				}
				
				$sql = 'SELECT cat_id, cat_name
					FROM _news_cat
					ORDER BY cat_order';
				$news_cat = sql_rowset($sql);
				
				foreach ($news_cat as $i => $row) {
					if (!$i) $template->assign_block_vars('news_cat');
					
					$template->assign_block_vars('news_cat.row', array(
						'CAT_ID' => $row['cat_id'],
						'CAT_NAME' => $row['cat_name'])
					);
				}
				break;
		}
	}
	
	public function _main() {
		global $user, $cache, $template;
		
		$cat = request_var('id', '');
		
		if (!empty($cat)) {
			$sql = 'SELECT *
				FROM _news_cat
				WHERE cat_url = ?';
			if (!$cat_data = sql_fieldrow(sql_filter($sql, $cat))) {
				fatal_error();
			}
			
			$template->assign_block_vars('cat', array(
				'CAT_URL' => s_link('news', $cat_data['cat_url']),
				'CAT_NAME' => $cat_data['cat_name'])
			);
			
			//
			$sql = 'SELECT n.*, m.username, m.username_base, m.user_color
				FROM _news n, _members m
				WHERE n.cat_id = ?
					AND n.poster_id = m.user_id
				ORDER BY n.post_time DESC, n.news_id DESC';
			$result = sql_rowset(sql_filter($sql, $cat_data['cat_id']));
			
			foreach ($result as $row) {
				$template->assign_block_vars('cat.item', array(
					'URL' => s_link('news', $row['news_id']),
					'SUBJECT' => $row['post_subject'],
					'DESC' => $row['post_desc'],
					'TIME' => $user->format_date($row['post_time'], 'd M'),
					'USERNAME' => $row['username'],
					'PROFILE' => s_link('m', $row['username_base']),
					'COLOR' => $row['user_color'])
				);
			}
		} else {
			if (!$cat = $cache->get('news_cat')) {
				$sql = 'SELECT c.*, COUNT(n.news_id) AS elements
					FROM _news_cat c, _news n
					WHERE c.cat_id = n.cat_id
					GROUP BY n.cat_id
					ORDER BY c.cat_order';
				if ($cat = sql_rowset($sql)) {
					$cache->save('news_cat', $cat);
				}
			}
			
			$template->assign_block_vars('list', array());
			
			foreach ($cat as $row) {
				$template->assign_block_vars('list.item', array(
					'URL' => s_link('news', $row['cat_url']),
					'NAME' => $row['cat_name'],
					'DESC' => $row['cat_desc'],
					'ELEMENTS' => $row['elements'])
				);
			}
		}
		
		return;
	}
	
	public function _view() {
		global $user, $config, $template;
		
		$offset = intval(request_var('ps', 0));
		
		if ($this->data['poster_id'] != $user->data['user_id'] && !$offset) {
			$sql = 'UPDATE _news SET post_views = post_views + 1
				WHERE news_id = ?';
			sql_query(sql_filter($sql, $this->data['news_id']));
		}
		
		$sql = 'SELECT user_id, username, username_base, user_color, user_avatar, user_posts, user_gender, user_rank, user_sig
			FROM _members
			WHERE user_id = ?';
		$userinfo = sql_fieldrow(sql_filter($sql, $this->data['poster_id']));
		
		$comments = new _comments();
		
		$user_profile = $comments->user_profile($userinfo);
		
		$mainpost_data = array(
			'MESSAGE' => $comments->parse_message($this->data['post_text']),
			'POST_TIME' => $user->format_date($this->data['post_time'])
		);
		
		foreach ($user_profile as $key => $value) {
			$mainpost_data[strtoupper($key)] = $value;
		}
		
		$template->assign_block_vars('mainpost', $mainpost_data);
		
		$comments_ref = s_link('news', $this->data['news_id']);
		
		if ($this->data['post_replies']) {
			$comments->reset();
			$comments->ref = $comments_ref;
			
			$sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar, m.user_rank, m.user_posts, m.user_gender, m.user_sig
				FROM _news_posts p, _members m 
				WHERE p.news_id = ? 
					AND p.post_active = 1 
					AND p.poster_id = m.user_id 
				ORDER BY p.post_time DESC
				LIMIT ??, ??';
			
			$comments->data = array(
				'SQL' => sql_filter($sql, $this->data['news_id'], $offset, $config['s_posts'])
			);
			
			$comments->view($offset, 'ps', $this->data['post_replies'], $config['s_posts'], '', '', 'TOPIC_');
		}
		
		$template->assign_vars(array(
			'CAT_URL' => s_link('news', $this->data['cat_url']),
			'CAT_NAME' => $this->data['cat_name'],
			'POST_SUBJECT' => $this->data['post_subject'],
			'POST_VIEWS' => number_format($this->data['post_views']),
			'POST_REPLIES' => number_format($this->data['post_replies']))
		);
		
		//
		// Posting box
		//
		$template->assign_block_vars('posting_box', array());
		
		if ($user->is('member')) {
			$template->assign_block_vars('posting_box.box', array(
				'REF' => $comments_ref)
			);
		}
		
		return;
	}
}

?>