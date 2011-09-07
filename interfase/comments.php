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

if (class_exists('_comments'))
{
	return;
}

class _comments
{
	var $ref;
	var $mesage;
	var $param = array();
	var $data = array();
	var $auth = array();
	var $users = array();
	var $options = array();
	
	function _comments()
	{
		$this->ref = '';
		$this->auth = array();
		$this->data = array();
		$this->param = array();
		$this->users = array();
		return;
	}
	
	function reset()
	{
		$this->_comments();
	}
	
	function reset2()
	{
		$this->message = '';
		$this->options = array();
	}
	
	//
	// Store members comments for (all) comment systems
	//
	function store()
	{
		global $user, $config;
		
		$ref = $this->ref;
		$this->ref = str_replace('http://' . $config['server_name'], '', $this->ref);
		$this->ref = preg_replace('#^/?(.*?)/?$#', '\1', $this->ref);
		$this->param = explode('/', $this->ref);
		$this->ref = $ref;
		
		$sql = '';
		$id = (isset($this->param[3])) ? (int) $this->param[3] : 0;
		
		switch ($this->param[0])
		{
			case 'a':
				if ($this->param[2] == 9)
				{
					$sql = 'SELECT *
						FROM _dl d, _artists a
						WHERE d.id = ?
							AND a.subdomain = ? 
							AND d.ub = a.ub';
					$sql = sql_filter($sql, $id, $this->param[1]);
					
					$this->data = array(
						'DATA_TABLE' => '_dl',
						'POST_TABLE' => '_dl_posts',
						'HISTORY' => UH_M
					);
				}
				else
				{
					$sql = 'SELECT *
						FROM _artists
						WHERE subdomain = ?';
					$sql = sql_filter($sql, $this->param[1]);
					
					$this->data = array(
						'DATA_TABLE' => '_artists',
						'POST_TABLE' => '_artists_posts',
						'HISTORY' => UH_C
					);
				}
				break;
			case 'events':
				$sql = 'SELECT *
					FROM _events
					WHERE id = ?';
				$sql = sql_filter($sql, $this->param[1]);
				
				$this->data = array(
					'DATA_TABLE' => '_events',
					'POST_TABLE' => '_events_posts',
					'HISTORY' => UH_EP
				);
				break;
			case 'news':
				$sql = 'SELECT *
					FROM _news
					WHERE news_id = ?';
				$sql = sql_filter($sql, $this->param[1]);
					
				$this->data = array(
					'DATA_TABLE' => '_news',
					'POST_TABLE' => '_news_posts',
					'HISTORY' => UH_NP
				);
				break;
			case 'art':
				$sql = 'SELECT *
					FROM _art
					WHERE art_id = ?';
				$sql = sql_filter($sql, $this->param[1]);
				
				$this->data = array(
					'DATA_TABLE' => '_art',
					'POST_TABLE' => '_art_posts',
					'HISTORY' => UH_W
				);
				break;
			case 'm':
				$sql = 'SELECT *
					FROM _members
					WHERE username_base = ?';
				$sql = sql_filter($sql, $this->param[1]);
				
				$this->data = array(
					'DATA_TABLE' => '_members',
					'POST_TABLE' => '_members_posts',
					'HISTORY' => UH_UPM
				);
				break;
			default:
				fatal_error();
				break;
		}
		
		if (empty($sql)) {
			fatal_error();
		}
		
		if (!$post_data = sql_fieldrow($sql)) {
			fatal_error();
		}
		
		$post_reply = 0;
		$error = array();
		$update_sql = '';
		$current_time = time();
		
		$this->auth['user'] = ($user->data['is_member']) ? TRUE : FALSE;
		$this->auth['adm'] = (($user->data['user_type'] == USER_FOUNDER) && $this->auth['user']) ? TRUE : FALSE;
		
		/*
		//
		// Flood control
		//
		if (!$this->auth['adm'] && !$this->auth['mod'])
		{
			$where_sql = (!$this->auth['user']) ? "post_ip = '$user_ip'" : "poster_id = " . $userdata['user_id'];
			$sql = "SELECT MAX(post_time) AS last_datetime 
				FROM " . $this->data['POST_TABLE'] . " 
				WHERE $where_sql";
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				if ((intval($row['last_datetime']) > 0) && ($current_time - intval($row['last_datetime'])) < 10)
				{
					$error[] = 'CHAT_FLOOD_CONTROL';
				}
			}
			$db->sql_freeresult($result);
		}
		*/
		
		//
		// CHECK USERNAME | MESSAGE
		//
		if (!sizeof($error))
		{
			$message = request_var('message', '', true);
			
			// Check message
			if (empty($message))
			{
				$error[] = 'EMPTY_MESSAGE';
			}
		}
		
		//
		// INSERT DATA
		//
		if (!sizeof($error))
		{
			$update_sql = '';
			$post_reply = (isset($this->param[4]) && $this->param[4] == 'reply') ? $id : 0;
			$message = $this->prepare($message);
			
			$insert_data = array(
				'post_reply' => (int) $post_reply,
				'post_active' => 1,
				'poster_id' => (int) $user->data['user_id'],
				'post_ip' => (string) $user->ip,
				'post_time' => (int) $current_time,
				'post_text' => (string) $message
			);
			
			switch ($this->param[0])
			{
				case 'a':
					switch ($this->param[2])
					{
						case 9:
							$insert_data['download_id'] = (int) $post_data['id'];
							$update_sql = 'posts = posts + 1 WHERE id = ' . (int) $post_data['id'];
							
							$this->data['HISTORY_EXTRA'] = $post_data['ub'];
							break;
						case 12:
						default:
							$insert_data['post_ub'] = (int) $post_data['ub'];
							$update_sql = 'posts = posts + 1 WHERE ub = ' . (int) $post_data['ub'];
							
							$this->data['HISTORY_EXTRA'] = $post_data['ub'];
							$this->data['REPLY_TO_SQL'] = 'SELECT p.poster_id, m.user_id
								FROM _artists_posts p, _members m
								WHERE p.post_id = ' . (int) $post_reply . '
									AND p.poster_id = m.user_id
									AND m.user_type NOT IN (' . USER_IGNORE . ', ' . USER_INACTIVE . ')';
							break;
					}
					break;
				case 'events':
					$insert_data['event_id'] = (int) $post_data['id'];
					$update_sql = 'posts = posts + 1 WHERE id = ' . (int) $post_data['id'];
					break;
				case 'news':
					$insert_data['news_id'] = (int) $post_data['news_id'];
					$update_sql = 'post_replies = post_replies + 1 WHERE news_id = ' . (int) $post_data['news_id'];
					break;
				case 'art':
					$insert_data['art_id'] = (int) $post_data['art_id'];
					$update_sql = 'posts = posts + 1 WHERE art_id = ' . (int) $post_data['art_id'];
					break;
				case 'm':
					$insert_data['userpage_id'] = (int) $post_data['user_id'];
					$update_sql = 'userpage_posts = userpage_posts + 1 WHERE user_id = ' . (int) $post_data['user_id'];
					
					$this->data['HISTORY_EXTRA'] = $post_data['user_id'];
					break;
			}
			
			$db->sql_query('INSERT INTO ' . $this->data['POST_TABLE'] . $db->sql_build_array('INSERT', $insert_data));
			$post_id = $db->sql_nextid();
			
			if ($update_sql != '')
			{
				$db->sql_query('UPDATE ' . $this->data['DATA_TABLE'] . ' SET ' . $update_sql);
			}
			
			$reply_to = 0;
			$history_extra = isset($this->data['HISTORY_EXTRA']) ? $this->data['HISTORY_EXTRA'] : 0;
			
			if ($post_reply && isset($this->data['REPLY_TO_SQL']))
			{
				$reply_result = $db->sql_query($this->data['REPLY_TO_SQL']);
				if ($reply_row = $db->sql_fetchrow($reply_result))
				{
					$reply_to = ($reply_row['user_id'] != GUEST) ? $reply_row['user_id'] : 0;
				}
				$db->sql_freeresult($reply_result);
				
				$user->delete_unread($this->data['HISTORY'], $post_reply);
			}
			
			$notify = true;
			if ($this->param[0] == 'm' && $user->data['user_id'] == $post_data['user_id'])
			{
				$notify = false;
			}
			
			if ($notify)
			{
				if ($this->param[0] == 'm')
				{
					$user->save_unread($this->data['HISTORY'], $post_id, $history_extra, $post_data['user_id']);
				}
				else
				{
					$user->save_unread($this->data['HISTORY'], $post_id, $history_extra, $reply_to, FALSE);
					
					// Points
					//$user->points_add(1);
				}
			}
			
			// Userpage messages
			if ($this->param[0] == 'm')
			{
				$sql = 'SELECT post_id
					FROM _members_posts p, _members_unread u
						WHERE u.item = p.post_id
							AND p.userpage_id = ' . (int) $user->data['user_id'] . '
							AND p.poster_id = ' . (int) $post_data['user_id'];
				$result = $db->sql_query($sql);
				
				$rows = array();
				while ($row = $db->sql_fetchrow($result))
				{
					$rows[] = $row['post_id'];
				}
				$db->sql_freeresult($result);
				
				if (count($rows))
				{
					$sql = 'DELETE FROM _members_unread
						WHERE user_id = ' . (int) $user->data['user_id'] . '
							AND element = ' . UH_UPM . '
							AND item IN (' . implode(',', $rows) . ')';
					$db->sql_query($sql);
				}
			}
		}
		else
		{
			$user->setup();
			
			$return_message = parse_error($error) . '<br /><br /><br /><a href="' . $ref . '">' . $user->lang['CLICK_RETURN_LASTPAGE'] . '</a>';
			trigger_error($return_message);
		}
		
		return;
	}
	
	//
	// View comments
	//
	function view($start, $start_field, $total_items, $items_pp, $tpl_prefix = '', $pag_prefix = '', $pag_lang_prefix = '', $simple_pagination = false)
	{
		global $config, $user, $template;
		
		if ($tpl_prefix == '')
		{
			$tpl_prefix = 'posts';
		}
		
		$ref = $this->ref;
		$this->ref = preg_replace('#^/?(.*?)/?$#', '\1', $this->ref);
		$this->param = explode('/', $this->ref);
		$this->ref = $ref;
		
		if (!isset($start))
		{
			$start = intval(request_var($start_field, 0));
		}
		
		// $db->return_on_error = true;
		if (!$result = $db->sql_query($this->data['SQL']))
		{
			return;
		}
		// $db->return_on_error = false;

		if ($row = $db->sql_fetchrow($result))
		{
			if (!isset($this->data['A_LINKS_CLASS']))
			{
				$this->data['A_LINKS_CLASS'] = '';
			}
			if (!isset($this->data['ARTISTS_NEWS']))
			{
				$this->data['ARTISTS_NEWS'] = FALSE;
			}
			if (!isset($this->data['CONTROL']))
			{
				$this->data['CONTROL'] = array();
			}
			
			$sizeof_controls = sizeof($this->data['CONTROL']);
			
			$template->assign_block_vars($tpl_prefix, array());
			
			$controls_data = array();
			$user_profile = array();
			do
			{
				$uid = $row['user_id'];
				if (!isset($user_profile[$uid]) || ($uid == GUEST))
				{
					$user_profile[$uid] = $this->user_profile($row);
				}
				
				$topic_title = (isset($row['topic_title']) && $row['topic_title'] != '') ? $row['topic_title'] : '';
				if ($topic_title == '')
				{
					$topic_title = (isset($row['post_subject']) && $row['post_subject'] != '') ? $row['post_subject'] : '';
				}
				
				if (!empty($topic_title))
				{
					$topic_title = ($this->data['ARTISTS_NEWS']) ? preg_replace('#(.*?): (.*?)#', '\\2', $topic_title) : $topic_title;
				}
				
				$data = array(
					'POST_ID' => $row['post_id'],
					'DATETIME' => $user->format_date($row['post_time']),
					'SUBJECT' => $topic_title,
					'MESSAGE' => $this->parse_message($row['post_text'], $this->data['A_LINKS_CLASS']),
					'REPLIES' => ($this->data['ARTISTS_NEWS']) ? $row['topic_replies'] : 0,
					'S_DELETE' => false
				);
				
				if (isset($this->data['USER_ID_FIELD']) && ($user->data['is_founder'] || ($user->data['user_id'] === $row[$this->data['USER_ID_FIELD']])))
				{
					$data['S_DELETE'] = sprintf($this->data['S_DELETE_URL'], $row['post_id']);
				}
				
				foreach ($user_profile[$uid] as $key => $value)
				{
					$data[strtoupper($key)] = $value;
				}
				
				$template->assign_block_vars($tpl_prefix . '.item', $data);
				$template->assign_block_vars($tpl_prefix . '.item.' . (($uid != GUEST) ? 'username' : 'guestuser'), array());
				
				if ($sizeof_controls)
				{
					$template->assign_block_vars($tpl_prefix . '.item.controls', array());
					
					foreach ($this->data['CONTROL'] as $block => $block_data)
					{
						foreach ($block_data as $item => $item_data)
						{
							$controls_data[$item_data['ID']][$item] = sprintf($item_data['URL'], $row[$item_data['ID']]);
						}
						$template->assign_block_vars($tpl_prefix . '.item.controls.' . $block, $controls_data[$item_data['ID']]);
					}
				}
			}
			while ($row = $db->sql_fetchrow($result));
			
			$f_pagination = ($simple_pagination) ? 'build_pagination' : 'build_num_pagination';
			$f_pagination($ref . $start_field . '%d/', $total_items, $items_pp, $start, $pag_prefix, $pag_lang_prefix);
		}
		
		return;
	}
	
	//
	// Get formatted member profile fields
	//
	function user_profile (&$row, $a_class = '', $unset_fields = false)
	{
		global $user, $config;
		static $all_ranks;
		
		if (!isset($this->users[$row['user_id']]) || $row['user_id'] == GUEST)
		{
			$data = array();
			foreach ($row as $key => $value)
			{
				if (strpos($key, 'user') === false && $key != 'post_id')
				{
					continue;
				}
				
				switch ($key)
				{
					case 'username':
						$data['username'] = ($row['user_id'] != GUEST) ? $value : '*' . (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
						break;
					case 'username_base':
						$data['profile'] = ($row['user_id'] != GUEST) ? s_link('m', $value) : '';
						break;
					case 'user_sig':
						$data[$key] = ($value != '') ? '<div' . ((isset($row['post_id'])) ? ' id="_sig_' . $row['post_id'] . '" ' : '') . 'class="lsig">' . $this->parse_message($value, $a_class) . '</div>' : '';
						break;
					case 'user_avatar':
						if ($row['user_id'] != GUEST) {
							if ($value != '') {
								$value = $config['avatar_path'] . '/' . $value;
							} else {
								$value = $config['default_avatar_users_url'];
							}
						} else {
							$value = $config['default_avatar_guests_url'];
						}
						
						$data[$key] = $value;
						break;
					case 'user_rank':
						if (!isset($all_ranks)) {
							$all_ranks = $user->init_ranks();
						}
						
						if ($row['user_id'] != GUEST) {
							if ($value) {
								for ($i = 0, $end = sizeof($all_ranks); $i < $end; $i++) {
									if (($row['user_rank'] == $all_ranks[$i]['rank_id']) && $all_ranks[$i]['rank_special']) {
										$ranks_e = explode('|', $all_ranks[$i]['rank_title']);
										$value = (isset($ranks_e[$row['user_gender']]) && ($ranks_e[$row['user_gender']] != '')) ? $ranks_e[$row['user_gender']] : $ranks_e[0];
									}
								}
							} else {
								if (isset($row['user_gender']) && isset($row['user_posts'])) {
									for ($i = 0, $end = sizeof($all_ranks); $i < $end; $i++) {
										if (($row['user_posts'] >= $all_ranks[$i]['rank_min']) && !$all_ranks[$i]['rank_special']) {
											$ranks_e = explode('|', $all_ranks[$i]['rank_title']);
											$value = (isset($ranks_e[$row['user_gender']]) && ($ranks_e[$row['user_gender']] != '')) ? $ranks_e[$row['user_gender']] : $ranks_e[0];
										}
									}
								} else {
									$value = '';
								}
							}
						} else {
							$value = $user->lang['GUEST'];
						}
						
						$data[$key] = $value;
						break;
					default:
						if ($value != '') {
							$data[$key] = $value;
						}
						break;
				}
			}
			
			if ($unset_fields !== false) {
				foreach ($unset_fields as $field) {
					unset($data[$field]);
				}
			}
			
			$this->users[$row['user_id']] = $data;
		}
		
		return $this->users[$row['user_id']];
	}
	
	//
	// Comments system functions
	//
	
	//
	// This function will prepare a posted message for
	// entry into the database.
	//
	function prepare($message)
	{
		global $config, $user;
		
		// Do some general 'cleanup' first before processing message,
		// e.g. remove excessive newlines(?), smilies(?)
		// Transform \r\n and \r into \n
		$match = array('#\r\n?#', '#sid=[a-z0-9]*?&amp;?#', "#([\n][\s]+){3,}#", "#(\.){3,}#", '#(script|about|applet|activex|chrome):#i');
		$replace = array("\n", '', "\n\n", '...', "\\1&#058;");
		$message = preg_replace($match, $replace, trim($message));
		
		if ($user->data['is_founder'] && preg_match('#\[chown\:([0-9a-z\_\-]+)\]#is', $message, $a_chown))
		{
			$sql = "SELECT *
				FROM _members
				WHERE username_base = '" . $db->sql_escape($a_chown[1]) . "'";
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$sql = 'UPDATE _members
					SET user_lastvisit = ' . time() . '
					WHERE user_id = ' . (int) $row['user_id'];
				$db->sql_query($sql);
				
				$user->data = $row;
			}
			$db->sql_freeresult($result);
			
			$message = str_replace('[chown:' . $a_chown[1] . ']', '', $message);
		}

		$allowed_tags = array('br', 'strong', 'ul', 'ol', 'li', 'em', 'small');
		$is_mod = $user->_team_auth('mod');
		
		if ($is_mod)
		{
			$allowed_tags = array_merge($allowed_tags, array('blockquote', 'object', 'param', 'a', 'h1', 'h2', 'h3', 'div', 'span', 'img', 'table', 'tr', 'td', 'th'));
		}
		
		if (isset($user->data['is_founder']) && $user->data['is_founder'])
		{
			$allowed_tags = array_merge($allowed_tags, array('script'));
		}
		
		$ptags = str_replace('*', '.*?', implode('|', $allowed_tags));
		$message = preg_replace('#&lt;(\/?)(' . $ptags . ')&gt;#is', '<$1$2>', $message);
		
		if ($is_mod)
		{
			if (preg_match_all('#&lt;(' . $ptags . ') (.*?)&gt;#is', $message, $in_quotes))
			{
				$repl = array('&lt;' => '<', '&gt;' => '>', '&quot;' => '"');
				
				foreach ($in_quotes[0] as $item)
				{
					$message = preg_replace('#' . preg_quote($item, '#') . '#is', str_replace(array_keys($repl), array_values($repl), $item), $message);
				}
			}
		}
		
		return $message;
	}
	
	function remove_quotes($message)
	{
		if (strstr($message, '<blockquote>'))
		{
			$message = trim(preg_replace('#^<br />#is', '', preg_replace("#<blockquote>.*?blockquote>(.*?)#is", '\\1', $message)));
		}
		return $message;
	}
	
	//
	// | Conversations System
	//
	
	//
	// Store new conversation
	//
	function store_dc($mode, $to, $from, $subject, $message, $can_reply = true, $can_email = false)
	{
		global $user;
		
		if ($mode == 'reply')
		{
			$insert = array(
				'parent_id' => (int) $to['parent_id'],
				'privmsgs_type' => PRIVMSGS_NEW_MAIL,
				'privmsgs_from_userid' => (int) $from['user_id'],
				'privmsgs_to_userid' => (int) $to['user_id'],
			);
		}
		else
		{
			$insert = array(
				'privmsgs_type' => PRIVMSGS_NEW_MAIL,
				'privmsgs_subject' => $subject,
				'privmsgs_from_userid' => (int) $from['user_id'],
				'privmsgs_to_userid' => (int) $to['user_id']
			);
		}
		
		$insert += array(
			'privmsgs_date' => time(),
			'msg_ip' => $user->ip,
			'privmsgs_text' => $this->prepare($message),
			'msg_can_reply' => (int) $can_reply
		);
		
		$db->sql_query('INSERT INTO _dc' . $db->sql_build_array('INSERT', $insert));
		$dc_id = $db->sql_nextid();
		
		if ($mode == 'reply')
		{
			$sql = 'UPDATE _dc SET root_conv = root_conv + 1, last_msg_id = ' . (int) $dc_id . '
				WHERE msg_id = ' . (int) $to['msg_id'];
			$db->sql_query($sql);
			
			$sql = 'UPDATE _dc SET msg_deleted = 0
				WHERE parent_id = ' . (int) $to['parent_id'];
			$db->sql_query($sql);
			
			$user->delete_unread(UH_NOTE, $to['parent_id']);
		}
		else
		{
			$sql = 'UPDATE _dc SET parent_id = ' . (int) $dc_id . ', last_msg_id = ' . (int) $dc_id . '
				WHERE msg_id = ' . (int) $dc_id;
			$db->sql_query($sql);
		}
		
		$user->save_unread(UH_NOTE, (($mode == 'reply') ? $to['parent_id'] : $dc_id), 0, $to['user_id']);
		
		//
		// Notify via email if user requires it
		//
		if ($mode == 'start' && $can_email && $user->data['user_email_dc'])
		{
			include_once('./interfase/emailer.php');
			$emailer = new emailer();
			
			$emailer->from('info@rockrepublik.net');
			$emailer->set_subject('Rock Republik: ' . $from['username'] . ' te ha enviado un mensaje');
			$emailer->use_template('dc_email');
			$emailer->email_address($to['user_email']);
			
			$dc_url = s_link('my', array('dc', 'read', $dc_id));
			
			$emailer->assign_vars(array(
				'USERNAME' => $to['username'],
				'SENT_BY' => $from['username'],
				'DC_URL' => $dc_url)
			);
			$emailer->send();
			$emailer->reset();
		}
		
		return $dc_id;
	}
	
	//
	// Delete conversation/s
	//
	function dc_delete($mark)
	{
		if (!is_array($mark) || !sizeof($mark))
		{
			return;
		}
		
		global $user;
		
		$sql_member = '((privmsgs_to_userid = ' . $user->data['user_id'] . ') OR (privmsgs_from_userid = ' . $user->data['user_id'] . '))';
		
		$sql = 'SELECT *
			FROM _dc
			WHERE parent_id IN (' . implode(',', array_map('intval', $mark)) . ')
				AND ' . $sql_member;
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$update_a = $delete_a = array();
			
			do
			{
				$var = ($row['msg_deleted'] && ($row['msg_deleted'] != $user->data['user_id'])) ? 'delete_a' : 'update_a';
				
				if (!isset(${$var}[$row['parent_id']]))
				{
					${$var}[$row['parent_id']] = true;
				}
			}
			while ($row = $db->sql_fetchrow($result));
			$db->sql_freeresult($result);
			
			//
			if (sizeof($update_a))
			{
				$sql = 'UPDATE _dc
					SET msg_deleted = ' . (int) $user->data['user_id'] . '
					WHERE parent_id IN (' . implode(',', array_map('intval', array_keys($update_a))) . ')
						AND ' . $sql_member;
				$db->sql_query($sql);
				
				$user->delete_unread(UH_NOTE, $update_a);
			}
			
			if (sizeof($delete_a))
			{
				$sql = 'DELETE FROM _dc
					WHERE parent_id IN (' . implode(',', array_map('intval', array_keys($delete_a))) . ')
						AND ' . $sql_member;
				$db->sql_query($sql);
				
				$user->delete_all_unread(UH_NOTE, $delete_a);
			}
		}
		
		return;
	}
	
	//
	// Message parser methods
	//
	
	function parse_message($message, $a_class = '')
	{
		$this->message = ' ' . $message . ' ';
		unset($message);
		
		$this->parse_flash();
		$this->parse_youtube();
		$this->parse_images();
		$this->parse_url();
		$this->parse_bbcode();
		$this->parse_smilies();
		$this->a_links($a_class);
		$this->d_links();
		$this->members_profile();
		$this->members_icon();
		$this->replace_blockquote();
		
		return str_replace("\n", '<br />', substr($this->message, 1, -1));
	}
	
	function replace_blockquote()
	{
		if (strstr($this->message, '<blockquote>'))
		{
			//$this->message = str_replace(array('<blockquote>', '</blockquote>'), array('<div class="msg-bq pad4 sub-color box2">', '</div><br />'), $this->message);
			//$this->message = str_replace(array('<blockquote>', '</blockquote>'), array('<blockquote>', '</blockquote>'), $this->message);
		}
	}
	
	function parse_html($message)
	{
		global $user, $cache;
		
		/*
		
		<img src="http://*" alt="*" />
		<img src="http://\1" alt="\2" />
		
		<a href="*">*</a>
		<a href="\1" target="_blank">\2</a>
		
		*/
		
		$html = array();
		$exclude = array();
		if (!$user->data['is_founder'])
		{
			$sql = 'SELECT *
				FROM _html_exclude
				WHERE html_member = ' . (int) $user->data['user_id'];
			$result = $db->sql_query($sql);
			
			if ($row_exlude = $db->sql_fetchrow($result))
			{
				$delete_expired = array();
				$current_time = time();
				do
				{
					if ($row['exclude_until'] > $current_time)
					{
						$exclude[] = $row_exclude['exclude_html'];
					}
					else
					{
						$delete_expired[] = $row_exclude['exclude_id'];
					}
				}
				while ($row_exlude = $db->sql_fetchrow($result));
			}
			$db->sql_freeresult($result);
		}
		
		if (!$html = $cache->get('html'))
		{
			$sql = 'SELECT *
				FROM _html';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				do
				{
					$html[$row['html_id']] = $row;
				}
				while ($row = $db->sql_fetchrow($result));
				$db->sql_freeresult($result);
				
				$cache->save('html', $html);
			}
		}
		
		if (sizeof($exclude))
		{
			foreach ($exclude as $item)
			{
				unset($html[$item]);
			}
		}
		
		
	}

	function parse_bbcode()
	{
		$orig = array('[sb]', '[/sb]');
		$repl = array('<blockquote>', '</blockquote>');

		$this->message = str_replace($orig, $repl, $this->message);
	}
	
	function parse_youtube()
	{
		if (preg_match_all('#(^|[\n ]|\()\[yt\:([0-9a-zA_Z\-\=\_\&]+)\]#i', $this->message, $match))
		{
			$this->message = preg_replace('#(^|[\n ]|\()\[yt\:([0-9a-zA_Z\-\=\_\&]+)\]#i', '$1<div id="yt_$2">Youtube video: http://www.youtube.com/watch?v=$2</div> <script type="text/javascript"> swfobject.embedSWF("http://www.youtube.com/v/$2", "yt_$2", "425", "350", "8.0.0", "expressInstall.swf"); </script>', $this->message);
			
		}
		
		return;
	}
	
	function parse_flash()
	{
		$p = '#(^|[\n ]|\()\[flash\:([\w]+?://.*?([^ \t\n\r<"\'\)]*)?)\:(\d+)\:(\d+)\]#ie';
		if (preg_match_all($p, $this->message, $match))
		{
			$this->message = preg_replace($p, '\'$1<div id="flash_"></div> <script type="text/javascript"> swfobject.embedSWF("$2", "flash_", "$4", "$5", "8.0.0", "expressInstall.swf"); </script>\'', $this->message);
		}
		
		return;
	}
	
	function parse_images()
	{
		// #(http|https|ftp)://(.*?\.)*?[a-z0-9\-]+?\.[a-z]{2,4}:?([0-9]*?).*?\.(gif|jpg|jpeg|png)#ie
		if (preg_match_all('#(^|[\n ]|\()(http|https|ftp)://([a-z0-9\-\.,\?!%\*_:;~\\&$@/=\+]+)(gif|jpg|jpeg|png)#ie', $this->message, $match))
		//if (preg_match_all('#https?://([a-z0-9\-\.,\?!%\*_:;~\\&$@/=\+]+)#ie', $this->message, $match))
		{
			$orig = $repl = array();
			foreach ($match[0] as $item)
			{
				$item = trim($item);
				$orig[] = '#(^|[\n ]|\()(' . preg_quote($item) . ')#i';
				$repl[] = '\\1<img src="' . $item . '" border="0" alt="" />';
			}
			
			if (sizeof($orig))
			{
				$this->message = preg_replace($orig, $repl, $this->message);
			}
		}
		
		return;
	}
	
	function parse_url()
	{
		global $config;
		
		if (!isset($this->options['url']))
		{
			global $user;
			
			$this->options['url'] = array(
				'orig' => array(
					'#(script|about|applet|activex|chrome):#is',
					'#(^|[\n ]|\()(' . preg_quote('http://' . $config['server_name'], '#') . ')/(.*?([^ \t\n\r<"\'\)]*)?)#is',
					'#(^|[\n ]|\()([\w]+?://.*?([^ \t\n\r<"\'\)]*)?)#ie',
					'#(^|[\n ]|\()(www\.[\w\-]+\.[\w\-.\~]+(?:/[^ \t\n\r<"\'\)]*)?)#ie',
					'#(^|[\n ]|\()([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)#ie'
				),
				'repl' => array(
					'\\1&#058;',
					'$1<a href="$2/$3">$2/$3</a>',
					"'\$1<a href=\"\$2\" target=\"_blank\">$2</a>'",
					"'\$1<a href=\"http://\$2\" target=\"_blank\">$2</a>'",
					"'\$1<a href=\"mailto:\$2\">$2</a>'"
				)
			);

			if (!$user->data['is_member'] || $user->data['is_bot'])
			{
				$this->options['url']['orig'][4] = '#(^|[\n ]|\()(([a-z0-9&\-_.]+?@)([\w\-]+\.([\w\-\.]+\.)?[\w]+))#se';
				$this->options['url']['repl'][4] = "'\$1<span class=\"red\">$3'.substr('$4', 0, 4).'...</span>'";
			}
		}
		
		$this->message = preg_replace($this->options['url']['orig'], $this->options['url']['repl'], $this->message);
		return;
	}
	
	function parse_smilies()
	{
		global $config;
		
		if (!isset($this->options['smilies']))
		{
			global $cache;
			
			if (!$smilies = $cache->get('smilies'))
			{
				$sql = 'SELECT *
					FROM _smilies
					ORDER BY LENGTH(code) DESC';
				$smilies = sql_rowset($sql);
				$cache->save('smilies', $smilies);
			}
			
			for ($i = 0, $end = sizeof($smilies); $i < $end; $i++)
			{
				//$this->options['smilies']['orig'][] = "/(?<=.\W|\W.|^\W)" . preg_quote($smilies[$i]['code'], "/") . "(?=.\W|\W.|\W$)/";
				//$this->options['smilies']['orig'][] = '#(?<=^|[\n ]|\.)' . preg_quote($smilies[$i]['code'], '#') . '#';
				// #(^|[\n ]|\() --- (?<=|[\n ]|\.)
				$this->options['smilies']['orig'][] = '#(^|[\n ]|\.|\()' . preg_quote($smilies[$i]['code'], '#') . '#';
				$this->options['smilies']['repl'][] = ' <img class="middle" src="' . $config['smilies_path'] . '/' . $smilies[$i]['smile_url'] . '" alt="' . $smilies[$i]['emoticon'] . '" />';
			}
		}
		
		if (sizeof($this->options['smilies']))
		{
			$this->message = preg_replace($this->options['smilies']['orig'], $this->options['smilies']['repl'], $this->message);
		}
		
		return;
	}
	
	function a_links ($style = 'ub-url')
	{
		if (!isset($this->options['a']))
		{
			global $cache;
			
			if (!$this->options['a']['match'] = $cache->get('ub_list'))
			{
				$sql = 'SELECT name
					FROM _artists
					ORDER BY name';
				$result = sql_rowset($sql);
				
				foreach ($result as $row) {
					$this->options['a']['match'][] = $row['name'];
				}
				
				$cache->save('ub_list', $this->options['a']['match']);
			}
		}
		
		if (preg_match_all('#\b(' . implode('|', $this->options['a']['match']) . ')\b#i', $this->message, $match))
		{
			foreach ($match[1] as $n)
			{
				$m = strtolower($n);
				$k = str_replace(array(' ', '_'), '', $m);
				if (!isset($this->options['a']['data'][$k]))
				{
					$this->options['a']['data'][$k] = ucwords($m);
				}
			}
			
			$orig = $repl = array();
			foreach ($this->options['a']['data'] as $sub => $real)
			{
				$orig[] = '#(^|\s)(?<=.\W|\W.|^\W)\b(' . preg_quote($real, "#") . ')\b(?=.\W|\W.|\W$)#is';
				$repl[] = '\\1<a href="' . s_link('a', $sub) . '">' . $real . '</a>';
			}
			
			$this->message = preg_replace($orig, $repl, $this->message);
		}
		
		return;
	}
	
	function d_links ($style = '')
	{
		global $user;
		
		if (!isset($this->options['downloads']))
		{
			global $cache;
			
			if (!$this->options['downloads']['list'] = $cache->get('downloads_list'))
			{
				global $db;
				
				$sql = 'SELECT a.name, a.subdomain, d.id, d.title
					FROM _artists a, _dl d
					WHERE a.ub = d.ub 
					ORDER BY d.id';
				$result = $db->sql_query($sql);
				
				while ($row = $db->sql_fetchrow($result))
				{
					$this->options['downloads']['list'][$row['id']] = $row;
				}
				$db->sql_freeresult($result);
				
				$cache->save('downloads_list', $this->options['downloads']['list']);
			}
		}
		
		if (preg_match_all('#\:d([0-9]+)(\*)?\:#', $this->message, $match))
		{
			$orig = array();
			$repl = array();
			foreach ($match[1] as $i => $download)
			{
				if (isset($this->options['downloads']['list'][$download]))
				{
					$show_a = (isset($match[2][$i]) && $match[2][$i] != '') ? TRUE : FALSE;
					$orig[] = ':d' . $download . $match[2][$i] . ':';
					$repl[] = '<a href="' . s_link('a', array($this->options['downloads']['list'][$download]['subdomain'], '9', $download)) . '" title="' . $this->options['downloads']['list'][$download]['name'] . ' - ' . $this->options['downloads']['list'][$download]['title'] . '">' . (($show_a) ? $this->options['downloads']['list'][$download]['name'] . ' - ' : '') . $this->options['downloads']['list'][$download]['title'] . '</a>';
				}
			}
			
			if (sizeof($orig))
			{
				$this->message = str_replace($orig, $repl, $this->message);
			}
		}
		
		return;
	}
	
	function members_profile ()
	{
		if (preg_match_all('#\:m([0-9a-zA-Z\_\- ]+)\:#ii', $this->message, $match))
		{
			$orig = array();
			$repl = array();
			foreach ($match[1] as $orig_member)
			{
				$member = get_username_base($orig_member);
				if (!isset($this->options['members'][$member]))
				{
					$this->options['members'][$member] = '<a href="' . s_link('m', $member) . '">' . $orig_member . '</a>';
				}
				
				$orig[] = ':m' . $orig_member . ':';
				$repl[] = $this->options['members'][$member];
			}
			$this->message = str_replace($orig, $repl, $this->message);
		}
		
		return;
	}
	
	function members_icon ()
	{
		global $config;
		
		if (preg_match_all('#\:i([0-9a-zA-Z\_\- ]+)\:#si', $this->message, $match))
		{
			$orig = array();
			$repl = array();
			$formats = array('.jpg', '.gif', '.png');
			
			foreach ($match[1] as $orig_member)
			{
				$member = get_username_base($orig_member);
				if (!isset($this->options['icons'][$member]))
				{
					for ($i = 0, $end = sizeof($formats); $i < $end; $i++)
					{
						$icon_file = $config['avatar_path'] . '/' . $member . $formats[$i];
						if (@file_exists('..' . $icon_file))
						{
							$this->options['icons'][$member] = '<a href="' . s_link('m', $member) . '" title="' . $orig_member . '"><img src="' . $icon_file . '" /></a>';
							break;
						}
					}
				}
				
				$orig[] = ':i' . $orig_member . ':';
				$repl[] = (isset($this->options['icons'][$member])) ? $this->options['icons'][$member] : '<a href="' . s_link('m', get_username_base($orig_member)) . '">' . $orig_member . '</a>';
			}
			
			$this->message = str_replace($orig, $repl, $this->message);
		}
		
		return;
	}
}

?>