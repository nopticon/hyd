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

include('./interfase/downloads.php');

class _art extends downloads
{
	var $data = array();
	
	function _art()
	{
		return;
	}
	
	function _sql()
	{
		global $cache;
		
		$rowset = array();
		if (!($rowset = $cache->get('art')))
		{
			global $db;
			
			$sql = 'SELECT *
				FROM _art
				WHERE ub = 0 
				ORDER BY art_datetime DESC, downloads DESC';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				$rowset[$row['art_id']]['title'] = $row['title'];
				$rowset[$row['art_id']]['image'] = $row['image'];
			}
			$db->sql_freeresult($result);
			
			$cache->save('art', $rowset);
		}
		
		return $rowset;
	}
	
	function _setup()
	{
		global $db;
		
		$art_id = intval(request_var('id', 0));
		if ($art_id)
		{
			$sql = 'SELECT *
				FROM _art
				WHERE art_id = ' . (int) $art_id;
			$result = $db->sql_query($sql);
			
			if (!$data = $db->sql_fetchrow($result))
			{
				fatal_error();
			}
			$db->sql_freeresult($result);
			
			$data['art_id'] = (int) $data['art_id'];
			$this->data = $data;
			
			return true;
		}
		
		return false;
	}
	
	function _rand()
	{
		static $rowset;
		
		if (!isset($rowset))
		{
			$rowset = $this->_sql();
		}
		
		if (!sizeof($rowset))
		{
			return;
		}
		
		$selected = array_rand($rowset);
		if (empty($rowset[$selected]))
		{
			return $this->_rand();
		}
		
		global $template;
		
		$template->assign_block_vars('wallpaper', array(
			'URL' => s_link('art', $selected),
			'IMAGE' => '/data/art/thumbnails/' . $selected . '.jpg',
			'TITLE' => $rowset[$selected]['title'])
		);
		
		return;
	}
	
	function home()
	{
		global $template;
		
		if ($rowset = $this->_sql())
		{
			$template->assign_block_vars('art', array());
			
			$tcol = 0;
			foreach ($rowset as $id => $data)
			{
				if (!$tcol)
				{
					$template->assign_block_vars('art.row', array());
				}
				
				$template->assign_block_vars('art.row.col', array(
					'URL' => s_link('art', $id),
					'IMAGE' => '/data/art/thumbnails/' . $id . '.jpg',
					'TITLE' => $data['title'])
				);
				
				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
		}
		else
		{
			$template->assign_block_vars('empty', array());
		}
		
		return;
	}
	
	function view()
	{
		global $user, $config, $template;
		
		$this->filename = $this->data['art_id'] . '.jpg';
		$this->filepath = '/data/art/full/' . $this->filename;
		
		if (!@file_exists('..' . $this->filepath))
		{
			redirect(s_link('art'));
		}
		
		if ($user->data['user_type'] != USER_FOUNDER && $user->data['user_id'] != $this->data['user_id'])
		{
			$db->sql_query('UPDATE _art SET views = views + 1 WHERE art_id = ' . (int) $this->data['art_id']);
			$this->data['views']++;
		}
		
		//
		require('./interfase/comments.php');
		$comments = new _comments();
		
		$sql = 'SELECT user_id, username, username_base, user_color, user_avatar
			FROM _members
			WHERE user_id = ' . (int) $this->data['user_id'];
		$result = $db->sql_query($sql);
		
		$userinfo = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		$profile = $comments->user_profile($userinfo);
		
		$comments_ref = s_link('art', $this->data['art_id']);
		
		if ($this->data['posts'])
		{
			$comments->reset();
			
			$start = intval(request_var('aps', 0));
			$comments->ref = $comments_ref;
			
			$comments->data = array(
				'A_LINKS_CLASS' => 'bold red',
				'SQL' => 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar, m.user_rank, m.user_posts, m.user_gender, m.user_sig
					FROM _art_posts p, _members m
					LEFT JOIN _art a ON p.art_id = a.art_id
					WHERE p.art_id = ' . (int) $this->data['art_id'] . '
						AND p.post_active = 1
						AND p.poster_id = m.user_id
					ORDER BY p.post_time DESC
					LIMIT ' . (int) $start . ', ' . (int) $config['s_posts']
			);
			
			$comments->view($start, 'aps', $this->data['posts'], 10);
		}
		
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
				'LEGEND' => sprintf($user->lang['LOGIN_TO_POST'], '', s_link('my', 'register')))
			);
		}
		
		$is_fav = false;
		if ($user->data['is_member'])
		{
			$sql = 'SELECT *
				FROM _art_fav
				WHERE art_id = ' . (int) $this->data['art_id'] . '
					AND member_id = ' . (int) $user->data['user_id'];
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$is_fav = true;
			}
			$db->sql_freeresult($result);
		}
		
		if (!$is_fav || !$user->data['is_member'])
		{
			$template->assign_block_vars('fav', array(
				'URL' => s_link('art', array($this->data['art_id'], 'fav')))
			);
		}
		
		
		$template->assign_vars(array(
			'ART_IMAGE' => $this->filepath,
			'ART_TITLE' => $this->data['title'],
			'ART_WIDTH' => $this->data['width'],
			'ART_HEIGHT' => $this->data['height'],
			'ART_VIEWS' => number_format($this->data['views']),
			'ART_DOWNLOADS' => number_format($this->data['downloads']),
			'ART_POSTS' => number_format($this->data['posts']),
			'ART_DATETIME' => $user->format_date($this->data['art_datetime']),
			'ART_FILESIZE' => $this->format_filesize($this->data['filesize']),
			
			'DOWNLOAD_URL' => s_link('art', array($this->data['art_id'], 'save')),
			
			'USERNAME' => $profile['username'],
			'USER_PROFILE' => $profile['profile'],
			'USER_COLOR' => $profile['user_color'],
			'USER_AVATAR' => $profile['user_avatar'])
		);
		
		return;
	}
	
	function save()
	{
		global $db;
		
		$this->filename = $this->data['title'] . '.jpg';
		$this->filepath = 'data/art/full/' . $this->data['art_id'] . '.jpg';
		
		$db->sql_query('UPDATE _art SET downloads = downloads + 1 WHERE art_id = ' . (int) $this->data['art_id']);
		
		$this->dl_file();
	}
	
	function fav()
	{
		global $user;
		
		if (!$user->data['is_member'])
		{
			do_login();
		}
		
		$sql = 'SELECT *
			FROM _art_fav
			WHERE art_id = ' . (int) $this->data['art_id'] . '
				AND member_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$db->sql_freeresult($result);
			$db->sql_query('UPDATE _art_fav SET fav_date = ' . time() . ' WHERE art_id = ' . (int) $this->data['art_id']);
		}
		else
		{
			$insert = array('art_id' => (int) $this->data['art_id'], 'member_id' => (int) $user->data['user_id'], 'fav_date' => time());
			$db->sql_query('INSERT INTO _art_fav ' . $db->sql_build_array('INSERT', $insert));
		}
		
		redirect(s_link('art', $this->data['art_id']));
	}
} // END CLASS

?>