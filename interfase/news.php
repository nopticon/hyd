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

/*
_news
...
NEWS_ID					INT(11)
POST_REPLY			INT(11)
POST_ANNOUNCE		TINYINT(1)
POST_LOCAL			TINYINT(1)
POSTER_ID				MEDIUMINT(8)
POST_USERNAME		VARCHAR(25)
POST_SUBJECT		VARCHAR(255)
POST_TEXT				TEXT
POST_VIEWS			INT(11)
POST_REPLIES		INT(11)
POST_TIME				INT(11)
POST_IP					VARCHAR(8)
*/

class _news
{
	var $data = array();
	var $news = array();
	
	function _news ()
	{
		return;
	}
	
	function _setup ()
	{
		global $db;
		
		$post_id = request_var('id', 0);
		if (!$post_id)
		{
			return false;
		}
		
		$sql = 'SELECT n.*, c.*
			FROM _news n, _news_cat c
			WHERE n.news_id = ' . (int) $post_id . '
				AND n.cat_id = c.cat_id';
		$result = $db->sql_query($sql);
		
		if (!$row = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$this->data = $row;
		return true;
	}
	
	function _main()
	{
		global $user, $cache, $template;
		
		$cat = request_var('cat', '');
		if (!empty($cat))
		{
			$sql = "SELECT *
				FROM _news_cat
				WHERE cat_url = '" . $db->sql_escape($cat) . "'";
			$result = $db->sql_query($sql);
			
			if (!$cat_data = $db->sql_fetchrow($result))
			{
				fatal_error();
			}
			$db->sql_freeresult($result);
			
			$template->assign_block_vars('cat', array(
				'CAT_URL' => s_link('news', $cat_data['cat_url']),
				'CAT_NAME' => $cat_data['cat_name'])
			);
			
			//
			$sql = 'SELECT n.*, m.username, m.username_base, m.user_color
				FROM _news n, _members m
				WHERE n.cat_id = ' . (int) $cat_data['cat_id'] . '
					AND n.poster_id = m.user_id
				ORDER BY n.post_time DESC, n.news_id DESC';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
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
			$db->sql_freeresult($result);
		}
		else
		{
			if (!$cat = $cache->get('news_cat'))
			{
				$sql = 'SELECT c.*, COUNT(n.news_id) AS elements
					FROM _news_cat c, _news n
					WHERE c.cat_id = n.cat_id
					GROUP BY n.cat_id
					ORDER BY c.cat_order';
				$result = $db->sql_query($sql);
				
				$cat = array();
				while ($row = $db->sql_fetchrow($result))
				{
					$cat[] = $row;
				}
				$db->sql_freeresult($result);
				
				$cache->save('news_cat', $cat);
			}
			
			$template->assign_block_vars('list', array());
			
			foreach ($cat as $row)
			{
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
	
	function _view ()
	{
		global $user, $config, $template;
		
		$offset = intval(request_var('ps', 0));
		
		if ($this->data['poster_id'] != $user->data['user_id'] && !$offset)
		{
			$db->sql_query('UPDATE _news SET post_views = post_views + 1 WHERE news_id = ' . (int) $this->data['news_id']);
		}
		
		$sql = 'SELECT user_id, username, username_base, user_color, user_avatar, user_posts, user_gender, user_rank, user_sig
			FROM _members
			WHERE user_id = ' . (int) $this->data['poster_id'];
		$result = $db->sql_query($sql);
		
		$userinfo = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		include('./interfase/comments.php');
		$comments = new _comments();
		
		$user_profile = $comments->user_profile($userinfo);
		
		$mainpost_data = array(
			'MESSAGE' => $comments->parse_message($this->data['post_text']),
			'POST_TIME' => $user->format_date($this->data['post_time'])
		);
		
		foreach ($user_profile as $key => $value)
		{
			$mainpost_data[strtoupper($key)] = $value;
		}
		
		$template->assign_block_vars('mainpost', $mainpost_data);
		
		$comments_ref = s_link('news', $this->data['news_id']);
		
		if ($this->data['post_replies'])
		{
			$comments->reset();
			$comments->ref = $comments_ref;
			
			$comments->data = array(
				'SQL' => 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar, m.user_rank, m.user_posts, m.user_gender, m.user_sig
					FROM _news_posts p, _members m 
					WHERE p.news_id = ' . (int) $this->data['news_id'] . ' 
						AND p.post_active = 1 
						AND p.poster_id = m.user_id 
					ORDER BY p.post_time DESC
					LIMIT ' . (int) $offset . ', ' . (int) $config['s_posts']
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
		
		if ($user->data['is_member'])
		{
			$template->assign_block_vars('posting_box.box', array(
				'REF' => $comments_ref)
			);
		}
		else
		{
			$template->assign_block_vars('posting_box.only_registered', array(
				'LEGEND' => sprintf($user->lang['LOGIN_TO_POST'], '', s_link('my', 'register'))
			));
		}
	}
}

?>