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

/*

// * _chat_ch
...
CH_ID				MEDIUMINT(8)
CH_NAME			VARCHAR(25)
CH_DESC			VARCHAR(255)
CH_FOUNDER	MEDIUMINT(8)
CH_TYPE			TINYINT(2)
CH_AUTH			TINYINT(2)
CH_USERS		MEDIUMINT(5)
CH_DEF			TINYINT(1)
CH_IP				VARCHAR(40)
CH_LOCKED		TINYINT(1)

// CH_TYPE
...
0						OFFICIAL
1						USER_CUSTOM

// CH_AUTH
...
0						ALL
1						REG
2						PRIVATE
3						HIDDEN

-----------------------
// * CHAT_SESSIONS_TABLE
...
SESSION_ID				VARCHAR(32)
SESSION_USER_ID		MEDIUMINT(8)
SESSION_CH_ID			MEDIUMINT(8)
SESSION_TYPE			TINYINT(2)
SESSION_IP				VARCHAR(8)
SESSION_START			INT(11)
SESSION_TIME			INT(11)
SESSION_LAST_MESSAGE	INT(11)

-----------------------
// * CHAT_AUTH_TABLE
...
USER_CHANNEL				MEDIUMINT(8)
USER_ID							MEDIUMINT(8)
USER_AUTH						TINYINT(2)

-----------------------
// * CHAT_MSG_TABLE
...
MSG_ID							INT(11)
MSG_CH							INT(11)
MSG_IGNORE					INT(11)
MSG_MEMBER_ID				INT(11)
MSG_TEXT						TEXT
MSG_TIME						INT(11)
MSG_IP							VARCHAR(40)


*/

class _chat
{
	var $data = array();
	var $rooms = array();
	var $users = array();
	var $comments;
	
	function _chat()
	{
		require('./interfase/comments.php');
		$this->comments = new _comments;
		
		return;
	}
	
	function m_rooms()
	{
		$cat = $this->get_cats();
		
		if (!sizeof($cat))
		{
			return;
		}
		
		global $db, $user, $config, $template;
		
		$sql = 'SELECT *
			FROM _chat_ch
			ORDER BY ch_def DESC, ch_type, ch_name';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			do
			{
				$this->rooms[] = $row;
			}
			while ($row = $db->sql_fetchrow($result));
			$db->sql_freeresult($result);
			
			$template->assign_block_vars('chat', array());
			
			foreach ($cat as $cat_data)
			{
				$template->assign_block_vars('chat.cat', array(
					'LABEL' => $cat_data['cat_name'])
				);
				
				$rooms = 0;
				foreach ($this->rooms as $channel)
				{
					if ($cat_data['cat_id'] != $channel['cat_id'])
					{
						continue;
					}
					
					$ch_auth = ($channel['ch_auth']) ? '* ' : '';
					
					$template->assign_block_vars('chat.cat.channel', array(
						'VALUE' => $channel['ch_int_name'],
						'LABEL' => $ch_auth . $channel['ch_name'],
						'SELECTED' => ($channel['ch_def']) ? ' selected' : '')
					);
					$rooms++;
				}
				
				if (!$rooms)
				{
					$template->assign_block_vars('chat.cat.noch', array());
				}
			}
		}
		
		return;
	}
	
	function get_ch_listing($cat)
	{
		if (!sizeof($cat))
		{
			return;
		}
		
		global $db, $template;
		
		$sql = 'SELECT ch.*, m.username, m.username_base, m.user_color
			FROM _chat_ch ch, _members m
			WHERE ch.ch_founder = m.user_id
			ORDER BY ch_def DESC, cat_id, ch_users DESC';
		$result = $db->sql_query($sql);
		
		$ch = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$ch[] = $row;
		}
		$db->sql_freeresult($result);
		
		$chatters = 0;
		foreach ($cat as $cat_data)
		{
			$template->assign_block_vars('cat', array(
				'NAME' => $cat_data['cat_name'])
			);
			
			$rooms = 0;
			foreach ($ch as $ch_data)
			{
				if ($cat_data['cat_id'] != $ch_data['cat_id'])
				{
					continue;
				}
				
				$chatters += $ch_data['ch_users'];
				
				$template->assign_block_vars('cat.item', array(
					'U_CHANNEL' => s_link('chat', $ch_data['ch_int_name']),
					'CH_NAME' => $ch_data['ch_name'],
					'CH_DESC' => $ch_data['ch_desc'],
					'CH_USERS' => $ch_data['ch_users'],
					'USERNAME' => $ch_data['username'],
					'U_USERNAME' => s_link('m', $ch_data['username_base']),
					'USER_COLOR' => $ch_data['user_color'])
				);
				$rooms++;
			}
			
			if (!$rooms)
			{
				$template->assign_block_vars('cat.no_rooms', array());
			}
		}
		
		return $chatters;
	}
	
	function get_cats()
	{
		global $cache, $db;
		
		$cat = array();
		if (!$cat = $cache->get('chat_cat'))
		{
			$sql = 'SELECT *
				FROM _chat_cat
				ORDER BY cat_order';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				$cat[] = $row;
			}
			$db->sql_freeresult($result);
			
			$cache->save('chat_cat', $cat);
		}
		
		return $cat;
	}
	
	function _setup()
	{
		global $db, $user;
		
		$ch = request_var('ch', '');
		if (!empty($ch))
		{
			if (preg_match('/([0-9a-z\-]+)/', $ch))
			{
				$sql = 'SELECT *
					FROM _chat_ch ' . "
					WHERE ch_int_name = '" . $db->sql_escape($ch) . "'
					LIMIT 1";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$row['ch_id'] = (int) $row['ch_id'];
					$this->data = $row;
					
					$db->sql_freeresult($result);
					
					return true;
				}
			}
			
			fatal_error();
		}
		
		return false;
	}
	
	function process_data($csid, $mode)
	{
		global $db, $user, $config;
		
		if (empty($csid))
		{
			return false;
		}
		
		/*
		MSG_ID							INT(11)
		MSG_CH							INT(11)
		MSG_IGNORE					INT(11)
		MSG_MEMBER_ID				INT(11)
		MSG_TEXT						TEXT
		MSG_TIME						INT(11)
		MSG_IP							VARCHAR(40)
		*/
		
		$last_msg = request_var('last_msg', 0);
		
		switch ($mode)
		{
			case 'logout':
				$sql = "SELECT *
					FROM _chat_sessions
					WHERE session_id = '" . $db->sql_escape($csid) . "'";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$sql = 'UPDATE _chat_ch
						SET ch_users = ch_users - 1
						WHERE ch_id = ' . (int) $row['session_ch_id'];
					$db->sql_query($sql);
					
					$sql = "DELETE FROM _chat_sessions
						WHERE session_id = '" . $db->sql_escape($csid) . "'";
					$db->sql_query($sql);
					
					$this->_message($row['session_ch_id'], $user->data['user_id'], sprintf($user->lang['CHAT_MEMBER_LOGOUT'], $user->data['username']));
				}
				
				redirect(s_link('chat'));
				break;
			case 'send':
				$message = request_var('message', '');
				
				if (empty($message))
				{
					return false;
				}
				
				$this->_message($this->data['ch_id'], 0, $message);
				
			case 'get':
				$messages = array();
				$members = array();
				
				$sql = 'SELECT c.*, m.username, m.user_color
					FROM _chat_msg c, _members m
					WHERE c.msg_ch = ' . (int) $this->data['ch_id'] . '
						AND c.msg_ignore <> ' . (int) $user->data['user_id'] . '
						AND c.msg_id > ' . (int) $last_msg . '
						AND c.msg_time > ' . $this->data['session_start'] . '
						AND c.msg_member_id = m.user_id
					ORDER BY c.msg_time ASC';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					do
					{
						$messages[] = $row;
					}
					while ($row = $db->sql_fetchrow($result));
				}
				$db->sql_freeresult($result);
				
				$sql = 'SELECT m.user_id, m.username, m.username_base, m.user_color
					FROM _chat_sessions s, _members m
					WHERE s.session_ch_id = ' . (int) $this->data['ch_id'] . '
						AND s.session_member = m.user_id
					ORDER BY m.username';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					do
					{
						$members[] = $row;
					}
					while ($row = $db->sql_fetchrow($result));
				}
				$db->sql_freeresult($result);
				
				$so_messages = sizeof($messages);
				$so_members = sizeof($members);
				
				//
				if ($so_messages || $so_members)
				{
					header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); 
					header("Last-Modified: " . gmdate( "D, d M Y H:i:s" ) . "GMT" ); 
					header("Cache-Control: no-cache, must-revalidate" ); 
					header("Pragma: no-cache" );
					header("Content-Type: text/xml; charset=iso-8859-1");
					
					$xmlre = '<?xml version="1.0" ?><ROOT>';
					
					if ($so_messages)
					{
						foreach ($messages as $row)
						{
							$message = $this->comments->parse_message($row['msg_text'], 'bold red');
							
							$xmlre .= '<message id="' . $row['msg_id'] . '" sid="' . $this->data['session_id'] . '">';
							
							if (!$row['msg_ignore'])
							{
								if (preg_match("#\b(" . $user->data['username'] . ")\b#i", $message))
								{
									$message = '<span class="rkc_self">' . str_replace('\"', '"', substr(@preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "@preg_replace('#\b(" . str_replace('\\', '\\\\', $user->data['username']) . ")\b#i', '<span class=\"sgray bold\">\\\\1</span>', '\\0')", '>' . $message . '<'), 1, -1)) . '</span>';
								}
								
								$message = '<strong style="color: #' . $row['user_color'] . '">&lt;' . $row['username'] . '&gt;</strong> ' . $message . '<br />';
							}
							
							$xmlre .= '<smsg>' . rawurlencode($message) . '</smsg>';
							
							$xmlre .= '</message>';
						}
					}
					
					if ($so_members)
					{
						foreach ($members as $row)
						{
							$xmlre .= '<member user_id="' . $row['user_id'] . '">';
							
							$xmlre .= '<nick>' . $row['username'] . '</nick><prof>' . s_link('m', $row['username_base']) . '</prof>';
							
							$xmlre .= '</member>';
						}
					}
					
					$xmlre .= '</ROOT>';
					echo $xmlre;
				}
				
				//
				//
				//
				/*
				$sql = 'SELECT c.*, m.username, m.user_color
					FROM _chat_msg c, _members m
					WHERE c.msg_ch = ' . (int) $this->data['ch_id'] . '
						AND c.msg_ignore <> ' . (int) $user->data['user_id'] . '
						AND c.msg_id > ' . (int) $last_msg . '
						AND c.msg_time > ' . $this->data['session_start'] . '
						AND c.msg_member_id = m.user_id
					ORDER BY c.msg_time ASC';
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					do
					{
						
					}
					while ($row = $db->sql_fetchrow($result));
					$db->sql_fetchrow($result);
				}
				**/
				break;
		}
		
		return;
	}
	
	function auth()
	{
		global $user, $db;
		
		if ($user->data['is_founder'] || ($this->data['ch_founder'] == $user->data['user_id']))
		{
			return true;
		}
		
		//
		// Check friends
		//
		if ($this->data['ch_auth'] == 2)
		{
			$sql = 'SELECT *
				FROM _members_friends
				WHERE (user_id = ' . (int) $this->data['ch_founder'] . ' AND buddy_id = ' . (int) $user->data['user_id'] . ')
					OR (user_id = ' . (int) $user->data['user_id'] . ' AND buddy_id = ' . (int) $this->data['ch_founder'] . ')';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$db->sql_freeresult($result);
				return true;
			}
			return false;
		}
		
		/*
		0 - No Access
		1 - Founder
		2 - Member
		*/
		
		$sql = 'SELECT *
			FROM _chat_auth
			WHERE ch_id = ' . (int) $this->data['ch_id'] . '
				AND ch_user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			switch ($row['ch_auth'])
			{
				case 0:
					return false;
					break;
				case 1:
					$this->data['is_founder'] = true;
					break;
				case 2:
					$this->data['is_member'] = true;
					break;
			}
		}
		else
		{
			if ($this->data['ch_auth'])
			{
				return false;
			}
		}
		$db->sql_freeresult($result);
		
		return true;
	}
	
	function session($sid)
	{
		global $db, $user, $config;
		
		/*
		SESSION_ID				VARCHAR(32)
		SESSION_MEMBER		MEDIUMINT(8)
		SESSION_CH_ID			MEDIUMINT(8)
		SESSION_IP				VARCHAR(8)
		SESSION_START			INT(11)
		SESSION_TIME			INT(11)
		SESSION_LAST_MESSAGE	INT(11)
		*/
		
		$ttime = time();
		
		$updated = FALSE;
		$sql = "SELECT *
			FROM _chat_sessions
			WHERE /*session_id = '" . $db->sql_escape($sid) . "'
				AND */session_member = " . (int) $user->data['user_id'] . '
				AND session_ch_id = ' . (int) $this->data['ch_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$last_msg = request_var('last_msg', 0);
			
			$sql = 'UPDATE _chat_sessions
				SET session_time = ' . $ttime . ', session_last_msg = ' . (int) $last_msg . "
				WHERE session_id = '" . $db->sql_escape($sid) . "'
					AND session_member = " . (int) $user->data['user_id'] . '
					AND session_ch_id = ' . (int) $this->data['ch_id'];
			$db->sql_query($sql);
			
			$row['session_time'] = $ttime;
			$row['session_last_msg'] = $last_msg;
			$this->data += $row;
			
			$updated = TRUE;
		}
		$db->sql_freeresult($result);
		
		if ($updated)
		{
			return true;
		}
		
		$insert_data = array(
			'session_id' => md5(unique_id()),
			'session_member' => (int) $user->data['user_id'],
			'session_ch_id' => (int) $this->data['ch_id'],
			'session_ip' => $user->ip,
			'session_start' => (int) $ttime,
			'session_time' => (int) $ttime,
			'session_last_msg' => 0
		);
		$db->sql_query('INSERT INTO _chat_sessions' . $db->sql_build_array('INSERT', $insert_data));
		
		$sql = 'UPDATE _chat_ch
			SET ch_users = ch_users + 1
			WHERE ch_id = ' . (int) $this->data['ch_id'];
		$db->sql_query($sql);
		
		$this->_message($this->data['ch_id'], $user->data['user_id'], sprintf($user->lang['CHAT_MEMBER_ENTERED'], $user->data['username']));
		
		$this->data += $insert_data;
		
		$this->sys_clean();
		
		return;
	}
	
	function _message($ch, $ignore, $message)
	{
		global $db, $user;
		
		$insert_data = array(
			'msg_ch' => (int) $ch,
			'msg_ignore' => (int) $ignore,
			'msg_member_id' => (int) $user->data['user_id'],
			'msg_text' => (string) $this->comments->prepare($message),
			'msg_time' => (int) time(),
			'msg_ip' => $user->ip
		);
		$db->sql_query('INSERT INTO _chat_msg' . $db->sql_build_array('INSERT', $insert_data));
		
		return $insert_data;
	}
	
	function window()
	{
		global $db, $user, $config, $template;
		
		$template->assign_vars(array(
			'CH_SID' => $this->data['session_id'],
			'CH_INT_NAME' => $this->data['ch_int_name'],
			'CH_NAME' => $this->data['ch_name'])
		);
		
		if ($user->data['user_id'] === $this->data['ch_founder'])
		{
			// TEMP
			// $template->assign_block_vars('ch_manage', array());
		}
	}
	
	function sys_clean()
	{
		global $db;
		
		$ttime = time();
		
		$sql = 'DELETE FROM _chat_msg
			WHERE msg_time < ' . ($ttime - 3600);
		$db->sql_query($sql);
		
		//
		//
		$sql = 'SELECT s.*, m.username
			FROM _chat_sessions s, _members m
			WHERE s.session_time < ' . (int) ($ttime - 300) . '
				AND s.session_member = m.user_id';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			global $user;
			
			$update_ch = array();
			$delete_sessions = array();
			$show_members = array();
			
			do
			{
				$chid = $row['session_ch_id'];
				if (!isset($update_ch[$chid]))
				{
					$update_ch[$chid] = 0;
				}
				
				$update_ch[$chid]++;
				$show_members[$chid][$row['session_member']] = $row['username'];
				$delete_sessions[] = "'" . $db->sql_escape($row['session_id']) . "'";
			}
			while ($row = $db->sql_fetchrow($result));
			
			foreach ($update_ch as $ch_id => $number)
			{
				$sql = 'UPDATE _chat_ch
					SET ch_users = ch_users - ' . (int) $number . '
					WHERE ch_id = ' . (int) $ch_id;
				$db->sql_query($sql);
				
				foreach ($show_members[$ch_id] as $user_id => $username)
				{
					$this->_message($ch_id, $user_id, sprintf($user->lang['CHAT_MEMBER_TIMEOUT'], $username));
				}
			}
			
			$sql = 'DELETE FROM _chat_sessions
				WHERE session_id IN (' . implode(',', $delete_sessions) . ')';
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);
		
		//
		//
		//
		/*
		$sql = 'DELETE FROM _chat_sessions
			WHERE session_start < ' . ($ttime - 43200);
		$db->sql_query($sql);
		*/
	}
}

?>