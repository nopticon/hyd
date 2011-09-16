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

function user_profile($row) {
	global $user;
	static $user_profile = array();
	
	if (!isset($user_profile[$row['user_id']]) || $row['user_id'] == GUEST) {
		if ($row['user_id'] != GUEST) {
			$user_profile[$row['user_id']]['username'] = $row['username'];
			$user_profile[$row['user_id']]['profile'] = s_link('m', $row['username_base']);
			$user_profile[$row['user_id']]['color'] = $row['user_color'];
		} else {
			$user_profile[$row['user_id']]['username'] = ($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST'];
			$user_profile[$row['user_id']]['profile'] = '';
			$user_profile[$row['user_id']]['color'] = $row['user_color'];
		}
	}
	
	return $user_profile[$row['user_id']];
}

class unread {
	public function __construct() {
		return;
	}
	
	public function conversations() {
		global $user, $template;
		
		$sql = 'SELECT c.*, c2.privmsgs_date, m.user_id, m.username, m.username_base, m.user_color
			FROM _members_unread u, _dc c, _dc c2, _members m
			WHERE u.user_id = ?
				AND u.element = ?
				AND u.item = c.msg_id 
				AND c.last_msg_id = c2.msg_id
				AND c2.privmsgs_from_userid = m.user_id 
			ORDER BY c2.privmsgs_date DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_NOTE));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.notes', array(
					'ELEMENT' => UH_NOTE)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.notes.item', array(
				'S_MARK_ID' => $row['parent_id'],
				'U_READ' => s_link('my', array('dc', 'read', $row['last_msg_id'])) . '#' . $row['last_msg_id'],
				'SUBJECT' => $row['privmsgs_subject'],
				'DATETIME' => $user->format_date($row['privmsgs_date']),
				'USER_ID' => $row['user_id'],
				'USERNAME' => $row['username'],
				'USER_COLOR' => $row['user_color'],
				'U_USERNAME' => $user_profile['profile'])
			);
		}
		
		return true;
	}
	
	public function friends() {
		global $user, $template;
		
		$sql = 'SELECT u.item, u.datetime, m.user_id, m.username, m.username_base, m.user_color, m.user_rank
			FROM _members_unread u, _members m
			WHERE u.user_id = ?
				AND u.element = ?
				AND u.item = m.user_id
			ORDER BY u.datetime DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_FRIEND));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.friends', array(
					'ELEMENT' => UH_FRIEND)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.friends.item', array(
				'S_MARK_ID' => $row['user_id'],
				'U_PROFILE' => s_link('new', array(UH_FRIEND, $row['user_id'])),
				'POST_TIME' => $user->format_date($row['datetime']),
				'USERNAME' => $row['username'],
				'USER_COLOR' => $row['user_color'])
			);
		}
		
		return true;
	}
	
	public function members_posts() {
		global $user, $template;
		
		$sql = 'SELECT p.*, u.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _members_unread u, _members_posts p, _members m
			WHERE u.user_id = ?
				AND u.element = ?
				AND u.item = p.post_id
				AND p.poster_id = m.user_id
			ORDER BY p.post_time DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_UPM));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.userpagem', array(
					'ELEMENT' => UH_UPM)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.userpagem.item', array(
				'S_MARK_ID' => $row['post_id'],
				'U_PROFILE' => s_link('new', array(UH_UPM, $row['post_id'])),
				'POST_TIME' => $user->format_date($row['datetime']),
				'USERNAME' => $row['username'],
				'USER_COLOR' => $row['user_color'])
			);
		}
		
		return true;
	}
	
	public function artists_news() {
		global $user, $template;
		
		$sql = 'SELECT t.*
			FROM _members_unread u, _forum_topics t
			WHERE u.user_id = ?
				AND u.element = ?
				AND u.item = t.topic_id
			ORDER BY t.topic_time DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_N));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.a_news', array(
					'ELEMENT' => UH_N)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.a_news.item', array(
				'S_MARK_ID' => $row['topic_id'],
				'POST_URL' => s_link('new', array(UH_N, $row['topic_id'])),
				'POST_TITLE' => $row['topic_title'],
				'POST_TIME' => $user->format_date($row['topic_time']))
			);
		}
		
		return true;
	}
	
	public function site_news() {
		global $user, $template;
		
		$sql = 'SELECT n.*
			FROM _members_unread u, _news n
			WHERE u.user_id = ?
				AND u.element = ?
				AND u.item = n.news_id
			ORDER BY n.post_time DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_GN));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.news', array(
					'ELEMENT' => UH_GN)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.news.item', array(
				'S_MARK_ID' => $row['news_id'],
				'POST_URL' => s_link('new', array(UH_GN, $row['news_id'])),
				'POST_TITLE' => $row['post_subject'],
				'POST_TIME' => $user->format_date($row['post_time']))
			);
		}
		
		return true;
	}
	
	public function artists() {
		global $user, $template;
		
		$sql = 'SELECT a.ub, a.name, a.datetime 
			FROM _members_unread u, _artists a 
			WHERE u.user_id = ? 
				AND u.element = ? 
				AND u.item = a.ub 
			ORDER BY name';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_A));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.artists', array(
					'ELEMENT' => UH_A)
				);
			}
			
			$template->assign_block_vars('items.artists.item', array(
				'S_MARK_ID' => $row['ub'],
				'UB_URL' => s_link('new', array(UH_A, $row['ub'])),
				'NAME' => $row['name'],
				'POST_TIME' => $user->format_date($row['datetime']))
			);
		}
		
		return true;
	}
	
	public function artists_comments() {
		global $user, $template;
		
		$sql = 'SELECT b.subdomain, b.name, p.*, m.user_id, m.username, m.username_base, m.user_color 
			FROM _members_unread u, _artists b, _artists_posts p, _members m 
			WHERE u.user_id = ? 
				AND u.element = ? 
				AND u.item = p.post_id
				AND b.ub = p.post_ub 
				AND p.poster_id = m.user_id 
				AND p.post_active = 1 
			ORDER BY p.post_id DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_C));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.a_messages', array(
					'ELEMENT' => UH_C)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.a_messages.item', array(
				'S_MARK_ID' => $row['post_id'],
				'ITEM_URL' => s_link('new', array(UH_C, $row['post_id'])),
				'UB_URL' => s_link('a', $row['subdomain']),
				'UB' => $row['name'],
				'DATETIME' => $user->format_date($row['post_time']),
				'USER_ID' => $row['user_id'],
				'USER_COLOR' => $user_profile['color'],
				'USER_PROFILE' => $user_profile['profile'],
				'USERNAME' => $user_profile['username'])
			);
		}
		
		return true;
	}
	
	public function artists_fav() {
		global $user, $template;
		
		$sql = 'SELECT f.fan_id, f.joined, a.name, a.subdomain, m.user_id, m.username, m.username_base, m.user_color
			FROM _members_unread u, _artists a, _artists_fav f, _members m
			WHERE u.user_id = ?
				AND u.element = ?
				AND u.item = f.fan_id
				AND f.ub = a.ub
				AND f.user_id = m.user_id
			ORDER BY f.joined DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_AF));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.a_fav', array(
					'ELEMENT' => UH_AF)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.a_fav.item', array(
				'S_MARK_ID' => $row['fan_id'],
				'ITEM_URL' => s_link('new', array(UH_AF, $row['fan_id'])),
				'UB_URL' => s_link('a', $row['subdomain']),
				'UB' => $row['name'],
				'POST_TIME' => $user->format_date($row['joined']),
				'USER_ID' => $row['user_id'],
				'USER_COLOR' => $user_profile['color'],
				'USER_PROFILE' => $user_profile['profile'],
				'USERNAME' => $user_profile['username'])
			);
		}
		
		return true;
	}
	
	public function downloads() {
		global $user, $template;
		
		$sql = 'SELECT b.ub, b.subdomain, b.name, d.id, d.ud AS ud_type, d.title, d.date 
			FROM _members_unread u, _artists b, _dl d 
			WHERE u.user_id = ? 
				AND u.element = ?
				AND u.item = d.id 
				AND d.ub = b.ub 
			ORDER BY d.id DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_D));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.downloads', array(
					'ELEMENT' => UH_D)
				);
			}
			
			$download_type = $downloads->dl_type($row['ud_type']);
			
			$template->assign_block_vars('items.downloads.item', array(
				'S_MARK_ID' => $row['id'],
				'UB_URL' => s_link('a', $row['subdomain']),
				'UD_URL' => s_link('new', array(UH_D, $row['id'])),
				'UD_TYPE' => $download_type['av'],
				'DATETIME' => $user->format_date($row['date']),
				'UB' => $row['name'],
				'UD' => $row['title'])
			);
		}
		
		return true;
	}
	
	public function downloads_comments() {
		global $user, $template, $downloads;
		
		$sql = "SELECT b.ub, b.subdomain, b.name, d.id AS dl_id, d.ud AS ud_type, d.title, m.*, u.user_id, u.username, u.username_base, u.user_color
			FROM _members_unread ur, _artists b, _dl d, _dl_posts m, _members u 
			WHERE ur.user_id = " . $user->data['user_id'] . " 
				AND ur.element = " . UH_M . " 
				AND ur.item = m.post_id 
				AND m.download_id = d.id 
				AND d.ub = b.ub 
				AND m.poster_id = u.user_id 
				AND m.post_active = 1 
			ORDER BY m.post_id DESC";
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_M));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.d_messages', array(
					'ELEMENT' => UH_M)
				);
			}
			
			$download_type = $downloads->dl_type($row['ud_type']);
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.d_messages.item', array(
				'S_MARK_ID' => $row['post_id'],
				'ITEM_URL' => s_link('new', array(UH_M, $row['post_id'])),
				'UB_URL' => s_link('a', $row['subdomain']),
				'UD_URL' => s_link('a', array($row['subdomain'], 9, $row['dl_id'])),
				'UD_TYPE' => $download_type['av'],
				'UB' => $row['name'],
				'UD' => $row['title'],
				'POST_TIME' => $user->format_date($row['post_time']),
				'USER_ID' => $row['user_id'],
				'USER_COLOR' => $user_profile['color'],
				'USER_PROFILE' => $user_profile['profile'],
				'USERNAME' => $user_profile['username'])
			);
		}
		
		return true;
	}
	
	public function board() {
		global $user, $template;
		
		$sql = 'SELECT t.*, f.forum_alias, f.forum_id, f.forum_name, p.post_id, p.post_username, p.post_time, m.user_id, m.username, m.username_base, m.user_color 
			FROM _members_unread u, _forums f, _forum_topics t, _forum_posts p, _members m 
			WHERE u.user_id = ? 
				AND f.forum_id NOT IN (??)
				AND u.element = ? 
				AND u.item = t.topic_id 
				AND t.topic_id = p.topic_id 
				AND t.topic_last_post_id = p.post_id 
				AND t.forum_id = f.forum_id 
				AND p.poster_id = m.user_id 
			ORDER BY t.topic_announce DESC, p.post_time DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], '22' . forum_for_team_not(), UH_T));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.forums', array(
					'ELEMENT' => UH_T)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.forums.item', array(
				'S_MARK_ID' => $row['topic_id'],
				'FOR_MODS' => in_array($row['forum_id'], forum_for_team_array()),
				'TOPIC_URL' => s_link('post', $row['post_id']) . '#' . $row['post_id'],
				'TOPIC_TITLE' => $row['topic_title'],
				'TOPIC_REPLIES' => $row['topic_replies'],
				'TOPIC_COLOR' => $row['topic_color'],
				'FORUM_URL' => s_link('forum', $row['forum_alias']),
				'FORUM_NAME' => $row['forum_name'],
				'DATETIME' => $user->format_date($row['post_time']),
				'USER_ID' => $row['user_id'],
				'USER_COLOR' => $user_profile['color'],
				'USER_PROFILE' => $user_profile['profile'],
				'USERNAME' => $user_profile['username'])
			);
		}
		
		return true;
	}
	
	public function events() {
		return true;
	}
	
	public function members() {
		global $user, $template;
		
		$sql = 'SELECT m.user_id, m.username, m.username_base, m.user_color, m.user_regdate 
			FROM _members_unread u, _members m 
			WHERE u.user_id = ? 
				AND u.element = ? 
				AND u.item = m.user_id 
				AND m.user_active = 1 
			ORDER BY m.user_id DESC';
		$result = sql_rowset(sql_filter($sql, $user->data['user_id'], UH_U));
		
		foreach ($result as $i => $row) {
			if (!$i) {
				$template->assign_block_vars('items.users', array(
					'ELEMENT' => UH_U)
				);
			}
			
			$user_profile = user_profile($row);
			
			$template->assign_block_vars('items.users.item', array(
				'S_MARK_ID' => $row['user_id'],
				'USER_COLOR' => $user_profile['color'],
				'USER_PROFILE' => $user_profile['profile'],
				'USERNAME' => $user_profile['username'],
				'DATETIME' => $user->format_date($row['user_regdate']))
			);
		}
		
		return true;
	}
}

?>