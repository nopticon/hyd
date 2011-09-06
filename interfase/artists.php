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
if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

include(ROOT.'interfase/downloads.php');

//
// Class: layout
//
class layout extends downloads
{
	//
	// Home
	//
	function _1()
	{
		global $user, $config, $template;
		
		//
		// Gallery
		//
		if ($this->data['images'])
		{
			$simage = $this->get_images(FALSE, $this->data['ub'], TRUE);
			$imagedata = $this->images[$this->data['ub']][$simage];
			$image = $imagedata['path'];
		}
		else
		{
			if (!$this->data['um'] && !$this->data['uv'] && $this->data['bio'] == '')
			{
				$default_image = 'default.jpg';
			}
			else
			{
				$default_image = 'default2.jpg';
			}
			
			$image = 'data/artists/default/' . $default_image;
		}
		
		$template->assign_block_vars('ub_image', array(
			'IMAGE' => '/' . $image)
		);
		
		if ($this->data['images'] > 1)
		{
			$template->assign_block_vars('ub_image.view', array(
				'URL' => s_link('a', array($this->data['subdomain'], 4, $imagedata['image'], 'view')))
			);
		}
		
		//
		// News
		//
		if ($this->auth['mod'])
		{
			$template->assign_block_vars('news_create', array(
				'URL' => s_link('control', array('_' . $this->data['subdomain'], 'news')))
			);
		}
		
		if ($this->data['news'])
		{
			$this->msg->ref = s_link('a', array($this->data['subdomain'], 16));
			$this->msg->auth = $this->auth;
			
			$this->msg->data = array(
				'ARTISTS_NEWS' => TRUE,
				'A_LINKS_CLASS' => 'bold red',
				'SQL' => 'SELECT t.*, p.*, m.*
					FROM _forum_topics t, _forum_posts p, _members m
					WHERE t.forum_id = ' . (int) $config['ub_fans_f'] . '
						AND t.topic_ub = ' . (int) $this->data['ub'] . '
						AND t.topic_poster = m.user_id
						AND p.post_id = t.topic_first_post_id
						AND t.topic_important = 0
					ORDER BY t.topic_time DESC'
			);
			
			if ($this->auth['mod'])
			{
				$this->msg->data['CONTROL'] = array(
					/*'topic' => array(
						'VIEW' => array(
							'URL' => s_link('a', array($this->data['subdomain'], 16, 't%d')),
							'ID' => 'topic_id'),
						'POST' => array(
							'URL' => s_link('a', array($this->data['subdomain'], 16, 't%d')) . '#reply',
							'ID' => 'topic_id')
					),*/
					'auth' => array(
						'EDIT' => array(
							'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'news', 'manage' => 'edit', 'id' => '%d')),
							'ID' => 'topic_id'
						),
						'DELETE' => array(
							'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'news', 'manage' => 'delete', 'id' => '%d')),
							'ID' => 'topic_id'
						)
					)
				);
			}
			
			$this->msg->view(0, 'ns', $this->data['news'], $this->data['news'], 'news');
			$this->msg->reset();
		}
		
		//
		// Poll
		//
		$user_voted = FALSE;
		if ($this->auth['user'] && !$this->auth['mod'])
		{
			$sql = 'SELECT *
				FROM _artists_voters
				WHERE ub = ' . $this->data['ub'] . '
					AND user_id = ' . (int) $user->data['user_id'];
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$user_voted = TRUE;
			}
			$db->sql_freeresult($result);
		}
		
		$template->assign_block_vars('ub_poll', array());
		
		if ($this->auth['mod'] || !$this->auth['user'] || $user_voted)
		{
			$sql = 'SELECT option_id, vote_result
				FROM _artists_votes
				WHERE ub = ' . $this->data['ub'] . '
				ORDER BY option_id';
			$result = $db->sql_query($sql);
			
			$results = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$results[$row['option_id']] = $row['vote_result'];
			}
			$db->sql_freeresult($result);
			
			$template->assign_block_vars('ub_poll.results', array());
			
			foreach ($this->voting['ub'] as $item)
			{
				$vote_result = (isset($results[$item])) ? intval($results[$item]) : 0;
				$vote_percent = ($this->data['votes'] > 0) ? $vote_result / $this->data['votes'] : 0;

				$template->assign_block_vars('ub_poll.results.item', array(
					'CAPTION' => $user->lang['UB_VC' . $item],
					'RESULT' => $vote_result,
					'PERCENT' => sprintf("%.1d", ($vote_percent * 100)))
				);
			}
		}
		else
		{
			$template->assign_block_vars('ub_poll.options', array(
				'S_VOTE_ACTION' => s_link('a', array($this->data['subdomain'], 17)))
			);
			
			foreach ($this->voting['ub'] as $item)
			{
				$template->assign_block_vars('ub_poll.options.item', array(
					'ID' => $item,
					'CAPTION' => $user->lang['UB_VC' . $item])
				);
			}
		}
		
		//
		// Auth Members
		//
		if ($this->auth['mod'] || $this->data['mods_legend'])
		{
			$sql = 'SELECT b.ub, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar, u.user_avatar_type
				FROM _artists_auth a, _artists b, _members u
				WHERE a.ub = ' . (int) $this->data['ub'] . '
					AND a.ub = b.ub 
					AND a.user_id = u.user_id 
				ORDER BY u.username';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$template->assign_block_vars('mods', array());
				
				do
				{
					$user_profile = $this->msg->user_profile($row);
					
					$template->assign_block_vars('mods.item', array(
						'PROFILE' => $user_profile['profile'],
						'USERNAME' => $user_profile['username'],
						'COLOR' => $user_profile['user_color'])
					);
				}
				while ($row = $db->sql_fetchrow($result));
				$db->sql_freeresult($result);
				$this->msg->reset();
				
				if ($this->auth['mod'])
				{
					$template->assign_block_vars('mods.manage', array(
						'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'auth')))
					);
				}
			}
		}
		
		//
		// Favorites | Viewers
		//
		$last_data = array();
		
		$sql = 'SELECT v.*, u.user_id, u.username, u.username_base, u.user_color
			FROM _artists_fav v, _members u
			WHERE v.ub = ' . (int) $this->data['ub'] . '
				AND v.user_id = u.user_id 
			ORDER BY joined DESC 
			LIMIT 0, 10';
		$result = $db->sql_query($sql);
		
		$total = $db->sql_numrows($result);
		if ($total)
		{
			$last_data['favorites'] = array(
				'data' => $db->sql_fetchrowset($result),
				'total' => $total
			);
		}
		$db->sql_freeresult($result);
		
		$sql = 'SELECT v.*, u.user_id, u.username, u.username_base, u.user_color
			FROM _artists_viewers v, _members u
			WHERE v.ub = ' . (int) $this->data['ub'] . '
				AND v.user_id = u.user_id 
			ORDER BY datetime DESC';
		$result = $db->sql_query($sql);
		
		$total = $db->sql_numrows($result);
		if ($total)
		{
			$last_data['visitors'] = array(
				'data' => $db->sql_fetchrowset($result),
				'total' => $total
			);
		}
		$db->sql_freeresult($result);
		
		if (sizeof($last_data))
		{
			$template->assign_block_vars('recent_members', array());
			
			foreach ($last_data as $block => $items)
			{
				$template->assign_block_vars('recent_members.col', array(
					'COUNT' => $items['total'],
					'TITLE' => ($block == 'favorites') ? $user->lang['UB_RECENT_F'] : $user->lang['UB_RECENT_V'])
				);
				
				$datetime = ($block == 'favorites') ? 'joined' : 'datetime';
				
				foreach ($items['data'] as $item)
				{
					$template->assign_block_vars('recent_members.col.item', array(
						'DATETIME' => $user->format_date($item[$datetime]),
						'USER_COLOR' => $item['user_color'],
						'PROFILE' => s_link('m', $item['username_base']),
						'USERNAME' => $item['username'])
					);
				}
			}
		}
		
		return;
	}
	
	//
	// Biography
	//
	
	function _2()
	{
		global $template;
		
		if ($this->data['featured_image'])
		{
			$template->assign_block_vars('featured_image', array(
				'IMAGE' => SDATA . 'artists/' . $this->data['ub'] . '/gallery/' . $this->data['featured_image'] . '.jpg',
				'URL' => s_link('a', array($this->data['subdomain'], 4, $this->data['featured_image'], 'view')))
			);
		}
		
		//
		// Parse Biography
		//		
		$template->assign_vars(array(
			'UB_BIO' => $this->msg->parse_message($this->data['bio'], 'bold red'))
		);
		
		return;
	}
	
	function _3()
	{
		return;
	}
	
	//
	// Gallery
	//
	function _4()
	{
		global $config, $template;
		
		$mode = request_var('mode', '');
		$download_id = intval(request_var('download_id', 0));
		
		if ($mode == 'save' || $mode == 'view')
		{
			if (!$download_id)
			{
				redirect(s_link('a', array($this->data['subdomain'], 4)));
			}
			
			if ($mode == 'view')
			{
				$sql = 'SELECT g.*, COUNT(g2.image) AS prev_images
					FROM _artists_images g, _artists_images g2
					WHERE g.ub = ' . (int) $this->data['ub'] . '
						AND g2.ub = g.ub 
						AND g.image = ' . (int) $download_id . '
						AND g2.image <= ' . (int) $download_id . '
					GROUP BY g.image 
					ORDER BY g.image ASC';
			}
			else
			{
				$sql = 'SELECT g.*
					FROM _artists a, _artists_images g
					WHERE a.ub = ' . (int) $this->data['ub'] . '
						AND a.ub = g.ub 
						AND g.image = ' . (int) $download_id;
			}
			$result = $db->sql_query($sql);
			
			if (!$imagedata = $db->sql_fetchrow($result))
			{
				redirect(s_link('a', array($this->data['subdomain'], 4)));
			}
			$db->sql_freeresult($result);
		}
		
		switch ($mode)
		{
			case 'save':
				if (!$imagedata['allow_dl'])
				{
					redirect(s_link('a', array($this->data['subdomain'], 4, $imagedata['image'], 'view')));
				}
				
				$db->sql_query('UPDATE _artists_images SET downloads = downloads + 1 WHERE ub = ' . (int) $this->data['ub'] . ' AND image = ' . (int) $imagedata['image']);
				
				$this->filename = $this->data['name'] . '_' . $imagedata['image'] . '.jpg';
				$this->filepath = 'data/artists/' . $this->data['ub'] . '/gallery/' . $imagedata['image'] . '.jpg';
				$this->dl_file();				
				break;
			case 'view':
			default:
				if ($mode == 'view')
				{
					if (!$this->auth['mod'])
					{
						$db->sql_query('UPDATE _artists_images SET views = views + 1 WHERE ub = ' . (int) $this->data['ub'] . ' AND image = ' . (int) $imagedata['image']);
					}
					
					$template->assign_block_vars('selected', array(
						'IMAGE' => SDATA . 'artists/' . $this->data['ub'] . '/gallery/' . $imagedata['image'] . '.jpg',
						'WIDTH' => $imagedata['width'], 
						'HEIGHT' => $imagedata['height'])
					);
					
					if ($imagedata['allow_dl'])
					{
						$template->assign_block_vars('selected.download', array(
							'URL' => s_link('a', array($this->data['subdomain'], 4, $imagedata['image'], 'save')))
						);
					}
					
					$this->data['images']--;
				}
				
				//
				// Get thumbnails
				//
				$sql = 'SELECT g.*
					FROM _artists a, _artists_images g
					WHERE a.ub = ' . $this->data['ub'] . '
						AND a.ub = g.ub' . 
						(($download_id) ? ' AND g.image <> ' . $download_id : '') . '
					ORDER BY image DESC';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$tcol = 0;
					$template->assign_block_vars('thumbnails', array());
					do
					{
						if (!$tcol)
						{
							$template->assign_block_vars('thumbnails.row', array());
						}
						
						$template->assign_block_vars('thumbnails.row.col', array(
							'URL' => s_link('a', array($this->data['subdomain'], 4, $row['image'], 'view')),
							'IMAGE' => SDATA . 'artists/' . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
							'RIMAGE' => get_a_imagepath(SDATA . 'artists/' . $this->data['ub'], $row['image'] . '.jpg', array('x1', 'gallery')),
							'WIDTH' => $row['width'], 
							'HEIGHT' => $row['height'],
							'FOOTER' => $row['image_footer'])
						);
						
						$tcol = ($tcol == 3) ? 0 : $tcol + 1;
					}
					while ($row = $db->sql_fetchrow($result));
					$db->sql_freeresult($result);
				}
				else
				{
					redirect(s_link('a', array($this->data['subdomain'], 4)));
				}
				break;
		}
		
		return;
	}
	
	function _5()
	{
		return;
	}
	
	//
	// Lyrics
	//
	function _6()
	{
		global $config, $template, $lang;
		
		$mode = request_var('mode', '');
		$download_id = intval(request_var('download_id', 0));
		
		if ($mode == 'view' || $mode == 'save')
		{
			if (!$download_id)
			{
				redirect(s_link('a', array($this->data['subdomain'], 6)));
			}
			
			$sql = 'SELECT l.*
				FROM _artists_lyrics l
				LEFT JOIN _artists a ON a.ub = l.ub
				WHERE l.ub = ' . (int) $this->data['ub'] . '
					AND l.id = ' . (int) $download_id;
			$result = $db->sql_query($sql);
			
			if (!$lyric_data = $db->sql_fetchrow($result))
			{
				redirect(s_link('a', array($this->data['subdomain'], 6)));
			}
			$db->sql_freeresult($result);
		}
		
		switch ($mode)
		{
			case 'save':
				$lyric_data['text'] = str_replace("\n", "\r\n", $lyric_data['text']);
				
				$orig_file = implode("\r\n", @file('../net/template/lyrics.txt'));
				$orig_vars = array('{ARTIST}', '{EMAIL}', '{WEBSITE}', '{LYRIC_TITLE}', '{LYRIC_AUTHOR}', '{LYRIC_TEXT}');
				$repl_vars = array($this->data['name'], $this->data['email'], $this->data['www'], $lyric_data['title'], $lyric_data['author'], $lyric_data['text']);
				
				if (!$this->auth['mod'])
				{
					$sql = 'UPDATE _artists_lyrics
						SET downloads = downloads + 1
						WHERE id = ' . (int) $lyric_data['id'] . '
							AND ub = ' . (int) $this->data['ub'];
					$db->sql_query($sql);
				}
				
				die($orig_file);
				
				$this->filename = $this->data['name'] . '_' . $lyric_data['title'] . '.txt';
				$this->dl_file('', '', str_replace($orig_vars, $repl_vars, $orig_file), 'text/txt');
				break;
			case 'view':
			default:
				if ($mode == 'view')
				{
					if (!$this->auth['mod'])
					{
						$db->sql_query('UPDATE _artists_lyrics SET views = views + 1 WHERE ub = ' . (int) $this->data['ub'] . ' AND id = ' . (int) $lyric_data['id']);
						$lyric_data['views']++;
					}
					
					$template->assign_block_vars('read', array(
						'TITLE' => $lyric_data['title'],
						'AUTHOR' => $lyric_data['author'],
						'TEXT' => str_replace("\n", '<br />', $lyric_data['text']),
						'VIEWS' => $lyric_data['views'],
						'DOWNLOADS' => $lyric_data['downloads'],
						'U_DOWNLOAD' => s_link('a', array($this->data['subdomain'], 6, $lyric_data['id'], 'save')))
					);
				}
				
				$sql = 'SELECT l.*
					FROM _artists_lyrics l
					LEFT JOIN _artists a ON a.ub = l.ub
					WHERE l.ub = ' . (int) $this->data['ub'] . '
					ORDER BY title';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$template->assign_block_vars('select', array());
					
					do
					{
						$template->assign_block_vars('select.item', array(
							'URL' => s_link('a', array($this->data['subdomain'], 6, $row['id'], 'view')) . '#read',
							'TITLE' => $row['title'],
							'SELECTED' => ($download_id && $download_id == $row['id']) ? TRUE : FALSE)
						);
					}
					while ($row = $db->sql_fetchrow($result));
				}
				$db->sql_freeresult($result);
				break;
		}
		
		return;
	}
	
	function _7()
	{
		return;
	}
	
	function _8()
	{
		return;
	}
	
	//
	// Downloads
	//
	function _9()
	{
		$this->dl_setup();
		
		$mode = request_var('dl_mode', '');
		if ($mode == '')
		{
			$mode = 'view';
		}
		
		if (!in_array($mode, array('view', 'save', 'vote', 'fav')))
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		$mode = 'dl_' . $mode;
		if (!method_exists($this, $mode))
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		return $this->$mode();
	}
	
	function _10()
	{
		return;
	}
	
	//
	// Messages
	//
	function _12()
	{
		global $user, $config, $template;
		
		$post_id = request_var('post_id', 0);
		if (!$post_id)
		{
			fatal_error();
		}
		
		$sql = 'SELECT a.ub, p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists a, _artists_posts p, _members m
			WHERE a.ub = ' . (int) $this->data['ub'] . '
				AND a.ub = p.post_ub
				AND p.post_id = ' . (int) $post_id . '
				AND p.poster_id = m.user_id';
		$result = $db->sql_query($sql);
		
		if (!$pdata = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		
		$mode = request_var('mode', '');
		
		if ($mode == 'report')
		{
			
		}
		else // ELSE << IF MODE != REPORT
		{
			$comments_ref = s_link('a', array($this->data['subdomain'], 12, $pdata['post_id'], 'reply'));
			
			$start = intval(request_var('rs', 0));
			$this->msg->ref = $comments_ref;
			$this->msg->auth = $this->auth;
			
			$this->msg->data = array(
				'A_LINKS_CLASS' => 'bold red',
				'SQL' => 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar
					FROM _artists_posts p, _members m, _artists a
					WHERE p.post_ub = ' . $this->data['ub'] . '
						AND (p.post_id = ' . (int) $post_id . ' OR p.post_reply = ' . (int) $post_id . ')
						AND p.post_active = 1
						AND p.post_ub = a.ub
						AND p.poster_id = m.user_id
					ORDER BY p.post_reply ASC, p.post_time DESC
					LIMIT ' . (int) $start . ', ' . (int) $config['s_posts']
			);
			
			if ($this->auth['user'])
			{
				$this->msg->data['CONTROL']['reply'] = array(
					'REPLY' => array(
						'URL' => s_link('a', array($this->data['subdomain'], 12, '%d')) . '#reply',
						'ID' => 'post_id'
					)
				);
			}
			
			if ($this->auth['user'] && !$this->auth['mod'])
			{
				$this->msg->data['CONTROL']['report'] = array(
					'REPORT' => array(
						'URL' => s_link('a', array($this->data['subdomain'], 12, '%d', 'report')),
						'ID' => 'post_id'
					)
				);
			}
			
			if ($this->auth['mod'])
			{
				$this->msg->data['CONTROL']['auth'] = array();
				
				if ($this->auth['adm'] && $user->data['is_founder'])
				{
					$this->msg->data['CONTROL']['auth']['EDIT'] = array(
						'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'edit', 'id' => '%d')),
						'ID' => 'post_id'
					);
				}
				
				$this->msg->data['CONTROL']['auth']['DELETE'] = array(
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'delete', 'id' => '%d')),
					'ID' => 'post_id'
				);
			}
			
			$sql = preg_replace('/LIMIT ([0-9]+), ([0-9]+)/', '', $this->msg->data['SQL']);
			$reply_result = $db->sql_query($sql);
			$total_posts = $db->sql_numrows($reply_result);
			$db->sql_freeresult($reply_result);
			
			$this->msg->view($start, 'rs', $total_posts, $config['s_posts'], 'reply_msg', 'RMSG_', '', FALSE);
			
			$template->assign_vars(array(
				'PARENT_ID' => $post_id)
			);
		}
		
		if ($this->auth['post'] && ($this->data['a_active'] || $user->_team_auth('founder')))
		{
			if ($this->auth['user'])
			{
				$template->assign_block_vars('reply_post_box', array(
					'REF' => $comments_ref)
				);
			}
			else
			{
				$template->assign_block_vars('reply_no_guest_posting', array(
					'LEGEND' => sprintf($user->lang['UB_NO_GUEST_POSTING'], $this->data['name'], s_link('my', 'register'))
				));
			}
		}
		else
		{
			$template->assign_block_vars('reply_no_post_auth', array());
			
			if ($this->auth['post_until'])
			{
				$template->assign_block_vars('reply_no_post_auth.until', array(
					'UNTIL_DATETIME' => $user->format_date($this->auth['post_until']))
				);
			}
		}
		
		return;
	}
	
	//
	// Email
	//
	function _13()
	{
		if (empty($this->data['email']))
		{
			fatal_error();
		}
		
		if (!$this->auth['user'])
		{
			do_login();
		}
		
		global $user, $config, $template;
		
		$error_msg = '';
		$subject = '';
		$message = '';
		$current_time = time();
		
		if (isset($_POST['submit']))
		{
			$subject = request_var('subject', '');
			$message = request_var('message', '', true);
			
			if (empty($subject) || empty($message))
			{
				$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['FIELDS_EMPTY'];
			}
			
			if (empty($error_msg))
			{
				$sql = 'UPDATE _artists SET last_email = ' . (int) $current_time . ", last_email_user = " . (int) $user->data['user_id'] . '
					WHERE ub = ' . (int) $this->data['ub'];
				$db->sql_query($sql);
				
				include(ROOT.'interfase/emailer.php');
				$emailer = new emailer($config['smtp_delivery']);

				$emailer->from($user->data['user_email']);

				$email_headers = 'X-AntiAbuse: User_id - ' . $user->data['user_id'] . "\n";
				$email_headers .= 'X-AntiAbuse: Username - ' . $user->data['username'] . "\n";
				$email_headers .= 'X-AntiAbuse: User IP - ' . $user->ip . "\n";

				$emailer->use_template('mmg_send_email', $config['default_lang']);
				$emailer->email_address($this->data['email']);
				$emailer->set_subject($subject);
				$emailer->extra_headers($email_headers);

				$emailer->assign_vars(array(
					'SITENAME' => $config['sitename'], 
					'BOARD_EMAIL' => $config['board_email'], 
					'FROM_USERNAME' => $user->data['username'], 
					'UB_NAME' => $this->data['name'], 
					'MESSAGE' => $message
				));
				$emailer->send();
				$emailer->reset();
				
				redirect(s_link('a', $this->data['subdomain']));
			}
		}
		
		if ($error_msg != '')
		{
			$template->assign_block_vars('error', array());
		}
		
		$template->assign_vars(array(
			'ERROR_MESSAGE' => $error_msg,
			
			'SUBJECT' => $subject,
			'MESSAGE' => $message)
		);
		
		return;
	}
	
	//
	// Redirect to Website
	//
	function _14()
	{
		if ($this->data['www'] == '')
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		global $user;
		
		if (!$this->data['www_awc'] && !check_www($this->data['www']))
		{
			trigger_error(sprintf($user->lang['LINKS_CANT_REDIRECT'], $this->data['www']));
		}
		
		$db->sql_query('UPDATE _artists SET www_views = www_views + 1 WHERE ub = ' . (int) $this->data['ub']);
		
		header('Location: http://' . $this->data['www']);
		exit();
	}
	
	//
	// Favorites
	//
	function _15()
	{
		global $user;
		
		if (!$this->auth['user'])
		{
			do_login(sprintf($user->lang['LOGIN_BE_FAN'], $this->data['name']));
		}
		
		$url = s_link('a', $this->data['subdomain']);
		
		if ($this->auth['smod'])
		{
			redirect($url);
		}
		
		if ($this->auth['fav'])
		{
			$sql_member = array('user_a_favs' => $user->data['user_a_favs'] - 1);
			
			if ($user->data['user_a_favs'] == 1 && $user->data['user_type'] == USER_FAN)
			{
				$sql_member += array('user_type' => USER_NORMAL, 'user_color' => '4D5358');
			}
			
			$db->sql_query('DELETE FROM _artists_fav WHERE ub = ' . $this->data['ub'] . ' AND user_id = ' . (int) $user->data['user_id']);
			
			$user->delete_all_unread(UH_AF, $user->data['user_id']);
		}
		else
		{
			$sql_member = array('user_a_favs' => $user->data['user_a_favs'] + 1);
			
			if ($user->data['user_type'] == USER_NORMAL)
			{
				$sql_member += array('user_type' => USER_FAN, 'user_color' => '7A0B43');
			}
			
			$db->sql_query('INSERT INTO _artists_fav' . $db->sql_build_array('INSERT', array('ub' => (int) $this->data['ub'], 'user_id' => (int) $user->data['user_id'], 'joined' => time())));
			
			$user->save_unread(UH_AF, $db->sql_nextid(), $this->data['ub']);
		}
		
		$sql = 'UPDATE _members SET ' . $db->sql_build_array('UPDATE', $sql_member) . ' WHERE user_id = ' . (int) $user->data['user_id'];
		$db->sql_query($sql);
		
		redirect($url);
		
		return;
	}
	
	function _16()
	{
		return;
	}
	
	//
	// Vote
	//
	function _17()
	{
		if (!$this->auth['user'])
		{
			do_login();
		}
		
		$option_id = intval(request_var('vote_id', 0));
		$url = s_link('a', $this->data['subdomain']);
		
		if ($this->auth['mod'] || !$option_id || !in_array($option_id, $this->voting['ub']))
		{
			redirect($url);
		}
		
		global $user;
		
		$sql = 'SELECT user_id
			FROM _artists_voters
			WHERE ub = ' . $this->data['ub'] . '
				AND user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			redirect($url);
		}
		$db->sql_freeresult($result);
		
		//
		$db->sql_query('UPDATE _artists_votes SET vote_result = vote_result + 1 WHERE ub = ' . (int) $this->data['ub'] . ' AND option_id = ' . (int) $option_id);
		
		if (!$db->sql_affectedrows())
		{
			$db->sql_query('INSERT INTO _artists_votes (ub, option_id, vote_result) VALUES (' . (int) $this->data['ub'] . ', ' . (int) $option_id . ', 1)');
		}
		
		$db->sql_query('INSERT INTO _artists_voters (ub, user_id, user_option) VALUES (' . (int) $this->data['ub'] . ', ' . (int) $user->data['user_id'] . ', ' . (int) $option_id . ')');
		$db->sql_query('UPDATE _artists SET votes = votes + 1 WHERE ub = ' . (int) $this->data['ub']);
		
		redirect($url);
	}
	
	function _18()
	{
		global $user, $template;
		
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
				'NAME' => $row['video_name'],
				'CODE' => $row['video_code'],
				'TIME' => $user->format_date($row['video_added']))
			);
			
			$video++;
		}
		$db->sql_freeresult($result);
		
		return;
	}
}

//
// Class: _artists
//
class _artists extends layout
{
	var $auth = array();
	var $data = array();
	var $adata = array();
	var $images = array();
	var $layout = array();
	var $voting = array();
	var $msg = array();
	var $ajx = true;
	
	function _artists ()
	{
		$this->layout = array(
			'_01' => array('code' => 1, 'text' => 'UB_L01', 'tpl' => 'a_main'),
			'_02' => array('code' => 2, 'text' => 'UB_L02', 'tpl' => 'a_bio'),
			'_03' => array('code' => 3, 'text' => 'UB_L03', 'tpl' => 'a_albums'),
			'_04' => array('code' => 4, 'text' => 'UB_L04', 'tpl' => 'a_gallery'),
			'_05' => array('code' => 5, 'text' => 'UB_L05', 'tpl' => 'a_tabs'),
			'_06' => array('code' => 6, 'text' => 'UB_L06', 'tpl' => 'a_lyrics'),
			'_07' => array('code' => 7, 'text' => 'UB_L07', 'tpl' => 'a_interviews'),
			'_09' => array('code' => 9, 'text' => 'DOWNLOADS', 'tpl' => 'a_downloads'),
			'_12' => array('code' => 12, 'text' => 'POSTS', 'tpl' => 'a_messages'),
			'_13' => array('code' => 13, 'text' => '', 'tpl' => 'a_email'),
			'_16' => array('code' => 16, 'text' => '', 'tpl' => 'a_news'),
			'_18' => array('code' => 18, 'text' => 'UB_L17', 'tpl' => 'a_video')
		);
		
		$this->voting = array(
			'ub' => array(1, 2, 3, 5, 6),
			'ud' => array(1, 2, 3, 4, 5)
		);
	}
	
	function get_data()
	{
		global $db;
		
		$sql = 'SELECT *
			FROM _artists
			ORDER BY name ASC';
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$this->adata[$row['ub']] = $row;
		}
		$db->sql_freeresult($result);
		
		return;
	}
	
	function _setup()
	{
		global $user;
		
		$_a = request_var('id', '');
		if (!empty($_a))
		{
			if (preg_match('/([0-9a-zA-Z]+)/', $_a))
			{
				$sql = "SELECT * 
					FROM _artists
					WHERE subdomain = '" . $db->sql_escape(strtolower($_a)) . "' 
					LIMIT 1";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$db->sql_freeresult($result);
					
					$row['ub'] = (int) $row['ub'];
					$this->data = $row;
					
					return true;
				}
			}
			
			fatal_error();
		}
		
		return false;
	}
	
	function _auth ()
	{
		global $user;
		
		$this->auth['user'] = ($user->data['is_member']) ? TRUE : FALSE;
		$this->auth['adm'] = (($user->data['user_type'] == USER_FOUNDER) && $this->auth['user']) ? TRUE : FALSE;
		$this->auth['mod'] = ($this->auth['adm']) ? TRUE : FALSE;
		$this->auth['smod'] = FALSE;
		$this->auth['fav'] = FALSE;
		$this->auth['post'] = TRUE;
		
		if (!$this->auth['user'] || $this->data['layout'] == 14) // TEMP
		{
			return;
		}
		
		if ($user->data['user_type'] == USER_ARTIST)
		{
			$sql = 'SELECT u.user_id
				FROM _members u, _artists_auth a, _artists b
				WHERE a.ub = ' . $this->data['ub'] . '
					AND a.user_id = ' . (int) $user->data['user_id'] . ' 
					AND a.user_id = u.user_id
					AND b.ub = a.ub
					AND b.ub = a.ub';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$db->sql_freeresult($result);
				
				$this->auth['smod'] = $this->auth['mod'] = TRUE;
				return;
			}
		}
		
		$sql = 'SELECT aa.*
			FROM _artists_access aa
			LEFT JOIN _artists a ON aa.ub = a.ub
			RIGHT JOIN _members m ON aa.user_id = m.user_id
			WHERE aa.ub = ' . (int) $this->data['ub'] . '
				AND aa.user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$current_time = time();
			
			if (!$row['ban_time'] || $row['ban_time'] > $current_time)
			{
				if ($row['ban_access'])
				{
					global $user;
					
					$message = (!$row['ban_time']) ? 'UB_BANNED' : sprintf($user->lang['UB_BANNED_UNTIL'], $user->format_date($row['ban_time']));
					trigger_error($message);
				}
				else
				{
					$this->auth['post'] = FALSE;
					$this->auth['post_until'] = ($row['ban_time']) ? $row['ban_time'] : 0;
				}
			}
			else
			{
				$sql = 'DELETE FROM _artists_access
					WHERE user_id = ' . (int) $user->data['user_id'] . '
						AND ub = ' . $this->data['ub'];
				$db->sql_query($sql);
			}
		}
		$db->sql_freeresult($result);
		
		if ($user->data['user_type'] != USER_NORMAL)
		{
			$sql = 'SELECT f.* 
				FROM _artists b, _artists_fav f, _members m 
				WHERE b.ub = ' . $this->data['ub'] . ' 
					AND b.ub = f.ub 
					AND f.user_id = ' . (int) $user->data['user_id'] . ' 
					AND f.user_id = m.user_id 
					AND m.user_type <> ' . USER_IGNORE;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$this->auth['fav'] = TRUE;
			}
			$db->sql_freeresult($result);
		}
		
		return;
	}
	
	function call_layout()
	{
		$layout = '_' . $this->data['layout'];
		if (!method_exists($this, $layout))
		{
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		return $this->$layout();
	}
	
	function stats ($id)
	{
		if (is_array($id))
		{
			$all_stats = array();
			foreach ($id as $item)
			{
				$all_stats[$item] = $this->stats($item);
			}
			return $all_stats;
		}
		
		$t_ub = $s_ub = 0;
		foreach ($this->adata as $data)
		{
			if ($data[$id] > $s_ub)
			{
				$s_ub = $data[$id];
				$t_ub = $data['ub'];
			}
		}
		
		return ($t_ub) ? $this->adata[$t_ub] : false;
	}
	
	function last_records()
	{
		global $user, $cache, $template;
		
		if (!$a_records = $cache->get('a_records'))
		{
			$sql = 'SELECT ub, subdomain, name, genre
				FROM _artists
				ORDER BY datetime DESC
				LIMIT 3';
			$result = $db->sql_query($sql);
			
			$a_records = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$a_records[$row['ub']] = $row;
			}
			$db->sql_freeresult($result);
			
			$cache->save('a_records', $a_records);
		}
		
		if (!$ai_records = $cache->get('ai_records'))
		{
			$ai_records = array();
			
			foreach ($a_records as $row)
			{
				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub = ' . (int) $row['ub'] . '
					ORDER BY image';
				$result = $db->sql_query($sql);
				
				while ($row2 = $db->sql_fetchrow($result))
				{
					$ai_records[$row['ub']][] = $row2['image'];
				}
				$db->sql_freeresult($result);
			}
			
			$cache->save('ai_records', $ai_records);
		}
		
		$template->assign_block_vars('a_records', array());
		
		foreach ($a_records as $row)
		{
			$template->assign_block_vars('a_records.item', array(
				'URL' => s_link('a', $row['subdomain']),
				'NAME' => $row['name'],
				'GENRE' => $row['genre'])
			);
			
			if (isset($ai_records[$row['ub']]))
			{
				$ai_select = array_rand($ai_records[$row['ub']]);
				
				$template->assign_block_vars('a_records.item.image', array(
					'IMAGE' => SDATA . 'artists/' . $row['ub'] . '/thumbnails/' . $ai_records[$row['ub']][$ai_select] . '.jpg')
				);
			}
		}
	}
	
	function top_stats ()
	{
		global $user, $template;
		
		$template->assign_block_vars('a_stats', array());
		
		$all_data = $this->stats(array('datetime', 'views', 'votes', 'posts'));
		
		if ($all_data['datetime'])
		{
			global $cache;
			
			$a_random = array();
			if (!$a_random = $cache->get('a_last_images'))
			{
				global $db;
				
				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub = ' . $all_data['datetime']['ub'] . '
					ORDER BY image';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					do
					{
						$a_random[] = $row['image'];
					}
					while ($row = $db->sql_fetchrow($result));
					
					$cache->save('a_last_images', $a_random);
				}
				$db->sql_freeresult($result);
			}
			
			if (sizeof($a_random))
			{
				$selected_image = array_rand($a_random);
				if (isset($a_random[$selected_image]))
				{
					$template->assign_block_vars('a_stats.gallery', array(
						'IMAGE' => SDATA . 'artists/' . $all_data['datetime']['ub'] . '/thumbnails/' . $a_random[$selected_image] . '.jpg',
						'URL' => s_link('a', $all_data['datetime']['subdomain']))
					);
				}
			}
		}
		
		foreach ($all_data as $id => $data)
		{
			if ($data['name'] != '')
			{
				$template->assign_block_vars('a_stats.item', array(
					'LANG' => $user->lang['UB_TOP_' . strtoupper($id)],
					'URL' => s_link('a', $data['subdomain']),
					'NAME' => $data['name'],
					'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
					'GENRE' => $data['genre'])
				);
			}
		}
		
		return;
	}
	
	function thumbnails()
	{
		global $cache, $template;
		
		if (!$a_recent = $cache->get('a_recent'))
		{
			$sql = 'SELECT ub
				FROM _artists
				ORDER BY datetime DESC
				LIMIT 10';
			$result = $db->sql_query($sql);
			
			$a_recent = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$a_recent[$row['ub']] = 1;
			}
			$db->sql_freeresult($result);
			
			$cache->save('a_recent', $a_recent);
		}
		
		$a_ary = array();
		for ($i = 0; $i < 2; $i++)
		{
			$_a = array_rand($a_recent);
			if (!$this->adata[$_a]['images'] || isset($a_ary[$_a]))
			{
				$i--;
				continue;
			}
			$a_ary[$_a] = $this->adata[$_a];
		}
		
		for ($i = 0; $i < 2; $i++)
		{
			$_a = array_rand($this->adata);
			if (!$this->adata[$_a]['images'] || isset($a_ary[$_a]))
			{
				$i--;
				continue;
			}
			$a_ary[$_a] = $this->adata[$_a];
		}
		
		if (sizeof($a_ary))
		{
			$sql = 'SELECT *
				FROM _artists_images
				WHERE ub IN (' . implode(',', array_keys($a_ary)) . ')
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
			
			$template->assign_block_vars('thumbnails', array());
			
			foreach ($a_ary as $ub => $data)
			{
				$template->assign_block_vars('thumbnails.item', array(
					'NAME' => $data['name'],
					'IMAGE' => SDATA . 'artists/' . $ub . '/thumbnails/' . $random_images[$ub] . '.jpg',
					'URL' => s_link('a', $data['subdomain']),
					'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
					'GENRE' => $data['genre'])
				);
			}
		}
		
		return;
	}
	
	function get_images($mainframe = false, $ub = 0, $rand = false)
	{
		if ($this->images)
		{
			return;
		}
		
		global $db;
		
		if ($mainframe)
		{
			$sql = 'SELECT i.* 
				FROM _artists_images i, _artists a 
				WHERE i.ub = a.ub 
				ORDER BY i.image';
		}
		else
		{
			if ($ub)
			{
				$sql = 'SELECT i.* 
					FROM _artists_images i 
					LEFT JOIN _artists a ON a.ub = i.ub 
					WHERE i.ub = ' . (int) $ub . ' 
					ORDER BY ' . (($rand) ? 'RAND() LIMIT 1' : 'image');
			}
		}
		$result = $db->sql_query($sql);
		
		$gallery_path = 'data/artists/';
		
		if ($ub && !$mainframe)
		{
			if ($row = $db->sql_fetchrow($result))
			{
				$this->images[$row['ub']][$row['image']] = array(
					'path' => $gallery_path . $row['ub'] . '/gallery/' . $row['image'] . '.jpg',
					'image' => $row['image'],
					'allow_dl' => $row['allow_dl']
				);
			}
			$db->sql_freeresult($result);
			
			return $row['image'];
		}
		
		while ($row = $db->sql_fetchrow($result))
		{
			$this->images[$row['ub']][$row['image']] = array(
				'path' => $gallery_path . $row['ub'] . '/gallery/' . $row['image'] . '.jpg',
				'image' => $row['image'],
				'allow_dl' => $row['allow_dl']
			);
		}
		$db->sql_freeresult($result);
		
		return;
	}
	
	function downloads()
	{
		$this->dl_sql();
		
		if (empty($this->ud_song))
		{
			return;
		}
		
		global $config, $template;
		
		$template->assign_block_vars('downloads', array());
		
		$ud_in_ary = array();
		foreach ($this->ud_song as $ud => $dl_data)
		{
			$dl_size = sizeof($dl_data);
			if (!$dl_size)
			{
				continue;
			}
			
			$ud_size = ($dl_size > $config['main_dl']) ? $config['main_dl'] : $dl_size;
			$download_type = $this->dl_type($ud);
			
			$template->assign_block_vars('downloads.panel', array(
				'UD' => $download_type['lang'],
				'TOTAL_COUNT' => $dl_size)
			);
			
			for ($i = 0; $i < $ud_size; $i++)
			{
				$ud_rand = array_rand($dl_data);
				
				if (isset($ud_in_ary[$ud][$ud_rand]))
				{
					$i--;
					continue;
				}
				$ud_in_ary[$ud][$ud_rand] = TRUE;
				
				$template->assign_block_vars('downloads.panel.item', array(
					'UB' => $this->adata[$dl_data[$ud_rand]['ub']]['name'],
					'TITLE' => $dl_data[$ud_rand]['title'],
					'URL' => s_link('a', array($this->adata[$dl_data[$ud_rand]['ub']]['subdomain'], 9, $dl_data[$ud_rand]['id'])))
				);
			}
		}
		
		return;
	}
	
	function _list()
	{
		global $user, $config, $template;
		
		$sql = 'SELECT *
			FROM _artists
			ORDER BY local DESC, name ASC';
		$result = $db->sql_query($sql);
		
		$alphabet = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$this->adata[$row['local']][$row['ub']] = $row;
			
			$alpha_id = strtolower($row['name']);
			$alpha_id = $alpha_id{0};
			if (!isset($alphabet[$alpha_id]))
			{
				if (preg_match('/([0-9])/', $alpha_id))
				{
					$alpha_id = '#';
				}
				$alphabet[$alpha_id] = TRUE;
			}
		}
		$db->sql_freeresult($result);
			
		$selected_char = '';
		$s_alphabet = intval(request_var('alphabet', 0));
		
		if ($s_alphabet)
		{
			$selected_char = chr(octdec($s_alphabet));
			if (!preg_match('/([\#a-z])/', $selected_char))
			{
				redirect(s_link('a'));
			}
		}
		
		$sql_where = ($s_alphabet) ? 'WHERE ' . (($selected_char == '#') ? "name NOT RLIKE '^[a-z]'" : "name LIKE '" . $db->sql_escape($selected_char) . "%'") : 'WHERE images > 1';
		$sql_order = (!$s_alphabet) ? 'RAND() LIMIT 12' : 'name';
		
		$sql = 'SELECT *
			FROM _artists
			' . $sql_where . '
			ORDER BY ' . $sql_order;
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
			
			$template->assign_block_vars('search_match', array());
			
			if (!$s_alphabet)
			{
				$template->assign_block_vars('search_match.ajx', array());
				$this->ajx = false;
			}
			
			$tcol = 0;
			foreach ($selected_artists as $ub => $data)
			{
				$image = ($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif';
				
				if (!$tcol)
				{
					$template->assign_block_vars('search_match.row', array());
				}
				
				$template->assign_block_vars('search_match.row.col', array(
					'NAME' => $data['name'],
					'IMAGE' => SDATA . 'artists/' . $image,
					'URL' => s_link('a', $data['subdomain']),
					'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
					'GENRE' => $data['genre'])
				);
				
				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
		}
		else
		{
			redirect(s_link('a'));
		}
		
		ksort($alphabet);
		
		foreach ($alphabet as $key => $null)
		{
			$template->assign_block_vars('alphabet_item', array(
				'CHAR' => strtoupper($key),
				'URL' => s_link('a', '_' . decoct(ord($key))))
			);
		}
		
		$template->assign_vars(array(
			'TOTAL_A' => $config['max_artists'],
			'SELECTED_LETTER' => ($selected_char) ? strtoupper($selected_char) : '')
		);
		
		return;
	}
	
	function _panel()
	{
		global $user, $config, $template;
		
		$this->data['layout'] = request_var('layout', 0);
		$this->_auth();
		
		switch ($this->data['layout'])
		{
			case 14:
			case 15:
			case 17:
				$this->call_layout();
				break;
			default:
				if (!$this->data['layout'])
				{
					$this->data['layout'] = 1;
				}
				
				//
				// Nav
				//
				$s_layout = array();
				$s_layout['a']['_01'] = TRUE;
				$s_layout['a']['_02'] = ($this->data['bio'] != '') ? true : false;
				// $s_layout['a']['_03'] = TRUE;
				$s_layout['a']['_04'] = ($this->data['images'] > 1) ? true : false;
				// $s_layout['_05'] = TRUE;
				$s_layout['a']['_06'] = ($this->data['lirics'] > 0) ? true : false;
				// $s_layout['a']['_07'] = TRUE;
				$s_layout['a']['_09'] = ($this->data['layout'] == 9) ? true : false;
				$s_layout['a']['_12'] = ($this->data['layout'] == 12) ? true : false;
				$s_layout['a']['_18'] = ($this->data['a_video'] > 0) ? true : false;
				
				foreach ($this->layout as $item => $data)
				{
					$s_layout['x'][$item] = $data['code'];
					
					if ($data['text'] == '')
					{
						$s_layout['e'][$item] = $data['code'];
					}
					
					if (isset($s_layout['a'][$item]) && $s_layout['a'][$item] && $data['tpl'] != '')
					{
						$s_layout['s'][$data['code']] = $data;
					}
					
					if (($this->data['layout'] == $data['code']) && $data['tpl'] != '')
					{
						$this->data['template'] = $data['tpl'];
					}
				}
				
				if (!in_array($this->data['layout'], $s_layout['x']) || (!isset($s_layout['s'][$this->data['layout']]) && !in_array($this->data['layout'], $s_layout['e'])))
				{
					redirect(s_link('a', $this->data['subdomain']));
				}
				
				//
				// Call selected layout
				//
				$this->call_layout();
				
				//
				// Build nav
				//
				foreach ($s_layout['s'] as $data)
				{
					$template->assign_block_vars('nav', array(
						'LANG' => $user->lang[$data['text']])
					);
					
					if ($this->data['layout'] == $data['code'])
					{
						$template->assign_block_vars('nav.strong', array());
						continue;
					}
					
					$template->assign_block_vars('nav.a', array(
						'URL' => s_link('a', array($this->data['subdomain'], $data['code'])))
					);
				}
				
				//
				// Update stats
				//				
				if (!$this->auth['mod'])
				{
					$update_views = FALSE;
					$current_time = time();
					$current_month = date('Ym', $current_time);
					
					if ($this->auth['user'])
					{
						$sql_viewers = array('datetime' => (int) $current_time, 'user_ip' => $user->ip);
						$sql_viewers2 = array('ub' => (int) $this->data['ub'], 'user_id' => (int) $user->data['user_id']);
						
						$sql = 'UPDATE _artists_viewers
							SET ' . $db->sql_build_array('UPDATE', $sql_viewers) . '
							WHERE ' . $db->sql_build_array('SELECT', $sql_viewers2);
						$db->sql_query($sql);
						
						if (!$db->sql_affectedrows())
						{
							$update_views = TRUE;
							$sql_stats = array('ub' => (int) $this->data['ub'], 'date' => (int) $current_month);
							
							$db->sql_query('INSERT INTO _artists_viewers' . $db->sql_build_array('INSERT', $sql_viewers + $sql_viewers2));
							$db->sql_query('UPDATE _artists_stats SET members = members + 1 WHERE ' . $db->sql_build_array('SELECT', $sql_stats));
							
							if (!$db->sql_affectedrows())
							{
								$db->sql_query('INSERT INTO _artists_stats' . $db->sql_build_array('INSERT', $sql_stats + array('members' => 1, 'guests' => 0)));
							}
							
							$sql = 'SELECT user_id
								FROM _artists_viewers
								WHERE ub = ' . $this->data['ub'] . '
								ORDER BY datetime DESC
								LIMIT 10, 1';
							$result = $db->sql_query($sql);
							
							if ($row = $db->sql_fetchrow($result))
							{
								$sql = 'DELETE FROM _artists_viewers
									WHERE ub = ' . $this->data['ub'] . '
										AND user_id = ' . $row['user_id'];
								$db->sql_query($sql);
							}
							$db->sql_freeresult($result);
						}
					}
					
					if ((($this->auth['user'] && $update_views) || (!$this->auth['user'] && $this->data['layout'] == 1)) && !isset($_REQUEST['ps']))
					{
						$db->sql_query('UPDATE _artists SET views = views + 1 WHERE ub = ' . $this->data['ub']);
						$this->data['views']++;
						
						if ((!$this->auth['user'] && $this->data['layout'] == 1) && !isset($_REQUEST['ps']))
						{
							$sql_stats = array('ub' => (int) $this->data['ub'], 'date' => (int) $current_month);
							
							$db->sql_query('UPDATE _artists_stats SET guests = guests + 1 WHERE ' . $db->sql_build_array('SELECT', $sql_stats));
							
							if (!$db->sql_affectedrows())
							{
								$db->sql_query('INSERT INTO _artists_stats' . $db->sql_build_array('INSERT', $sql_stats + array('members' => 0, 'guests' => 1)));
							}
						}
					}
				}
				
				//
				// OWN EVENTS
				//
				$timezone = $config['board_timezone'] * 3600;
		
				list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
				$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;
				
				$g = getdate($midnight);
				$week = mktime(0, 0, 0, $m, ($d + (7 - ($g['wday'] - 1)) - (!$g['wday'] ? 7 : 0)), $y) - $timezone;
				
				$sql = 'SELECT *
					FROM _events e, _artists_events ae
					WHERE ae.a_artist = ' . (int) $this->data['ub'] . '
						AND ae.a_event = e.id
					ORDER BY e.date';
				$result = $db->sql_query($sql);
				
				$events = array();
				while ($row = $db->sql_fetchrow($result))
				{
					if ($row['date'] >= $midnight)
					{
						if ($row['date'] >= $midnight && $row['date'] < $midnight + 86400)
						{
							$events['is_today'][] = $row;
						}
						else if ($row['date'] >= $midnight + 86400 && $row['date'] < $midnight + (86400 * 2))
						{
							$events['is_tomorrow'][] = $row;
						}
						else if ($row['date'] >= $midnight + (86400 * 2) && $row['date'] < $week)
						{
							$events['is_week'][] = $row;
						}
						else
						{
							$events['is_future'][] = $row;
						}
					}
					else if ($row['images'])
					{
						$events['is_gallery'][] = $row;
					}
				}
				$db->sql_freeresult($result);
				
				if (isset($events['is_gallery']) && sizeof($events['is_gallery']))
				{
					$gallery = $events['is_gallery'];
					@krsort($gallery);
					
					$template->assign_block_vars('events_gallery', array());
					foreach ($gallery as $row)
					{
						$template->assign_block_vars('events_gallery.item', array(
							'URL' => s_link('events', $row['id']),
							'TITLE' => $row['title'],
							'DATETIME' => $user->format_date($row['date'], $user->lang['DATE_FORMAT']))
						);
					}
					
					unset($events['is_gallery']);
				}
				
				if (sizeof($events))
				{
					$template->assign_block_vars('events_future', array());
					
					foreach ($events as $is_date => $data)
					{
						$template->assign_block_vars('events_future.set', array(
							'L_TITLE' => $user->lang['UE_' . strtoupper($is_date)])
						);
						
						foreach ($data as $item)
						{
							$template->assign_block_vars('events_future.set.item', array(
								'ITEM_ID' => $item['id'],
								'TITLE' => $item['title'],
								'DATE' => $user->format_date($item['date']),
								'THUMBNAIL' => SDATA . 'events/future/thumbnails/' . $item['id'] . '.jpg',
								'SRC' => SDATA . 'events/future/' . $item['id'] . '.jpg')
							);
						}
					}
				}
				
				//
				// Downloads
				//
				if ($this->data['um'] || $this->data['uv'])
				{
					$this->dl_sql($this->data['ub'], 'ud, title');
					
					foreach ($this->ud_song as $key => $data)
					{
						$download_type = $this->dl_type($key);
						$template->assign_block_vars('ud_block', array('LANG' => $download_type['lang']));
						
						foreach ($data as $song)
						{
							$template->assign_block_vars('ud_block.item', array('TITLE' => $song['title']));
							
							if (isset($this->dl_data['id']) && ($song['id'] == $this->dl_data['id']))
							{
								$template->assign_block_vars('ud_block.item.strong', array());
								continue;
							}
							
							$template->assign_block_vars('ud_block.item.a', array('URL' => s_link('a', array($this->data['subdomain'], 9, $song['id']))));
						}
					}
				}
				
				//
				// Art
				//
				if ($this->data['arts'])
				{
					$template->assign_block_vars('art_block', array());
						
					$sql = 'SELECT *
						FROM _art
						WHERE ub = ' . $this->data['ub'] . '
						ORDER BY title';
					$result = $db->sql_query($sql);
					
					while ($row = $db->sql_fetchrow($result))
					{
						$template->assign_block_vars('art_block.item', array('TITLE' => $row['title']));
						
						if ($row['id'] == $this->ud['TITLE'])
						{
							$template->assign_block_vars('art_block.item.strong', array());
							continue;
						}
						
						$template->assign_block_vars('art_block.item.a', array('URL' => s_link('art', $row['id'])));
					}
					$db->sql_freeresult($result);
				}
				
				//
				// Messages
				//
				$ref_layout = in_array($this->data['layout'], array(9, 12, 16)) ? 1 : $this->data['layout'];
				$comments_ref = s_link('a', array($this->data['subdomain'], $ref_layout));
				
				if ($this->data['posts'])
				{
					$start = intval(request_var('ps', 0));
					$this->msg->ref = $comments_ref;
					$this->msg->auth = $this->auth;
					
					$this->msg->data = array(
						'A_LINKS_CLASS' => 'bold red',
						'SQL' => 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color
							FROM _artists_posts p, _members m, _artists a
							WHERE p.post_ub = ' . $this->data['ub'] . ' 
								AND p.post_ub = a.ub
								AND p.post_active = 1 
								AND p.poster_id = m.user_id 
							ORDER BY p.post_time DESC 
							LIMIT ' . $start . ', ' . $config['s_posts']
					);
					
					if ($this->auth['user'])
					{
						$this->msg->data['CONTROL']['reply'] = array(
							'REPLY' => array(
								'URL' => s_link('a', array($this->data['subdomain'], 12, '%d')) . '#reply',
								'ID' => 'post_id'
							)
						);
					}
					
					if ($this->auth['user'] && !$this->auth['mod'])
					{
						$this->msg->data['CONTROL']['report'] = array(
							'REPORT' => array(
								'URL' => s_link('a', array($this->data['subdomain'], 12, '%d', 'report')),
								'ID' => 'post_id'
							)
						);
					}
					
					if ($this->auth['mod'])
					{
						$this->msg->data['CONTROL']['auth'] = array();
						
						if ($this->auth['adm'] && $user->data['is_founder'])
						{
							$this->msg->data['CONTROL']['auth']['EDIT'] = array(
								'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'edit', 'id' => '%d')),
								'ID' => 'post_id'
							);
						}
						
						$this->msg->data['CONTROL']['auth']['DELETE'] = array(
							'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'delete', 'id' => '%d')),
							'ID' => 'post_id'
						);
					}
					
					$this->msg->view($start, 'ps', $this->data['posts'], $config['s_posts'], '', 'MSG_', '', FALSE);
				}
				
				if ($this->data['a_active'] || $user->_team_auth('founder'))
				{
					if ($this->auth['post'])
					{
						if ($this->auth['user'])
						{
							$template->assign_block_vars('post_box', array('REF' => $comments_ref));
						}
						else
						{
							$template->assign_block_vars('no_guest_posting', array(
								'LEGEND' => sprintf($user->lang['UB_NO_GUEST_POSTING'], $this->data['name'], s_link('my', 'register'))
							));
						}
					}
					else
					{
						$template->assign_block_vars('no_post_auth', array());
						
						if ($this->auth['post_until'])
						{
							$template->assign_block_vars('no_post_auth.until', array('UNTIL_DATETIME' => $user->format_date($this->auth['post_until'])));
						}
					}
				}
				
				//
				// Contact | Fav
				//
				if ($this->data['email'] != '' || $this->data['www'] != '' || !$this->auth['mod'])
				{
					$template->assign_block_vars('contact', array(
						'WWW' => ($this->data['www'] != '') ? s_link('a', array($this->data['subdomain'], 14)) : '',
						'EMAIL' => ($this->data['email'] != '') ? s_link('a', array($this->data['subdomain'], 13)) : '',
						'FAV_URL' => (!$this->auth['smod']) ? s_link('a', array($this->data['subdomain'], 15)) : '',
						'FAV_LANG' => (!$this->auth['smod']) ? (($this->auth['fav']) ? $user->lang['UB_FAV_DEL'] : $user->lang['UB_FAV_ADD']) : '')
					);
				}
				
				//
				// Set template
				//
				$template->assign_vars(array(
					'INACTIVE' => !$this->data['a_active'],
					'UNAME' => $this->data['name'],
					'GENRE' => $this->data['genre'],
					'POSTS' => number_format($this->data['posts']),
					'VOTES' => number_format($this->data['votes']),
					
					'S_CONTROLPANEL' => (($user->data['is_member'] && $user->data['user_auth_control']) ? s_link('control', '_' . $this->data['subdomain']) : ''),
					'LOCATION' => ($this->data['local']) ? (($this->data['location'] != '') ? $this->data['location'] . ', ' : '') . 'Guatemala' : $this->data['location'])
				);
				
				$template->set_filenames(array(
					'a_body' => 'a_layout/' . $this->data['template'] . '.htm')
				);
				$template->assign_var_from_handle('UB_BODY', 'a_body');
				break;
		}
		
		return;
	}
	
	function a_sidebar()
	{
		global $template;
		
		$sql = 'SELECT *
			FROM _artists
			ORDER BY RAND()';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$sql = 'SELECT *
				FROM _artists_images
				WHERE ub = ' . (int) $row['ub'] . '
				ORDER BY RAND()';
			$result2 = $db->sql_query($sql);
			
			if ($row2 = $db->sql_fetchrow($result2))
			{
				$row['rand_image'] = $row2['image'];
			}
			$db->sql_freeresult($result2);
			
			$template->assign_block_vars('random_a', array(
				'NAME' => $row['name'],
				'IMAGE' => SDATA . 'artists/' . ((isset($row['rand_image'])) ? $row['ub'] . '/thumbnails/' . $row['rand_image'] . '.jpg' : 'default/shadow.gif'),
				'URL' => s_link('a', $row['subdomain']),
				'LOCATION' => ($row['local']) ? 'Guatemala' : $row['location'],
				'GENRE' => $row['genre'])
			);
		}
		$db->sql_freeresult($result);
	}

}

?>