<?php
// -------------------------------------------------------------
// $Id: unread.php,v 1.5 2006/02/16 04:58:15 Psychopsia Exp $
//
// STARTED   : Wed Jul 13, 2005
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------

/*
U			USERS									ADM
A			ARTISTS								-
E			EVENTS								-
N			NEWS									-
P			POSTS									-
D			DOWNLOADS							-
C			ARTISTS MESSAGES			ADM / USER_MOD / USER_FAN
M			DOWNLOADS MESSAGES		ADM / USER_MOD / USER_FAN
W			WALLPAPERS / ART			-
F			ARTISTS NEWS					-
I			ARTISTS IMAGES				-
*/

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();

if (!$user->data['is_member'])
{
	if ($user->data['is_bot'])
	{
		redirect(s_link('cover'));
	}
	do_login();
}

$unread_element = request_var('elem', 0);
$unread_item = request_var('item', 0);

if (isset($_POST['items']) && (isset($_POST['delete']) || isset($_POST['delete_all'])))
{
	$items = (is_array($_POST['items']) && !empty($_POST['items'])) ? $_POST['items'] : array();
	
	if (isset($_POST['delete_all']))
	{
		foreach ($items as $element => $data)
		{
			$user->delete_unread($element, $data);
		}
	}
	else
	{
		foreach ($items as $element => $void)
		{
			if (isset($_POST['delete'][$element]))
			{
				$user->delete_unread($element, $items[$element]);
				break;
			}
		}
	}
	
	redirect(s_link('new'));
}
else if (isset($_POST['options']))
{
	$mark_option = (isset($_POST['mark_read_option'])) ? $_POST['mark_read_option'] : $user->data['user_mark_items'];
	$mark_option = intval($mark_option);
	
	if ($user->data['user_mark_items'] != $mark_option)
	{
		$db->sql_query('UPDATE _members SET user_mark_items = ' . (int) $mark_option . ' WHERE user_id = ' . (int) $user->data['user_id']);
	}
	
	redirect(s_link('new'));
}
else if ($unread_element && $unread_item)
{
	$url = '';
	$delete_item = TRUE;
	
	switch ($unread_element)
	{
		case UH_U:
			$url = '';
			break;
		case UH_A:
			$sql = 'SELECT subdomain
				FROM _artists
				WHERE ub = ' . (int) $unread_item;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('a', $row['subdomain']);
			}
			break;
		case UH_E:
			$url = s_link('events', $unread_item);
			break;
		case UH_N:
			$sql = 'SELECT a.subdomain
				FROM _artists a, _forum_topics t
				WHERE t.topic_id = ' . (int) $unread_item . '
					AND t.topic_ub = a.ub';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('a', $row['subdomain']);
			}
			break;
		case UH_GN:
			$url = s_link('news', $unread_item);
			break;
		case UH_D:
			$sql = 'SELECT a.subdomain
				FROM _artists a, _dl d
				WHERE d.id = ' . (int) $unread_item . '
					AND d.ub = a.ub';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('a', array($row['subdomain'], 9, $unread_item));
				$delete_item = FALSE;
			}
			break;
		case UH_C:
			$sql = 'SELECT a.subdomain
				FROM _artists a, _artists_posts p
				WHERE post_id = ' . (int) $unread_item . '
					AND p.post_ub = a.ub';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('a', array($row['subdomain'], 12, $unread_item));
				$delete_item = FALSE;
			}
			break;
		case UH_FRIEND:
			$sql = 'SELECT username_base
				FROM _members
				WHERE user_id = ' . (int) $unread_item;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('m', $row['username_base']);
			}
			break;
		case UH_UPM:
			$sql = 'SELECT username_base
				FROM _members m, _members_posts p
				WHERE m.user_id = p.userpage_id
					AND p.post_id = ' . (int) $unread_item;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('m', array($row['username_base'], 'messages'));
			}
			break;
	}
	
	if ($url != '')
	{
		if ($user->data['user_mark_items'] && $delete_item)
		{
			$user->delete_unread($unread_element, $unread_item);
		}
		
		redirect($url);
	}
	
	redirect(s_link('new'));
	
}

$user->setup();

//
// Show unread list
//
$sql = 'SELECT element
	FROM _members_unread
	WHERE user_id = ' . (int) $user->data['user_id'] . '
	ORDER BY element, item';
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$items = array();
	do
	{
		if (!isset($items[$row['element']]))
		{
			$items[$row['element']] = 0;
		}
		$items[$row['element']]++;
	}
	while ($row = $db->sql_fetchrow($result));
	
	if (isset($items[UH_D]) || isset($items[UH_M]))
	{
		require('./interfase/downloads.php');
		$downloads = new downloads();
	}
	
	$template->assign_block_vars('items', array(
		'TOTAL_ITEMS' => $db->sql_numrows($result))
	);
	
	$db->sql_freeresult($result);
	
	//
	// Notes (PM)
	//			
	if (isset($items[UH_NOTE]))
	{
		$sql = 'SELECT c.*, c2.privmsgs_date, m.user_id, m.username, m.username_base, m.user_color
			FROM _members_unread u, _dc c, _dc c2, _members m
			WHERE u.user_id = ' . (int) $user->data['user_id'] . '
				AND u.element = ' . UH_NOTE . '
				AND u.item = c.msg_id 
				AND c.last_msg_id = c2.msg_id
				AND c2.privmsgs_from_userid = m.user_id 
			ORDER BY c2.privmsgs_date DESC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.notes', array(
				'ELEMENT' => UH_NOTE)
			);
			
			do
			{
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
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}

	if (isset($items[UH_FRIEND]))
	{
		$sql = 'SELECT u.item, u.datetime, m.user_id, m.username, m.username_base, m.user_color, m.user_rank
			FROM _members_unread u, _members m
			WHERE u.user_id = ' . (int) $user->data['user_id'] . '
				AND u.element = ' . UH_FRIEND . '
				AND u.item = m.user_id
			ORDER BY u.datetime DESC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.friends', array(
				'ELEMENT' => UH_FRIEND)
			);
			
			do
			{
				$user_profile = user_profile($row);
				
				$template->assign_block_vars('items.friends.item', array(
					'S_MARK_ID' => $row['user_id'],
					'U_PROFILE' => s_link('new', array(UH_FRIEND, $row['user_id'])),
					'POST_TIME' => $user->format_date($row['datetime']),
					'USERNAME' => $row['username'],
					'USER_COLOR' => $row['user_color'])
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	if (isset($items[UH_UPM]))
	{
		$sql = 'SELECT p.*, u.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _members_unread u, _members_posts p, _members m
			WHERE u.user_id = ' . (int) $user->data['user_id'] . '
				AND u.element = ' . UH_UPM . '
				AND u.item = p.post_id
				AND p.poster_id = m.user_id
			ORDER BY p.post_time DESC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.userpagem', array(
				'ELEMENT' => UH_UPM)
			);
			
			do
			{
				$user_profile = user_profile($row);
				
				$template->assign_block_vars('items.userpagem.item', array(
					'S_MARK_ID' => $row['post_id'],
					'U_PROFILE' => s_link('new', array(UH_UPM, $row['post_id'])),
					'POST_TIME' => $user->format_date($row['datetime']),
					'USERNAME' => $row['username'],
					'USER_COLOR' => $row['user_color'])
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Site and Artists News
	//			
	if (isset($items[UH_N]))
	{
		$sql = 'SELECT t.*
			FROM _members_unread u, _forum_topics t
			WHERE u.user_id = ' . (int) $user->data['user_id'] . '
				AND u.element = ' . UH_N . '
				AND u.item = t.topic_id
			ORDER BY t.topic_time DESC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.a_news', array(
				'ELEMENT' => UH_N)
			);
			
			do
			{
				$user_profile = user_profile($row);
				
				$template->assign_block_vars('items.a_news.item', array(
					'S_MARK_ID' => $row['topic_id'],
					'POST_URL' => s_link('new', array(UH_N, $row['topic_id'])),
					'POST_TITLE' => $row['topic_title'],
					'POST_TIME' => $user->format_date($row['topic_time']))
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}

	if (isset($items[UH_GN]))
	{
		$sql = 'SELECT n.*
			FROM _members_unread u, _news n
			WHERE u.user_id = ' . $user->data['user_id'] . '
				AND u.element = ' . UH_GN . '
				AND u.item = n.news_id
			ORDER BY n.post_time DESC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.news', array(
				'ELEMENT' => UH_GN)
			);
			
			do
			{
				$user_profile = user_profile($row);
				
				$template->assign_block_vars('items.news.item', array(
					'S_MARK_ID' => $row['news_id'],
					'POST_URL' => s_link('new', array(UH_GN, $row['news_id'])),
					'POST_TITLE' => $row['post_subject'],
					'POST_TIME' => $user->format_date($row['post_time']))
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Artists
	//
	if (isset($items[UH_A]))
	{
		$sql = "SELECT a.ub, a.name, a.datetime 
			FROM _members_unread u, _artists a 
			WHERE u.user_id = " . $user->data['user_id'] . " 
				AND u.element = " . UH_A . " 
				AND u.item = a.ub 
			ORDER BY name";
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.artists', array(
				'ELEMENT' => UH_A
			));
			
			do
			{
				$template->assign_block_vars('items.artists.item', array(
					'S_MARK_ID' => $row['ub'],
					'UB_URL' => s_link('new', array(UH_A, $row['ub'])),
					'NAME' => $row['name'],
					'POST_TIME' => $user->format_date($row['datetime']))
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Downloads
	//
	if (isset($items[UH_D]))
	{
		$sql = "SELECT b.ub, b.subdomain, b.name, d.id, d.ud AS ud_type, d.title, d.date 
			FROM _members_unread u, _artists b, _dl d 
			WHERE u.user_id = " . $user->data['user_id'] . " 
				AND u.element = " . UH_D . " 
				AND u.item = d.id 
				AND d.ub = b.ub 
			ORDER BY d.id DESC";
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.downloads', array(
				'ELEMENT' => UH_D
			));
			
			do
			{
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
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Forum Topics
	//
	if (isset($items[UH_T]))
	{
		$sql = "SELECT t.*, f.forum_alias, f.forum_id, f.forum_name, p.post_id, p.post_username, p.post_time, m.user_id, m.username, m.username_base, m.user_color 
			FROM _members_unread u, _forums f, _forum_topics t, _forum_posts p, _members m 
			WHERE u.user_id = " . $user->data['user_id'] . " 
				AND f.forum_id NOT IN (22" . forum_for_team_not() . ")
				AND u.element = " . UH_T . " 
				AND u.item = t.topic_id 
				AND t.topic_id = p.topic_id 
				AND t.topic_last_post_id = p.post_id 
				AND t.forum_id = f.forum_id 
				AND p.poster_id = m.user_id 
			ORDER BY t.topic_announce DESC, p.post_time DESC";
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.forums', array(
				'ELEMENT' => UH_T)
			);
			
			do
			{
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
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Artists comments
	//
	if (isset($items[UH_C]))
	{
		$sql = "SELECT b.subdomain, b.name, p.*, m.user_id, m.username, m.username_base, m.user_color 
			FROM _members_unread u, _artists b, _artists_posts p, _members m 
			WHERE u.user_id = " . $user->data['user_id'] . " 
				AND u.element = " . UH_C . " 
				AND u.item = p.post_id
				AND b.ub = p.post_ub 
				AND p.poster_id = m.user_id 
				AND p.post_active = 1 
			ORDER BY p.post_id DESC";
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.a_messages', array(
				'ELEMENT' => UH_C
			));
			
			do
			{
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
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Downloads comments
	//
	if (isset($items[UH_M]))
	{
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
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.d_messages', array(
				'ELEMENT' => UH_M
			));
			
			do
			{
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
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Events
	//
	if (isset($items[UH_E]))
	{
		
	}
	
	//
	// ARTISTS FAV
	//
	if (isset($items[UH_AF]))
	{
		$sql = 'SELECT f.fan_id, f.joined, a.name, a.subdomain, m.user_id, m.username, m.username_base, m.user_color
			FROM _members_unread u, _artists a, _artists_fav f, _members m
			WHERE u.user_id = ' . $user->data['user_id'] . '
				AND u.element = ' . UH_AF . '
				AND u.item = f.fan_id
				AND f.ub = a.ub
				AND f.user_id = m.user_id
			ORDER BY f.joined DESC';
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.a_fav', array(
				'ELEMENT' => UH_AF)
			);
			
			do
			{
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
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Members
	//
	if (isset($items[UH_U]))
	{
		$sql = "SELECT m.user_id, m.username, m.username_base, m.user_color, m.user_regdate 
			FROM _members_unread u, _members m 
			WHERE u.user_id = " . $user->data['user_id'] . " 
				AND u.element = " . UH_U . " 
				AND u.item = m.user_id 
				AND m.user_active = 1 
			ORDER BY m.user_id DESC";
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('items.users', array(
				'ELEMENT' => UH_U
			));
			
			do
			{
				$user_profile = user_profile($row);
				
				$template->assign_block_vars('items.users.item', array(
					'S_MARK_ID' => $row['user_id'],
					'USER_COLOR' => $user_profile['color'],
					'USER_PROFILE' => $user_profile['profile'],
					'USERNAME' => $user_profile['username'],
					'DATETIME' => $user->format_date($row['user_regdate']))
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
}
else
{
	$template->assign_block_vars('no_items', array());
}

$mark_options = array('NEW_MARK_NEVER', 'NEW_MARK_ALWAYS');
foreach ($mark_options as $i => $mark_item)
{
	$template->assign_block_vars('mark_item', array(
		'ITEM' => $i,
		'NAME' => $user->lang[$mark_item],
		'SELECTED' => (($i == $user->data['user_mark_items']) ? ' selected="selected"' : '')
	));
}

$sql = 'SELECT d.id, d.title, a.subdomain, a.name
	FROM _dl d, _artists a
	WHERE d.ud = 1
		AND d.ub = a.ub
	ORDER BY d.date DESC
	LIMIT 0, 10';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('downloads', array(
		'URL' => s_link('a', array($row['subdomain'], 9, $row['id'])),
		'A' => $row['name'],
		'T' => $row['title'])
	);
}
$db->sql_freeresult($result);

$template->assign_vars(array(
	'S_UNREAD_ACTION' => s_link('new'))
);

//
// Load sidebar
sidebar('artists', 'events');

page_layout('UNREAD_ITEMS', 'unread_body');

//
// FUNCTIONS
//
function user_profile($row)
{
	global $user;
	static $user_profile = array();
	
	if (!isset($user_profile[$row['user_id']]) || $row['user_id'] == GUEST)
	{
		if ($row['user_id'] != GUEST)
		{
			$user_profile[$row['user_id']]['username'] = $row['username'];
			$user_profile[$row['user_id']]['profile'] = s_link('m', $row['username_base']);
			$user_profile[$row['user_id']]['color'] = $row['user_color'];
		}
		else
		{
			$user_profile[$row['user_id']]['username'] = ($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST'];
			$user_profile[$row['user_id']]['profile'] = '';
			$user_profile[$row['user_id']]['color'] = $row['user_color'];
		}
	}
	
	return $user_profile[$row['user_id']];
}
	
?>