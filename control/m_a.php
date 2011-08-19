<?php
// -------------------------------------------------------------
// $Id: m_a.php,v 1.9 2006/02/06 07:56:08 Psychopsia Exp $
//
// FILENAME  : m_.php
// STARTED   : Sat Dec 18, 2005
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

class a extends common
{
	var $data = array();
	var $methods = array(
		'news' => array('add', 'edit', 'delete'),
		'aposts' => array('edit', 'delete'),
		//'log' => array('view', 'delete'),
		'auth' => array('add', 'delete'),
		'gallery' => array('add', 'edit', 'delete', 'footer'),
		'biography' => array('edit'),
		//'lyrics' => array('add', 'edit', 'delete'),
		'stats' => array(),
		'video' => array('add', 'delete'),
		//'voters' => array('view'),
		//'downloads' => array('add', 'edit', 'delete'),
		//'dposts' => array('edit', 'delete')
	);
	var $comments;
	
	function a()
	{
		require('./interfase/comments.php');
		$this->comments = new _comments();
				
		return;
	}
	
	function setup()
	{
		global $db, $user;
		
		$a = $this->control->get_var('a', '');
		if (empty($a))
		{
			return false;
		}
		
		$sql = "SELECT *
			FROM _artists
			WHERE subdomain = '" . $db->sql_escape($a) . "'";
		$result = $db->sql_query($sql);
		
		if (!$a_data = $db->sql_fetchrow($result))
		{
			return false;
		}
		$db->sql_freeresult($result);
		
		if ($user->data['user_type'] == USER_ARTIST)
		{
			$sql = 'SELECT *
				FROM _artists_auth
				WHERE ub = ' . (int) $a_data['ub'] . '
					AND user_id = ' . (int) $user->data['user_id'];
			$auth_result = $db->sql_query($sql);
			
			if (!$auth_row = $db->sql_fetchrow($auth_result))
			{
				fatal_error();
			}
			$db->sql_freeresult($auth_result);
		}
		
		$this->data = $a_data;
		return true;
	}
	
	function nav()
	{
		$this->control->set_nav(array('a' => $this->data['subdomain']), $this->data['name']);
		
		if ($this->mode != 'home')
		{
			global $user;
			
			$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => $this->mode), $user->lang['CONTROL_A_' . strtoupper($this->mode)]);
		}
	}
	
	function home()
	{
		global $db, $user, $template;
		
		if ($this->setup())
		{
			$template->assign_block_vars('menu', array());
			foreach ($this->methods as $module => $void)
			{
				$template->assign_block_vars('menu.item', array(
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $module)),
					'NAME' => $user->lang['CONTROL_A_' . strtoupper($module)])
				);
			}
			
			$this->nav();
		}
		else
		{
			$sql_where = '';
			if ($user->data['user_type'] == USER_ARTIST)
			{
				$sql = 'SELECT a.ub
					FROM _artists_auth au, _artists a, _members m
					WHERE au.ub = a.ub
						AND au.user_id = m.user_id
						AND m.user_id = ' . (int) $user->data['user_id'] . '
						AND m.user_type = ' . USER_ARTIST . '
					ORDER BY m.username';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$mod_ary = array();
					do
					{
						$mod_ary[] = $row['ub'];
					}
					while ($row = $db->sql_fetchrow($result));
					
					$sql_where = 'WHERE ub IN (' . implode(',', array_map('intval', $mod_ary)) . ')';
				}
				$db->sql_freeresult($result);
			}
			
			$sql = 'SELECT *
				FROM _artists
				' . $sql_where . '
				ORDER BY name';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$selected_artists = array();
				do
				{
					$selected_artists[$row['ub']] = $row;
				}
				while ($row = $db->sql_fetchrow($result));
				$db->sql_freeresult($result);
				
				//
				// Get artists images
				//
				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub IN (' . implode(',', array_keys($selected_artists)) . ')
					ORDER BY RAND()';
				$result = $db->sql_query($sql);
				
				$random_images = array();
				while ($row = $db->sql_fetchrow($result))
				{
					if (!isset($random_images[$row['ub']]))
					{
						$random_images[$row['ub']] = $row['image'];
					}
				}
				$db->sql_freeresult($result);
				
				$template->assign_block_vars('select_a', array());
				
				$tcol = 0;
				foreach ($selected_artists as $ub => $data)
				{
					$image = ($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif';
					
					if (!$tcol)
					{
						$template->assign_block_vars('select_a.row', array());
					}
					
					$template->assign_block_vars('select_a.row.col', array(
						'NAME' => $data['name'],
						'IMAGE' => SDATA . 'artists/' . $image,
						'URL' => s_link_control('a', array('a' => $data['subdomain'])),
						'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
						'GENRE' => $data['genre'])
					);
					
					$tcol = ($tcol == 4) ? 0 : $tcol + 1;
				}
			}
			// $db->sql_freeresult($result);
		}
		
		return;
	}
	
	//
	// News
	//
	function news()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _news_home()
	{
		global $template;
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add');
		
		$template->assign_vars(array(
			'S_HIDDEN' => s_hidden($s_hidden))
		);
		
		return;
	}
	
	function _news_add()
	{
		$submit = isset($_POST['submit']) ? TRUE : FALSE;
		
		if (!$submit)
		{
			redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'news')));
		}
		
		global $db, $user, $config, $template;
		
		$post_title = $this->control->get_var('title', '');
		$message = $this->control->get_var('message', '', true);
		$current_time = time();
		$error = array();
		
		// Check subject
		if (empty($post_title))
		{
			$error[] = 'EMPTY_SUBJECT';
		}
		
		// Check message
		if (empty($message))
		{
			$error[] = 'EMPTY_MESSAGE';
		}
		elseif (/*preg_match('#(\.){1,}#i', $message) || */(strlen($message) < 10))
		{
			$error[] = 'EMPTY_MESSAGE';
		}
		
		// Flood
		if (!sizeof($error))
		{
			$sql = 'SELECT MAX(post_time) AS last_post_time
				FROM _forum_posts
				WHERE poster_id = ' . $user->data['user_id'];
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				if (intval($row['last_post_time']) > 0 && ($current_time - intval($row['last_post_time'])) < intval($config['flood_interval']))
				{
					$error[] = 'FLOOD_ERROR';
				}
			}
			$db->sql_freeresult($result);
		}
		
		if (!sizeof($error))
		{
			$message = $this->comments->prepare($message);
			
			$insert_data['TOPIC'] = array(
				'forum_id' => (int) $config['ub_fans_f'],
				'topic_ub' => $this->data['ub'],
				'topic_title' => $this->data['name'] . ': ' . $post_title,
				'topic_poster' => (int) $user->data['user_id'],
				'topic_time' => (int) $current_time,
				'topic_locked' => 0,
				'topic_important' => 0,
				'topic_vote' => 0
			);
			$db->sql_query('INSERT INTO _forum_topics' . $db->sql_build_array('INSERT', $insert_data['TOPIC']));
			$topic_id = $db->sql_nextid();
			
			$insert_data['POST'] = array(
				'topic_id' => (int) $topic_id,
				'forum_id' => (int) $config['ub_fans_f'],
				'poster_id' => (int) $user->data['user_id'],
				'post_time' => (int) $current_time,
				'poster_ip' => $user->ip,
				'post_text' => $message
			);
			$db->sql_query('INSERT INTO _forum_posts' . $db->sql_build_array('INSERT', $insert_data['POST']));
			$post_id = $db->sql_nextid();
			
			$sql = 'UPDATE _forums
				SET forum_posts = forum_posts + 1, forum_topics = forum_topics + 1, forum_last_topic_id = ' . $topic_id . '
				WHERE forum_id = ' . $config['ub_fans_f'];
			$db->sql_query($sql);
			
			$sql = 'UPDATE _forum_topics
				SET topic_first_post_id = ' . $post_id . ', topic_last_post_id = ' . $post_id . '
				WHERE topic_id = ' . $topic_id;
			$db->sql_query($sql);
			
			$sql = 'UPDATE _members
				SET user_posts = user_posts + 1
				WHERE user_id = ' . $user->data['user_id'];
			$db->sql_query($sql);
			
			$sql = 'UPDATE _artists
				SET news = news + 1
				WHERE ub = ' . (int) $this->data['ub'];
			$db->sql_query($sql);
			
			topic_feature($topic_id, 0);
			set_config('max_posts', $config['max_posts'] + 1);
			$user->save_unread(UH_N, $topic_id);
			
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		if (sizeof($error))
		{
			$template->assign_block_vars('error', array(
				'MESSAGE' => parse_error($error))
			);
		}
		
		$template->assign_vars(array(
			'TOPIC_TITLE' => $post_title,
			'MESSAGE' => $message)
		);
		
		return;
	}
	
	function _news_edit()
	{
		global $db, $user, $config, $template;
		
		$submit = isset($_POST['submit']) ? TRUE : FALSE;
		$id = $this->control->get_var('id', 0);
		if (!$id)
		{
			fatal_error();
		}
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ' . (int) $id . '
				AND forum_id = ' . (int) $config['ub_fans_f'] . '
				AND topic_ub = ' . (int) $this->data['ub'];
		$result = $db->sql_query($sql);
		
		if (!$nsdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$sql = 'SELECT *
			FROM _forum_posts
			WHERE post_id = ' . (int) $nsdata['topic_first_post_id'] . '
				AND topic_id = ' . (int) $nsdata['topic_id'] . '
				AND forum_id = ' . (int) $nsdata['forum_id'];
		$result = $db->sql_query($sql);
		
		if (!$nsdata2 = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$post_title = preg_replace('#(.*?): (.*?)#', '\\2', $nsdata['topic_title']);
		$message = $nsdata2['post_text'];
		
		if ($submit)
		{
			$post_title = $this->control->get_var('title', '');
			$message = $this->control->get_var('message', '', true);
			$current_time = time();
			$error = array();
			
			// Check subject
			if (empty($post_title))
			{
				$error[] = 'EMPTY_SUBJECT';
			}
			
			// Check message
			if (empty($message))
			{
				$error[] = 'EMPTY_MESSAGE';
			}
			
			if (!sizeof($error))
			{
				$message = $this->comments->prepare($message);
				if ($message != $nsdata2['post_text'])
				{
					$update_data = array(
						'TOPIC' => array(
							'topic_title' => $this->data['name'] . ': ' . $post_title,
							'topic_time' => (int) $current_time
						),
						'POST' => array(
							'post_time' => (int) $current_time,
							'poster_ip' => $user->ip,
							'post_text' => $message
						)
					);
					
					$db->sql_query('UPDATE _forum_topics SET ' . $db->sql_build_array('UPDATE', $update_data['TOPIC']) . ' WHERE topic_id = ' . (int) $nsdata['topic_id']);
					$db->sql_query('UPDATE _forum_posts SET ' . $db->sql_build_array('UPDATE', $update_data['POST']) . ' WHERE post_id = ' . (int) $nsdata['topic_first_post_id']);
					
					$user->save_unread(UH_N, $nsdata['topic_id']);
				}
				
				redirect(s_link('a', $this->data['subdomain']));
			}
			
			if (sizeof($error))
			{
				$template->assign_block_vars('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $nsdata['topic_id']), 'A_NEWS_EDIT');
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $nsdata['topic_id']);
		
		$template->assign_vars(array(
			'TOPIC_TITLE' => $post_title,
			'MESSAGE' => $message,
			'S_HIDDEN' => s_hidden($s_hidden))
		);
		
		return;
	}
	
	function _news_delete()
	{
		if (isset($_POST['cancel']))
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		global $db, $config, $user;
		
		$id = $this->control->get_var('id', 0);
		
		if (!$id)
		{
			fatal_error();
		}
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ' . (int) $id . '
				AND forum_id = ' . (int) $config['ub_fans_f'] . '
				AND topic_ub = ' . (int) $this->data['ub'];
		$result = $db->sql_query($sql);
		
		if (!$nsdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		if (isset($_POST['confirm']))
		{
			include('./interfase/functions_admin.php');
			
			$sql_a = array();
			
			$sql = 'SELECT poster_id, COUNT(post_id) AS posts
				FROM _forum_posts
				WHERE topic_id = ' . (int) $id . '
				GROUP BY poster_id';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				$sql_a[] = 'UPDATE _members SET user_posts = user_posts - ' . (int) $row['posts'] . '
					WHERE user_id = ' . (int) $row['poster_id'];
			}
			$db->sql_freeresult($result);
			
			$sql_a += array(
				'DELETE FROM _forum_topics WHERE topic_id = ' . (int) $id,
				'DELETE FROM _forum_posts WHERE topic_id = ' . (int) $id,
				'DELETE FROM _forum_topics_fav WHERE topic_id = ' . (int) $id,
				'UPDATE _artists SET news = news - 1 WHERE ub = ' . (int) $this->data['ub']
			);
			$db->sql_query($sql_a);
			
			sync('forum', $config['ub_fans_f']);
			set_config('max_posts', $config['max_posts'] - 1);
			$user->delete_all_unread(UH_N, $id);
			
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		//
		// Show confirm dialog
		//
		global $template;
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $nsdata['topic_id']);
		
		$template->assign_vars(array(
			'MESSAGE_TEXT' => $user->lang['CONTROL_A_NEWS_DELETE'] . '<br /><br /><h1>' . $nsdata['topic_title'] . '</h1>',

			'S_CONFIRM_ACTION' => s_link('control'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden))
		);
		
		//
		// Output to template
		//
		page_layout('CONTROL_A_NEWS', 'confirm_body');
	}
	
	//
	// A Posts
	//
	function aposts()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		if ($this->manage == 'home')
		{
			//die('home: artists > aposts @ ! link');
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _aposts_edit()
	{
		global $db, $user, $config, $template;
		
		$submit = isset($_POST['submit']) ? TRUE : FALSE;
		
		$id = $this->control->get_var('id', 0);
		
		if (!$id)
		{
			fatal_error();
		}
		
		$sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists_posts p, _members m
			WHERE p.post_id = ' . (int) $id . '
				AND p.post_ub = ' . (int) $this->data['ub'] . '
				AND p.poster_id = m.user_id';
		$result = $db->sql_query($sql);
		
		if (!$pdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$message = $pdata['post_text'];
		
		if ($submit)
		{
			$message = $this->control->get_var('message', '', true);
			$error = array();
			
			// Check message
			if (empty($message))
			{
				$error[] = 'EMPTY_MESSAGE';
			}
			
			if (!sizeof($error))
			{
				$message = $this->comments->prepare($message);
				
				$sql = "UPDATE _artists_posts
					SET post_text = '" . $db->sql_escape($message) . "'
					WHERE post_id = " . (int) $pdata['post_id'];
				$db->sql_query($sql);
				
				redirect(s_link('a', array($this->data['subdomain'], 12, $pdata['post_id'])));
			}
			
			if (sizeof($error))
			{
				$template->assign_block_vars('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']), 'A_NEWS_EDIT');
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']);
		
		$template->assign_vars(array(
			'P_MEMBER' => ($pdata['user_id'] != GUEST) ? $pdata['username'] : (($pdata['post_username'] != '') ? $pdata['post_username'] : $user->lang['GUEST']),
			'MESSAGE' => $message,
			'S_HIDDEN' => s_hidden($s_hidden))
		);
		
		return;
	}
	
	function _aposts_delete()
	{
		if (isset($_POST['cancel']))
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		global $db, $config, $user;
		
		$id = $this->control->get_var('id', 0);
		
		if (!$id)
		{
			fatal_error();
		}
		
		$delete_forever = (isset($_POST['delete_forever'])) ? TRUE : FALSE;
		
		$sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists_posts p, _members m
			WHERE p.post_id = ' . (int) $id . '
				AND p.post_ub = ' . (int) $this->data['ub'] . '
				AND p.poster_id = m.user_id';
		$result = $db->sql_query($sql);
		
		if (!$pdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		if (isset($_POST['confirm']))
		{
			$delete_forever = ($user->data['is_founder'] && $delete_forever) ? TRUE : FALSE;
			
			if ($delete_forever)
			{
				$db->sql_query('DELETE FROM _artists_posts WHERE post_id = ' . (int) $id);
			}
			else
			{
				$db->sql_query('UPDATE _artists_posts SET post_active = 0 WHERE post_id = ' . (int) $id);
				
				// LOG THIS ACTION: $this->control->log
			}
			
			$db->sql_query('UPDATE _artists SET posts = posts - 1 WHERE ub = ' . (int) $this->data['ub']);
			
			$user->delete_all_unread(UH_C, $id);
			
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		//
		// Show confirm dialog
		//
		global $template;
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']);
		
		//
		// Output to template
		//
		$template_vars = array(
			'MESSAGE_TEXT' => $user->lang['CONTROL_A_APOSTS_DELETE'],

			'S_CONFIRM_ACTION' => s_link('control'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden),
			'DELETE_FOREVER' => $user->data['is_founder']
		);
		
		page_layout('CONTROL_A_APOSTS', 'confirm_body', $template_vars);
	}
	
	//
	// Log
	//
	function log()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _log_home()
	{
		global $db, $user, $template;
		
		$member = $this->control->get_var('m', 0);
		$no_results = TRUE;
		
		if ($member)
		{
			$sql = 'SELECT user_id, username, username_base, user_color
				FROM _members
				WHERE user_id = ' . (int) $member;
			$result = $db->sql_query($sql);
			
			if (!$memberdata = $db->sql_fetchrow($result))
			{
				$member = 0;
			}
			$db->sql_freeresult($result);
			
			$template->assign_vars(array(
				'USERNAME' => $memberdata['username'],
				'PROFILE' => s_link('m', $memberdata['username_base']),
				'USER_COLOR' => $memberdata['user_color'])
			);
			
			//
			// Get all logs for this member
			//
			$sql = 'SELECT *
				FROM _artists_log
				WHERE ub = ' . (int) $this->data['ub'] . '
					AND user_id = ' . (int) $member . '
				ORDER BY datetime DESC';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$no_results = FALSE;
				
				$data = array();
				do
				{
					$data['orig'][$row['module']][$row['item_id']] = $row;
				}
				while ($row = $db->sql_fetchrow($result));
				$db->sql_freeresult($result);
				
				foreach ($data['orig'] as $module => $rowdata)
				{
					$sql = '';
					switch ($module)
					{
						case UH_C:
							$sql = 'SELECT *
								FROM _artists_posts
								WHERE post_id IN (' . implode(',', array_keys($rowdata)) . ')';
							break;
						case UH_M:
							$sql = 'SELECT *
								FROM _dl_posts
								WHERE post_id IN (' . implode(',', array_keys($rowdata)) . ')';
							break;
						case UH_F;
							$sql = 'SELECT *
								FROM ';
							break;
						case UH_CF;
							break;
						case UH_WW:
							break;
						case UH_BIO:
							break;
						case UH_LY:
							$sql = 'SELECT *
								FROM _artists_lyrics
								WHERE id IN (' . implode(',', array_keys($rowdata)) . ')';
							break;
					}
					
					if ($sql != '')
					{
						$result = $db->sql_query($sql);
						
						while ($row = $db->sql_fetchrow($result))
						{
							$data['repl'][$module][$row[0]] = $row;
						}
						$db->sql_freeresult($result);
					}
					
					die(print_r($data));
				}
			}
		}
		else
		{
			$sql = 'SELECT COUNT(l.id) AS total, m.user_id, m.username, m.user_color, m.user_avatar
				FROM _artists_log l, _members m
				WHERE l.ub = ' . (int) $this->data['ub'] . '
					AND l.user_id = m.user_id
				GROUP BY m.user_id
				ORDER BY m.username';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				include('./interfase/comments.php');
				$comments = new _comments();
				
				$no_results = FALSE;
				$tcol = 0;
				
				$template->assign_block_vars('members', array());
				do
				{
					$profile = $comments->user_profile($row);
					
					if (!$tcol)
					{
						$template->assign_block_vars('members.row', array());
					}
					
					$template->assign_block_vars('members.row.col', array(
						'USER_ID' => $row['user_id'],
						'USERNAME' => $row['username'],
						'COLOR' => $row['user_color'],
						'AVATAR' => $profile['user_avatar'],
						'U_VIEWLOG' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'm' => $row['user_id'])),
						'TOTAL' => $row['total'],
						'ACTION' => $user->lang['CONTROL_A_LOG_ACTION' . (($row['total'] == 1) ? '' : 'S')])
					);
					
					$tcol = ($tcol == 3) ? 0 : $tcol + 1;
				}
				while ($row = $db->sql_fetchrow($result));
			}
			$db->sql_freeresult($result);
		}
		
		if ($no_results)
		{
			$template->assign_block_vars('no_members', array(
				'MESSAGE' => $user->lang['CONTROL_A_LOG_EMPTY'])
			);
		}
		
		$template->assign_vars(array(
			'MEMBER' => ($member) ? $memberdata['username'] : '')
		);
	}
	
	function _log_delete()
	{
		die('default: log @ delete');
	}
	
	//
	// Auth
	//
	function auth()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function __auth_table($row, $result, $check_unique = false)
	{
		global $db, $user, $template;
		
		include('./interfase/comments.php');
		$comments = new _comments();
		
		$tcol = $trow = $items = 0;
		$total = $db->sql_numrows($result);
		
		$template->assign_block_vars('members', array());
		do
		{
			$auth_profile = $comments->user_profile($row);
			
			if (!$tcol)
			{
				$template->assign_block_vars('members.row', array());
				$trow++;
			}
			
			$template->assign_block_vars('members.row.col', array(
				'USER_ID' => $auth_profile['user_id'],
				'PROFILE' => $auth_profile['profile'],
				'USERNAME' => $auth_profile['username'],
				'COLOR' => $auth_profile['user_color'],
				'AVATAR' => $auth_profile['user_avatar'],
				'DELETE' => $check_unique || ($total > 1 && $auth_profile['user_id'] != $user->data['user_id']) || ($user->data['is_founder'] && $auth_profile['user_id'] != $user->data['user_id']),
				'CHECK' => ($total == 1 && $check_unique))
			);
			
			$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			$items++;
		}
		while ($row = $db->sql_fetchrow($result));
		
		if ($trow > 1)
		{
			for ($i = 0, $end = ((4 * $trow) - $items); $i < $end; $i++)
			{
				$template->assign_block_vars('members.row.blank', array());
			}
		}
		
		return;
	}
	
	function _auth_home()
	{
		global $db, $user, $template;
		
		$results = FALSE;
		
		$sql = 'SELECT u.user_id, u.user_type, u.username, u.username_base, u.user_color, u.user_avatar
			FROM _artists_auth a, _members u
			WHERE a.ub = ' . (int) $this->data['ub'] . '
				AND a.user_id = u.user_id
			ORDER BY u.username';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$results = TRUE;
			$this->__auth_table($row, $result);
		}
		else
		{
			$template->assign_block_vars('no_members', array(
				'MESSAGE' => $user->lang['CONTROL_A_AUTH_NOMEMBERS'])
			);
		}
		$db->sql_freeresult($result);
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'delete');
		
		$template->assign_vars(array(
			'S_HIDDEN' => s_hidden($s_hidden),
			'RESULTS' => $results,
			'ADD_MEMBER_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
	}
	
	function _auth_add()
	{
		global $db, $config, $user, $template;
		
		$submit = isset($_POST['submit']) ? TRUE : FALSE;
		$no_results = TRUE;
		
		if ($submit)
		{
			$s_members = $this->control->get_var('s_members', array(0));
			$s_member = $this->control->get_var('s_member', '');
			
			if (sizeof($s_members))
			{
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_id IN (' . implode(',', $s_members) . ')
					AND user_type NOT IN (' . USER_IGNORE . ((!$user->data['is_founder']) ? ', ' . USER_FOUNDER : '') . ')';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$s_members = array();
					$s_members_a = array();
					$s_members_i = array();
					
					do
					{
						$s_members[] = $row['user_id'];
					}
					while ($row = $db->sql_fetchrow($result));
					
					$db->sql_freeresult($result);
					
					$sql = 'SELECT user_id
						FROM _artists_auth
						WHERE ub = ' . (int) $this->data['ub'];
					$result = $db->sql_query($sql);
					
					while ($row = $db->sql_fetchrow($result))
					{
						$s_members_a[$row['user_id']] = TRUE;
					}
					$db->sql_freeresult($result);
					
					foreach ($s_members as $m)
					{
						if (!isset($s_members_a[$m]))
						{
							$s_members_i[] = $m;
						}
					}
					
					if (sizeof($s_members_i))
					{
						$sql = 'SELECT user_id, user_color, user_rank
							FROM _members
							WHERE user_id IN (' . implode(',', $s_members_i) . ')';
						$result = $db->sql_query($sql);
						
						$sd_members = array();
						while ($row = $db->sql_fetchrow($result))
						{
							$sd_members[$row['user_id']] = $row;
						}
						$db->sql_freeresult($result);
						
						foreach ($s_members_i as $m)
						{
							$db->sql_query('INSERT INTO _artists_auth (ub, user_id) VALUES (' . $this->data['ub'] . ', ' . $m .')');
						}
						
						foreach ($sd_members as $user_id => $item)
						{
							$update = array(
								'user_type' => USER_ARTIST,
								'user_auth_control' => 1
							);
							
							if ($item['user_color'] == '4D5358')
							{
								$update['user_color'] = '3DB5C2';
							}
							
							if (!$item['user_rank'])
							{
								$update['user_rank'] = (int) $config['default_a_rank'];
							}
							
							$sql = 'UPDATE _members SET ' . $db->sql_build_array('UPDATE', $update) . '
								WHERE user_id = ' . (int) $user_id . '
									AND user_type NOT IN (' . USER_INACTIVE . ', ' . USER_IGNORE . ', ' . USER_FOUNDER . ')';
							$db->sql_query($sql);
							
							$sql = 'SELECT fan_id
								FROM _artists_fav
								WHERE ub = ' . (int) $this->data['ub'] . '
									AND user_id = ' . (int) $user_id;
							$result = $db->sql_query($sql);
							
							if ($row = $db->sql_fetchrow($result))
							{
								$sql = 'DELETE FROM _artists_fav
									WHERE fan_id = ' . (int) $row['fan_id'];
								$db->sql_query($sql);
							}
							$db->sql_freeresult($result);
						}
						
						//
						// Back to auth home
						//
						redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
					}
				}
				
				$s_member = '';
				
				$template->assign_block_vars('no_members', array(
					'MESSAGE' => $user->lang['CONTROL_A_AUTH_ADD_NOMATCH'])
				);
			}
			
			if (!empty($s_member))
			{
				if ($s_member == '*')
				{
					$s_member = '';
				}
				
				if (preg_match_all('#\*#', $s_member, $st) > 1)
				{
					$s_member = str_replace('*', '', $s_member);
				}
			}
			
			if (!empty($s_member))
			{
				$s_member = phpbb_clean_username(str_replace('*', '%', $s_member));
				
				$sql = 'SELECT user_id
					FROM _artists_auth
					WHERE ub = ' . (int) $this->data['ub'];
				$result = $db->sql_query($sql);
				
				$s_auth = array(GUEST);
				while ($row = $db->sql_fetchrow($result))
				{
					$s_auth[] = $row['user_id'];
				}
				$db->sql_freeresult($result);
				
				$sql = "SELECT user_id, user_type, username, username_base, user_color, user_avatar
					FROM _members
					WHERE username LIKE '" . $db->sql_escape($s_member) . "'
						AND user_id NOT IN (" . implode(',', $s_auth) . ")
						AND user_type NOT IN (" . USER_IGNORE . ((!$user->data['is_founder']) ? ", " . USER_FOUNDER : '') . ")
					ORDER BY username";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					if ($db->sql_numrows($result) < 11)
					{
						$this->__auth_table($row, $result, true);
						$no_results = FALSE;
					}
					else
					{
						$template->assign_block_vars('no_members', array(
							'MESSAGE' => $user->lang['CONTROL_A_AUTH_ADD_TOOMUCH'])
						);
					}
				}
				else
				{
					$template->assign_block_vars('no_members', array(
						'MESSAGE' => $user->lang['CONTROL_A_AUTH_ADD_NOMATCH'])
					);
				}
				$db->sql_freeresult($result);
			} // IF !EMPTY
		}
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage);
		
		if ($submit && !$no_results)
		{
			$s_hidden['s_member'] = str_replace('%', '*', $s_member);
		}
		
		//
		// Output to template
		//
		$template->assign_vars(array(
			'SHOW_INPUT' => !$submit || $no_results,
			'S_HIDDEN' => s_hidden($s_hidden),
			'ADD_MEMBER_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
	}
	
	function _auth_delete()
	{
		$auth_url = s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode));
		
		if (isset($_POST['cancel']))
		{
			redirect($auth_url);
		}
		
		$submit = isset($_POST['submit']) ? TRUE : FALSE;
		$confirm = isset($_POST['confirm']) ? TRUE : FALSE;
		
		if ($submit || $confirm)
		{
			global $db, $config, $user, $template;
			
			$s_members = $this->control->get_var('s_members', array(0));
			$s_members_i = array();
			
			if (sizeof($s_members))
			{
				$sql = 'SELECT user_id
					FROM _artists_auth
					WHERE ub = ' . (int) $this->data['ub'];
				$result = $db->sql_query($sql);
				
				$s_auth = array();
				while ($row = $db->sql_fetchrow($result))
				{
					$s_auth[$row['user_id']] = TRUE;
				}
				$db->sql_freeresult($result);
				
				foreach ($s_members as $m)
				{
					if (isset($s_auth[$m]))
					{
						$s_members_i[] = $m;
					}
				}
			}
			
			if (!sizeof($s_members_i))
			{
				redirect($auth_url);
			}
			
			//
			// Check inputted members
			//
			$sql = 'SELECT user_id, username, user_color, user_rank
				FROM _members
				WHERE user_id IN (' . implode(',', $s_members_i) . ')
					AND user_id <> ' . (int) $user->data['user_id'] . '
					AND user_type NOT IN (' . USER_IGNORE . ')
				ORDER BY user_id';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$s_members = array();
				do
				{
					$s_members[] = $row;
				}
				while ($row = $db->sql_fetchrow($result));
				$db->sql_freeresult($result);
			}
			else
			{
				redirect($auth_url);
			}
			
			//
			// Confirm
			//
			if ($confirm)
			{
				foreach ($s_members as $item)
				{
					$update = array();
					
					if (!in_array($item['user_id'], array(2, 3)))
					{
						$keep_control = true;
						
						$sql = 'SELECT ub
							FROM _artists_auth
							WHERE user_id = ' . (int) $item['user_id'];
						$result = $db->sql_query($sql);
						
						if ($db->sql_numrows($results) == 1)
						{
							$keep_control = false;
						}
						$db->sql_freeresult($result);
						
						$user_type = USER_ARTIST;
						if (!$keep_control)
						{
							$user_type = USER_NORMAL;
							if ($item['user_color'] == '492064')
							{
								$update['user_color'] = '4D5358';
							}
							if ($item['user_rank'] == $config['default_a_rank'])
							{
								$update['user_rank'] = 0;
							}
							
							$sql = 'SELECT *
								FROM _artists_fav
								WHERE user_id = ' . (int) $item['user_id'];
							$result = $db->sql_query($sql);
							
							if ($row = $db->sql_fetchrow($result))
							{
								$user_type = USER_FAN;
							}
							$db->sql_freeresult($result);
						}
						
						$update['user_auth_control'] = $keep_control;
						$update['user_type'] = $user_type;
					}
					
					if (sizeof($update))
					{
						$sql = 'UPDATE _members SET ' . $db->sql_build_array('UPDATE', $update) . ' WHERE user_id = ' . (int) $item['user_id'];
						$db->sql_query($sql);
					}
					
					$sql = 'DELETE FROM _artists_auth
						WHERE ub = ' . (int) $this->data['ub'] . '
							AND user_id = ' . (int) $item['user_id'];
					$db->sql_query($sql);
				}
				
				redirect($auth_url);
			}
			
			//
			// Display confirm dialog
			//
			$s_members_list = '';
			$s_members_hidden = s_hidden(array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage));
			foreach ($s_members as $data)
			{
				$s_members_list .= (($s_members_list != '') ? ', ' : '') . '<strong style="color:#' . $data['user_color'] . '; font-weight: bold">' . $data['username'] . '</strong>';
				$s_members_hidden .= s_hidden(array('s_members[]' => $data['user_id']));
			}
			
			$template_vars = array(
				'MESSAGE_TEXT' => sprintf($user->lang[((sizeof($s_members) == 1) ? 'CONTROL_A_AUTH_DELETE2' : 'CONTROL_A_AUTH_DELETE')], $this->data['name'], $s_members_list),
				'S_CONFIRM_ACTION' => s_link('control'),
				'S_HIDDEN_FIELDS' => $s_members_hidden
			);
			
			//
			// Output to template
			//
			page_layout('CONTROL_A_AUTH', 'confirm_body', $template_vars);
		}
		
		redirect($auth_url);
	}
	
	//
	// Gallery
	//
	function gallery()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _gallery_home()
	{
		global $db, $user, $template;
		
		$sql = 'SELECT g.*
			FROM _artists a, _artists_images g
			WHERE a.ub = ' . $this->data['ub'] . '
				AND a.ub = g.ub
			ORDER BY image ASC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('gallery', array());
			
			$tcol = 0;
			do
			{
				if (!$tcol)
				{
					$template->assign_block_vars('gallery.row', array());
				}
				
				$template->assign_block_vars('gallery.row.col', array(
					'ITEM' => $row['image'],
					'URL' => s_link('a', array($this->data['subdomain'], 4, $row['image'], 'view')),
					'U_FOOTER' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'footer', 'image' => $row['image'])),
					'IMAGE' => SDATA . 'artists/' . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
					'RIMAGE' => get_a_imagepath(SDATA . 'artists/' . $this->data['ub'], $row['image'] . '.jpg', array('x1', 'gallery')),
					'WIDTH' => $row['width'], 
					'HEIGHT' => $row['height'],
					'TFOOTER' => $row['image_footer'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'])
				);
				
				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
			while ($row = $db->sql_fetchrow($result));
			$db->sql_freeresult($result);
		}
		else
		{
			$template->assign_block_vars('empty', array(
				'MESSAGE' => $user->lang['CONTROL_A_GALLERY_EMPTY'])
			);
		}
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'delete');
		
		$template->assign_vars(array(
			'S_HIDDEN' => s_hidden($s_hidden),
			'ADD_IMAGE_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
	}
	
	function __gallery_add_chmod($ary, $perm)
	{
		foreach ($ary as $cdir)
		{
			@chmod($cdir, $perm);
		}
	}
	
	function __gallery_add_delete($filename)
	{
		if (!@is_writable($filename))
		{
			//@chmod($filename, 0777);
		}
		@unlink($filename);
	}
	
	function _gallery_add()
	{
		global $db, $user, $template;
		
		$filesize = 3000 * 1024;
		if (isset($_POST['submit']) && isset($_FILES['add_image']))
		{
			require('./interfase/upload.php');
			$upload = new upload();
			
			$filepath = '..' . SDATA . 'artists/' . $this->data['ub'] . '/';
			$filepath_1 = $filepath . 'x1/';
			$filepath_2 = $filepath . 'gallery/';
			$filepath_3 = $filepath . 'thumbnails/';
			
			$f = $upload->process($filepath_1, $_FILES['add_image'], array('jpg'), $filesize);
			
			if (!sizeof($upload->error) && $f !== false)
			{
				$sql = 'SELECT MAX(image) AS total
					FROM _artists_images
					WHERE ub = ' . (int) $this->data['ub'];
				$result = $db->sql_query($sql);
				
				$img = 0;
				if ($row = $db->sql_fetchrow($result))
				{
					$img = $row['total'];
				}
				$db->sql_freeresult($result);
				
				$a = 0;
				foreach ($f as $row)
				{
					$img++;
					
					$xa = $upload->resize($row, $filepath_1, $filepath_1, $img, array(600, 400), false, false, true);
					if ($xa === false)
					{
						continue;
					}
					
					$xb = $upload->resize($row, $filepath_1, $filepath_2, $img, array(300, 225), false, false);
					$xc = $upload->resize($row, $filepath_2, $filepath_3, $img, array(100, 75), false, false);
					
					$insert = array(
						'ub' => (int) $this->data['ub'],
						'image' => (int) $img,
						'width' => $xa['width'],
						'height' => $xa['height']
					);
					$sql = 'INSERT INTO _artists_images' . $db->sql_build_array('INSERT', $insert);
					$db->sql_query($sql);
					
					$a++;
				}
				
				if ($a)
				{
					$sql = 'UPDATE _artists SET images = images + ' . (int) $a . '
						WHERE ub = ' . (int) $this->data['ub'];
					$db->sql_query($sql);
				}
				
				redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
			}
			else
			{
				$template->assign_block_vars('error', array(
					'MESSAGE' => parse_error($upload->error))
				);
			}
		}
		
		$template->assign_vars(array(
			'S_HIDDEN' => s_hidden(array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage)),
			'MAX_FILESIZE' => $filesize)
		);
		
		return;
	}
	
	function _gallery_edit()
	{
		
	}
	
	function _gallery_delete()
	{
		global $db, $user, $template;
		
		$error = false;
		if (isset($_POST['submit']))
		{
			$s_images = $this->control->get_var('ls_images', array(0));
			if (sizeof($s_images))
			{
				$affected = array();
				
				$common_path = './..' . SDATA . 'artists/' . $this->data['ub'] . '/';
				$path = array(
					$common_path . 'x1/',
					$common_path . 'gallery/',
					$common_path . 'thumbnails/',
				);
				
				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub = ' . (int) $this->data['ub'] . '
						AND image IN (' . implode(',', $s_images) . ')
					ORDER BY image';
				$result = $db->sql_query($sql);
				
				while ($row = $db->sql_fetchrow($result))
				{
					foreach ($path as $path_row)
					{
						$filepath = $path_row . $row['image'] . '.jpg';
						@unlink($filepath);
					}
					$affected[] = $row['image'];
				}
				$db->sql_freeresult($result);
				
				if (count($affected))
				{
					$sql = 'DELETE FROM _artists_images 
						WHERE ub = ' . (int) $this->data['ub'] . '
							AND image IN (' . implode(',', $affected) . ')';
					$db->sql_query($sql);
					
					$sql = 'UPDATE _artists
						SET images = images - ' . $db->sql_affectedrows() . '
						WHERE ub = ' . (int) $this->data['ub'];
					$db->sql_query($sql);
				}
			}
		}
		
		redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
	}
	
	function _gallery_footer()
	{
		global $db, $user, $template;
		
		$a = $this->control->get_var('image', '');
		$t = $this->control->get_var('value', '');
		
		$sql = 'SELECT *
			FROM _artists_images
			WHERE ub = ' . (int) $this->data['ub'] . '
				AND image = ' . (int) $a;
		$result = $db->sql_query($sql);
		
		if (!$row = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$sql = "UPDATE _artists_images
			SET image_footer = '" . $db->sql_escape($t) . "'
			WHERE ub = " . (int) $this->data['ub'] . '
				AND image = ' . (int) $a;
		$db->sql_query($sql);
		
		$this->e($t);
	}
	
	//
	// Biography
	//
	function biography()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _biography_home()
	{
		global $db, $user, $template;
		
		$sql = 'SELECT bio
			FROM _artists
			WHERE ub = ' . (int) $this->data['ub'];
		$result = $db->sql_query($sql);
		
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'edit');
		
		$template->assign_vars(array(
			'MESSAGE' => $row['bio'],
			'S_HIDDEN' => s_hidden($s_hidden))
		);
		
		if ($this->control->get_var('s', '') == 'u')
		{
			$template->assign_block_vars('updated', array());
		}
	}
	
	function _biography_edit()
	{
		global $db, $user;
		
		if (isset($_POST['submit']))
		{
			$message = $this->control->get_var('message', '', true);
			$message = $this->comments->prepare($message);
			
			$sql = "UPDATE _artists
				SET bio = '" . $db->sql_escape($message) . "'
				WHERE ub = " . (int) $this->data['ub'];
			$db->sql_query($sql);
		}
		
		redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 's' => 'u')));
	}
	
	//
	// Lyrics
	//
	function lyrics()
	{
		$this->call_method();
	}
	
	function _lyrics_add()
	{
		
	}
	
	function _lyrics_edit()
	{
		
	}
	
	function _lyrics_delete()
	{
		
	}
	
	//
	// Video
	//
	function video()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _video_home()
	{
		global $db, $user, $template;
		
		$sql = 'SELECT *
			FROM _artists_video
			WHERE video_a = ' . (int) $this->data['ub'] . '
			ORDER BY video_added DESC';
		$result = $db->sql_query($sql);
		
		$video = 0;
		while ($row = $db->sql_fetchrow($result))
		{
			if (!$video)
			{
				$template->assign_block_vars('video', array());
			}
			$template->assign_block_vars('video.row', array(
				'CODE' => $row['video_code'],
				'TIME' => $user->format_date($row['video_added']))
			);
			
			$video++;
		}
		$db->sql_freeresult($result);
		
		$template->assign_vars(array(
			'ADD_VIDEO_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
		
		return;
	}
	
	function _video_add()
	{
		global $db, $user, $template;
		
		if (isset($_POST['submit']))
		{
			$code = $this->control->get_var('code', '', true);
			$vname = $this->control->get_var('vname', '');
			
			if (!empty($code))
			{
				$sql = "SELECT *
					FROM _artists_video
					WHERE video_a = " . (int) $this->data['ub'] . "
						AND video_code = '" . $db->sql_escape($code) . "'";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$code = '';
				}
				$db->sql_freeresult($result);
			}
			
			if (!empty($code))
			{
				$code = get_yt_code($code);
			}
			
			if (!empty($code))
			{
				$insert = array(
					'video_a' => $this->data['ub'],
					'video_name' => $vname,
					'video_code' => $code,
					'video_added' => time()
				);
				$sql = 'INSERT INTO _artists_video' . $db->sql_build_array('INSERT', $insert);
				$db->sql_query($sql);
				
				$sql = 'UPDATE _artists SET a_video = a_video + 1
					WHERE ub = ' . (int) $this->data['ub'];
				$db->sql_query($sql);
			}
			
			redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
		}
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage);
		$template->assign_vars(array(
			'S_HIDDEN' => s_hidden($s_hidden))
		);
		
		return;
	}
	
	function _video_delete()
	{
		global $db, $user, $template;
	}
	
	//
	// Stats
	//
	function stats()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _stats_home()
	{
		global $db, $user, $template;
		
		$sql = 'SELECT *, SUM(members + guests) AS total
			FROM _artists_stats
			WHERE ub = ' . (int) $this->data['ub'] . '
			GROUP BY date
			ORDER BY date DESC';
		$result = $db->sql_query($sql);
		
		$stats = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$stats[$row['date']] = $row;
		}
		$db->sql_freeresult($result);
		
		$years_sum = array();
		$years_temp = array();
		$years = array();
		foreach ($stats as $date => $void)
		{
			$year = substr($date, 0, 4);
			
			if (!isset($years_temp[$year]))
			{
				$years[] = $year;
				$years_temp[$year] = TRUE;
			}
			
			if (!isset($years_sum[$year]))
			{
				$years_sum[$year] = 0;
			}
			
			$years_sum[$year] += $void['total'];
		}
		unset($years_temp);
		
		if (sizeof($years))
		{
			rsort($years);
		}
		else
		{
			$years[] = date('Y');
		}
		
		$total_graph = 0;
		foreach ($years as $year)
		{
			$template->assign_block_vars('year', array(
				'YEAR' => $year)
			);
			
			if (!isset($years_sum[$year]))
			{
				$years_sum[$year] = 0;
			}
			
			for ($i = 1; $i < 13; $i++)
			{
				$month = (($i < 10) ? '0' : '') . $i;
				$monthdata = (isset($stats[$year . $month])) ? $stats[$year . $month] : array();
				$monthdata['total'] = isset($monthdata['total']) ? $monthdata['total'] : 0;
				$monthdata['percent'] = ($years_sum[$year] > 0) ? $monthdata['total'] / $years_sum[$year] : 0;
				$monthdata['members'] = isset($monthdata['members']) ? $monthdata['members'] : 0;
				$monthdata['guests'] = isset($monthdata['guests']) ? $monthdata['guests'] : 0;
				$monthdata['unix'] = gmmktime(0, 0, 0, $i, 1, $year) - $user->timezone - $user->dst;
				$total_graph += $monthdata['total'];
				
				$template->assign_block_vars('year.month', array(
					'NAME' => $user->format_date($monthdata['unix'], 'F'),
					'TOTAL' => $monthdata['total'],
					'MEMBERS' => $monthdata['members'],
					'GUESTS' => $monthdata['guests'],
					'PERCENT' => sprintf("%.1d", ($monthdata['percent'] * 100)))
				);
			}
		}
		
		$template->assign_vars(array(
			'BEFORE_VIEWS' => number_format($this->data['views']),
			'SHOW_VIEWS_LEGEND' => ($this->data['views'] > $total_graph))
		);
		
		return;
	}
	
	//
	// Voters
	//
	function voters()
	{
		$this->call_method();
	}
	
	function _voters_home()
	{
		
	}
	
	//
	// Downloads
	//
	function downloads()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _downloads_home()
	{
		global $db, $user, $template;
		
		$sql = 'SELECT *
			FROM _dl
			WHERE ub = ' . (int) $this->data['ub'] . '
			ORDER BY title';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$downloads_type = array(
				1 => '/net/icons/browse.gif',
				2 => '/net/icons/store.gif'
			);
			
			$template->assign_block_vars('downloads', array());
			
			$tcol = 0;
			do
			{
				if (!$tcol)
				{
					$template->assign_block_vars('downloads.row', array());
				}
				
				$template->assign_block_vars('downloads.row.col', array(
					'ITEM' => $row['id'],
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'edit', 'd' => $row['id'])),
					'POSTS_URL' => s_link('a', array($this->data['subdomain'], 9, $row['id'])) . '#dpf',
					'IMAGE_TYPE' => $downloads_type[$row['ud']],
					'DOWNLOAD_TITLE' => $row['title'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'],
					'POSTS' => $row['posts'])
				);
				
				$tcol = ($tcol == 2) ? 0 : $tcol + 1;
			}
			while ($row = $db->sql_fetchrow($result));
			$db->sql_freeresult($result);
		}
		else
		{
			$template->assign_block_vars('empty', array(
				'MESSAGE' => $user->lang['CONTROL_A_DOWNLOADS_EMPTY'])
			);
		}
		
		return;
	}
	
	function _downloads_add()
	{
		
	}
	
	function _downloads_edit()
	{
		
	}
	
	function _downloads_delete()
	{
		
	}
	
	//
	// D Posts
	//
	function dposts()
	{
		if (!$this->setup())
		{
			fatal_error();
		}
		
		if ($this->manage == 'home')
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		$this->nav();
		$this->call_method();
	}
	
	function _dposts_edit()
	{
		global $db, $user, $config, $template;
		
		$submit = isset($_POST['submit']) ? TRUE : FALSE;
		
		$id = $this->control->get_var('id', 0);
		
		if (!$id)
		{
			fatal_error();
		}
		
		$sql = 'SELECT a.ub, d.*, p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists a, _dl d, _dl_posts p, _members m
			WHERE a.ub = ' . (int) $this->data['ub'] . '
				AND a.ub = d.ub
				AND d.id = p.download_id
				AND p.post_id = ' . (int) $id . '
				AND p.poster_id = m.user_id';
		$result = $db->sql_query($sql);
		
		if (!$pdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$message = $pdata['post_text'];
		
		if ($submit)
		{
			$message = $this->control->get_var('message', '', true);
			$error = array();
			
			// Check message
			if (empty($message))
			{
				$error[] = 'EMPTY_MESSAGE';
			}
			
			if (!sizeof($error))
			{
				$message = $this->comments->prepare($message);
				
				$db->sql_query("UPDATE _dl_posts SET post_text = '" . $db->sql_escape($message) . "' WHERE post_id = " . (int) $pdata['post_id']);
				
				redirect(s_link('a', array($this->data['subdomain'], 9, $pdata['download_id'])));
			}
			
			if (sizeof($error))
			{
				$template->assign_block_vars('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']), 'A_NEWS_EDIT');
		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => 'downloads', 'manage' => $this->manage, 'id' => $pdata['download_id']), $pdata['title']);
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']);
		
		$template->assign_vars(array(
			'P_MEMBER' => ($pdata['user_id'] != GUEST) ? $pdata['username'] : (($pdata['post_username'] != '') ? $pdata['post_username'] : $user->lang['GUEST']),
			'MESSAGE' => $message,
			'S_HIDDEN' => s_hidden($s_hidden))
		);
		
		return;
	}
	
	function _dposts_delete()
	{
		global $db, $config, $user;
		
		$id = $this->control->get_var('id', 0);
		
		if (!$id)
		{
			fatal_error();
		}
		
		$sql = 'SELECT a.ub, d.*, p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists a, _dl d, _dl_posts p, _members m
			WHERE a.ub = ' . (int) $this->data['ub'] . '
				AND a.ub = d.ub
				AND d.id = p.download_id
				AND p.post_id = ' . (int) $id . '
				AND p.poster_id = m.user_id';
		$result = $db->sql_query($sql);
		
		if (!$pdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		if (isset($_POST['cancel']))
		{
			redirect(s_link('a', array($this->data['subdomain'], 9, $pdata['download_id'])));
		}
		
		if (isset($_POST['confirm']))
		{
			$delete_forever = (isset($_POST['delete_forever'])) ? TRUE : FALSE;
			$delete_forever = ($user->data['is_founder'] && $delete_forever) ? TRUE : FALSE;
			
			if ($delete_forever)
			{
				$db->sql_query('DELETE FROM _dl_posts WHERE post_id = ' . (int) $id);
			}
			else
			{
				$db->sql_query('UPDATE _dl_posts SET post_active = 0 WHERE post_id = ' . (int) $id);
				
				// LOG THIS ACTION: $this->control->log
			}
			
			$db->sql_query('UPDATE _dl SET posts = posts - 1 WHERE id = ' . (int) $pdata['download_id']);
			
			$user->delete_all_unread(UH_M, $id);
			
			redirect(s_link('a', array($this->data['subdomain'], 9, $pdata['download_id'])));
		}
		
		//
		// Show confirm dialog
		//
		global $template;
		
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']);
		
		//
		// Output to template
		//
		$template_vars = array(
			'MESSAGE_TEXT' => $user->lang['CONTROL_A_APOSTS_DELETE'],

			'S_CONFIRM_ACTION' => s_link('control'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden),
			'DELETE_FOREVER' => $user->data['is_founder']
		);
		
		page_layout('CONTROL_A_APOSTS', 'confirm_body', $template_vars);
	}
	
	//
	// Art
	//
	function art()
	{
		
	}
	
	function _art_home()
	{
		
	}
	
	function _art_add()
	{
		
	}
	
	function _art_edit()
	{
		
	}
	
	function _art_delete()
	{
		
	}
	
	//
	// Art Posts
	//
	function arposts()
	{
		
	}
	
	function _arposts_edit()
	{
		
	}
	
	function _arposts_delete()
	{
		
	}
}

?>