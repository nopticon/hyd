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
define('IN_NUCLEO', true);
require('./interfase/common.php');

//
// Topic vars
//
$topic_id = request_var('t', 0);
$post_id = request_var('p', 0);
if (!$topic_id && !$post_id)
{
	fatal_error();
}

//
// Load sidebar
sidebar('events');

//
// Get topic data
//
$sql_from = ($post_id) ? ', _forum_posts p, _forum_posts p2, _members m ' : '';
$sql_where = (!$post_id) ? 't.topic_id = ' . (int) $topic_id : 'p.post_id = ' . (int) $post_id . ' AND p.poster_id = m.user_id AND t.topic_id = p.topic_id AND p2.topic_id = p.topic_id AND p2.post_id <= ' . (int) $post_id;
$sql_count = (!$post_id) ? '' : ', p.post_text, m.username AS reply_username, COUNT(p2.post_id) AS prev_posts, p.post_deleted';
$sql_order = (!$post_id) ? '' : ' GROUP BY p.post_id, t.topic_id, t.topic_title, t.topic_locked, t.topic_replies, t.topic_time, t.topic_important, t.topic_vote, t.topic_last_post_id, f.forum_name, f.forum_locked, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_announce, f.auth_pollcreate, f.auth_vote ORDER BY p.post_id ASC';

$sql = 'SELECT t.topic_id, t.topic_title, t.topic_locked, t.topic_replies, t.topic_time, t.topic_important, t.topic_vote, t.topic_featured, t.topic_points, t.topic_last_post_id, f.forum_alias, f.forum_name, f.forum_locked, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_announce, f.auth_pollcreate, f.auth_vote' . $sql_count . '
	FROM _forum_topics t, _forums f' . $sql_from . '
	WHERE ' . $sql_where . ' AND f.forum_id = t.forum_id' . $sql_order;
$result = $db->sql_query($sql);

$topic_data = array();
if (!$topic_data = $db->sql_fetchrow($result))
{
	fatal_error();
}
$db->sql_freeresult($result);

//
// Init member
//
$user->init();

$mod_auth = $user->_team_auth('mod');

//
// Hide deleted posts
//
if ($topic_data['post_deleted'])
{
	fatal_error();
}

//
// Init vars
//
$forum_id = (int) $topic_data['forum_id'];
$topic_id = (int) $topic_data['topic_id'];
$topic_url = s_link('topic', $topic_id);

$reply = request_var('reply', 0);
$start = request_var('offset', 0);
$submit_reply = isset($_POST['post']) ? TRUE : FALSE;
$submit_vote = isset($_POST['vote']) ? TRUE : FALSE;

$post_message = '';
$post_reply_message = '';
$post_np = '';
$current_time = time();

$error = array();
$is_auth = array();

if (!$post_id && $reply)
{
	$reply = 0;
}

//
// Load user config
//
$user->setup('viewtopic');

//
// Start member auth
//
$is_auth = $auth->forum(AUTH_ALL, $forum_id, $topic_data);

if ($submit_reply || $submit_vote)
{
	//$mod_auth = $auth->query('forum');
	$auth_key = ($submit_reply) ? 'auth_reply' : 'auth_vote';
	
	if (((!$is_auth['auth_view'] || !$is_auth['auth_read']) && $forum_id != 22) || !$is_auth[$auth_key])
	{
		if (!$user->data['is_member'])
		{
			do_login();
		}
		
		$can_reply_closed = $auth->option(array('forum', 'topics', 'delete'));
		
		if (!$can_reply_closed && ($topic_data['forum_locked'] || $topic_data['topic_locked']))
		{
			$error[] = 'TOPIC_LOCKED';
			
			if ($submit_vote && !$topic_data['topic_vote'])
			{
				$error[] = 'POST_HAS_NO_POLL';
			}
		}
		
		if (!sizeof($error))
		{
			if ($forum_id == 22)
			{
				redirect(s_link('awards'));
			}
			
			redirect($topic_url);
		}
	}
	
	if (!sizeof($error))
	{
		if ($submit_vote)
		{
			$vote_option = request_var('vote_id', 0);
			
			if ($vote_option)
			{
				$sql = 'SELECT vd.vote_id    
					FROM _poll_options vd, _poll_results vr
					WHERE vd.topic_id = ' . (int) $topic_id . '
						AND vr.vote_id = vd.vote_id 
						AND vr.vote_option_id = ' . (int) $vote_option . '
					GROUP BY vd.vote_id';
				$result = $db->sql_query($sql);
				
				if ($vote_data = $db->sql_fetchrow($result))
				{
					$vote_id = $vote_data['vote_id'];
					
					$sql = 'SELECT *
						FROM _poll_voters
						WHERE vote_id = ' . (int) $vote_id . '
							AND vote_user_id = ' . $user->data['user_id'];
					$result2 = $db->sql_query($sql);
					
					if (!$row = $db->sql_fetchrow($result2))
					{
						$sql = 'UPDATE _poll_results
							SET vote_result = vote_result + 1 
							WHERE vote_id = ' . $vote_id . '
								AND vote_option_id = ' . $vote_option;
						$db->sql_query($sql);
						
						$insert_vote = array(
							'vote_id' => (int) $vote_id,
							'vote_user_id' => (int) $user->data['user_id'],
							'vote_user_ip' => $user->ip,
							'vote_cast' => (int) $vote_option
						);
						
						$db->sql_query('INSERT INTO _poll_voters' . $db->sql_build_array('INSERT', $insert_vote));
					}
					$db->sql_freeresult($result2);
				}
				$db->sql_freeresult($result);
			}
			
			if ($forum_id == 22)
			{
				redirect(s_link('awards'));
			}
			
			redirect(s_link('topic', $topic_id));
		}
		else
		{
			$post_message = request_var('message', '', true);
			$post_np = request_var('np', '');
			
			if ($reply)
			{
				$post_reply_message = request_var('reply_message', '', true);
			}
			
			// Check message
			if (empty($post_message))
			{
				$error[] = 'EMPTY_MESSAGE';
			}
			
			if (!sizeof($error) && !$mod_auth)
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
				require('./interfase/comments.php');
				$comments = new _comments();
				
				$update_topic = array();
				
				if (strstr($post_message, '-Anuncio-') && $user->_team_auth('mod'))
				{
					$topic_announce = 1;
					$post_message = str_replace('-Anuncio-', '', $post_message);
					$update_topic['topic_announce'] = $topic_announce;
				}
				
				if (strstr($post_message, '-Cerrado-') && $user->_team_auth('mod'))
				{
					$topic_locked = 1;
					$post_message = str_replace('-Cerrado-', '', $post_message);
					$update_topic['topic_locked'] = $topic_locked;
				}
				
				$post_message = $comments->prepare($post_message);
				
				if ($reply && $post_reply_message != '')
				{
					$post_reply_message = preg_replace('#(^|[\n ]|\()(http|https|ftp)://([a-z0-9\-\.,\?!%\*_:;~\\&$@/=\+]+)(gif|jpg|jpeg|png)#ie', '', $post_reply_message);
				}
				
				if ($reply && empty($post_reply_message))
				{
					$post_reply_message = '...';
				}

				if ($reply && $post_reply_message != '')
				{
					$post_message = '<blockquote><strong>' . $topic_data['reply_username'] . "</strong>\n\n" . $post_reply_message . '</blockquote><br /> ' . $post_message;
				}
				else
				{
					$reply = 0;
				}
				
				$insert_data = array(
					'topic_id' => (int) $topic_id,
					'forum_id' => (int) $forum_id,
					'poster_id' => (int) $user->data['user_id'],
					'post_time' => (int) $current_time,
					'poster_ip' => $user->ip,
					'post_text' => $post_message,
					'post_np' => $post_np
				);
				if ($reply)
				{
					$insert_data['post_reply'] = $post_id;
				}
				
				$db->sql_query('INSERT INTO _forum_posts' . $db->sql_build_array('INSERT', $insert_data));
				$post_id = $db->sql_nextid();
				
				$user->delete_unread(UH_T, $topic_id);
				$user->save_unread(UH_T, $topic_id);
				
				if (!in_array($forum_id, forum_for_team_array()) && $topic_data['topic_points'])
				{
					//$user->points_add(1);
				}
				
				//
				$a_list = forum_for_team_list($forum_id);
				if (count($a_list))
				{
					$sql_delete_unread = 'DELETE FROM _members_unread
						WHERE element = 8
							AND item = ' . (int) $topic_id . '
							AND user_id NOT IN (' . implode(', ', $a_list) . ')';
					$db->sql_query($sql_delete_unread);
				}
				
				$update_topic['topic_last_post_id'] = $post_id;
				
				if ($topic_locked)
				{
					topic_feature($topic_id, 0);
				}
				
				$sql = 'UPDATE _forums
					SET forum_posts = forum_posts + 1, forum_last_topic_id = ' . $topic_id . '
					WHERE forum_id = ' . $forum_id;
				$db->sql_query($sql);
				
				$sql = 'UPDATE _forum_topics
					SET topic_replies = topic_replies + 1, ' . $db->sql_build_array('UPDATE', $update_topic) . '
					WHERE topic_id = ' . $topic_id;
				$db->sql_query($sql);
				
				$sql = 'UPDATE _members
					SET user_posts = user_posts + 1
					WHERE user_id = ' . $user->data['user_id'];
				$db->sql_query($sql);
				
				$sql = "SELECT SUM(forum_topics) AS topic_total, SUM(forum_posts) AS post_total 
					FROM _forums";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					set_config('max_posts', $row['post_total']);
					set_config('max_topics', $row['topic_total']);
				}
				$db->sql_freeresult($result);
				
				redirect(s_link('post', $post_id) . '#' . $post_id);
			}
		}
	}
}

if (!$is_auth['auth_view'] || !$is_auth['auth_read'])
{
	if (!$user->data['is_member'])
	{
		do_login();
	}
	
	fatal_error();
}

if ($post_id)
{
	$start = floor(($topic_data['prev_posts'] - 1) / (int) $config['posts_per_page']) * (int) $config['posts_per_page'];
	$user->data['user_topic_order'] = 0;
}

if ($user->data['is_member'])
{
	//
	// Topic posts order
	//
	if (isset($_POST['topicorder']))
	{
		$topicorder = request_var('topicorder', 0);
		if ($topicorder != $user->data['user_topic_order'])
		{
			$topicorder = ($topicorder == 1) ? 1 : 0;
			$db->sql_query('UPDATE _members SET user_topic_order = ' . (int) $topicorder . ' WHERE user_id = ' . (int) $user->data['user_id']);
			
			redirect($topic_url . (($start && !$topicorder) ? 's' . $start . '/' : ''));
		}
	}
	
	//
	// Is user watching this topic?
	//
	$sql = 'SELECT notify_status
		FROM _forum_topics_fav
		WHERE topic_id = ' . $topic_id . '
			AND user_id = ' . (int) $user->data['user_id'];
	$result = $db->sql_query($sql);

	if (!$row = $db->sql_fetchrow($result))
	{
		if (isset($_POST['watch']) )
		{
			$sql = 'INSERT LOW_PRIORITY INTO _forum_topics_fav (user_id, topic_id, notify_status)
				VALUES (' . $user->data['user_id'] . ', ' . (int) $topic_id . ', 0)';
			$db->sql_query($sql);
			
			redirect($topic_url . (($start) ? 's' . $start . '/' : ''));
		}
		
		$template->assign_block_vars('watch_topic', array());
	}
	$db->sql_freeresult($result);
}
else
{
	$user->data['user_topic_order'] = 0;
}

//
// Get all data for the topic
//
$get_post_id = ($reply) ? 'post_id' : 'topic_id';
$get_post_data['p.' . $get_post_id] = $$get_post_id;
if (!$user->data['is_founder'])
{
	$get_post_data['p.post_deleted'] = 0;
}

$sql = 'SELECT p.*, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar, u.user_posts, u.user_gender, u.user_rank, u.user_sig
	FROM _forum_posts p, _members u
	WHERE u.user_id = p.poster_id
		AND p.post_deleted = 0
		AND ' . $db->sql_build_array('SELECT', $get_post_data) . '
	ORDER BY p.post_time ' . (($user->data['user_topic_order']) ? 'DESC' : 'ASC') . 
	((!$reply) ? ' LIMIT ' . (int) $start . ', ' . (int) $config['posts_per_page'] : '');
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$messages = array();
	do
	{
		$messages[] = $row;
	}
	while ($row = $db->sql_fetchrow($result));
	$db->sql_freeresult($result);
}
else 
{
	if ($topic_data['topic_replies'] + 1)
	{
		fatal_error();
	}
	
	redirect(s_link('topic', $topic_id));
}

//
// Re-count topic replies
//
if ($user->data['is_founder'])
{
	$sql = 'SELECT COUNT(p.post_id) AS total
		FROM _forum_posts p, _members u 
		WHERE p.topic_id = ' . (int) $topic_id . '
			AND u.user_id = p.poster_id';
	$result = $db->sql_query($sql);
	
	$row = $db->sql_fetchrow($result);
	$topic_data['topic_replies2'] = $row['total'] - 1;
	$db->sql_freeresult($result);
}

if ($user->data['is_member'])
{
	$select_order = '';
	foreach (array(0 => 'OLDEST_FIRST', 1 => 'NEWEST_FIRST') as $option => $value)
	{
		$select_order .= '<option value="' . $option . '"' . (($user->data['user_topic_order'] == $option) ? ' selected="selected"' : '') . '>' . $user->lang[$value] . '</option>';
	}
	
	$template->assign_block_vars('order_posts', array(
		'SELECT_ORDER' => $select_order)
	);
}

//
// Update the topic views
//
if (!$start && $user->data['user_id'] != 2)
{
	$sql = 'UPDATE _forum_topics
		SET topic_views = topic_views + 1
		WHERE topic_id = ' . (int) $topic_id;
	$db->sql_query($sql);
}

//
// If the topic contains a poll, then process it
//
if ($topic_data['topic_vote'])
{
	$sql = 'SELECT vd.vote_id, vd.vote_text, vd.vote_start, vd.vote_length, vr.vote_option_id, vr.vote_option_text, vr.vote_result
		FROM _poll_options vd, _poll_results vr
		WHERE vd.topic_id = ' . (int) $topic_id . '
			AND vr.vote_id = vd.vote_id
		ORDER BY vr.vote_option_order, vr.vote_option_id ASC';
	$result = $db->sql_query($sql);

	if ($vote_info = $db->sql_fetchrowset($result))
	{
		$db->sql_freeresult($result);
		$vote_options = sizeof($vote_info);
		
		$sql = 'SELECT vote_id
			FROM _poll_voters
			WHERE vote_id = ' . (int) $vote_info[0]['vote_id'] . '
				AND vote_user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);

		$user_voted = ( $row = $db->sql_fetchrow($result) ) ? TRUE : 0;
		$db->sql_freeresult($result);

		$poll_expired = ($vote_info[0]['vote_length']) ? (($vote_info[0]['vote_start'] + $vote_info[0]['vote_length'] < $current_time) ? TRUE : 0) : 0;
		
		$template->assign_block_vars('poll', array(
			'POLL_TITLE' => $vote_info[0]['vote_text'])
		);

		if ($user_voted || $poll_expired || !$is_auth['auth_vote'] || $topic_data['topic_locked'])
		{
			$vote_results_sum = 0;
			for($i = 0; $i < $vote_options; $i++)
			{
				$vote_results_sum += $vote_info[$i]['vote_result'];
			}
			
			$template->assign_block_vars('poll.results', array());

			for ($i = 0; $i < $vote_options; $i++)
			{
				$vote_percent = ($vote_results_sum > 0) ? $vote_info[$i]['vote_result'] / $vote_results_sum : 0;

				$template->assign_block_vars('poll.results.item', array(
					'CAPTION' => $vote_info[$i]['vote_option_text'],
					'RESULT' => $vote_info[$i]['vote_result'],
					'PERCENT' => sprintf("%.1d", ($vote_percent * 100)))
				);
			}
		}
		else
		{
			$template->assign_block_vars('poll.options', array(
				'S_VOTE_ACTION' => $topic_url)
			);

			for ($i = 0; $i < $vote_options; $i++)
			{
				$template->assign_block_vars('poll.options.item', array(
					'POLL_OPTION_ID' => $vote_info[$i]['vote_option_id'],
					'POLL_OPTION_CAPTION' => $vote_info[$i]['vote_option_text'])
				);
			}
		}
	}
}

//
// Process the topic posts
//
require('./interfase/comments.php');
$comments = new _comments();

//
// Advanced auth
//
$unread_topic = $user->get_unread(UH_T, $topic_id);

/*$mod_auth = $auth->query('forum');
if ($mod_auth)
{
	$mod_edit = $auth->option(array('forum', 'topics', 'edit'));
	$mod_delete = $auth->option(array('forum', 'topics', 'delete'));
}*/

$controls = array();
$user_profile = array();
$unset_user_profile = array('user_id', 'user_posts', 'user_gender');

$template->assign_block_vars('posts', array());

foreach ($messages as $row)
{
	if ($user->data['is_member'])
	{
		$poster = ($row['user_id'] != GUEST) ? $row['username'] : (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
		
		$controls[$row['post_id']]['reply'] = s_link('post', array($row['post_id'], 'reply')) . '#reply';
		
		if ($mod_auth)
		{
			/*if ($user->data['is_founder'])
			{
				$controls[$row['post_id']]['ip'] = s_link_control('topic', array('post' => $row['post_id'], 'mode' => 'ip'));
				
				if ($row['post_deleted'])
				{
					$controls[$row['post_id']]['restore'] = s_link_control('topic', array('post' => $row['post_id'], 'mode' => 'restore'));
				}
			}
			if ($mod_edit)
			{
				$controls[$row['post_id']]['edit'] = s_link_control('topic', array('post' => $row['post_id'], 'mode' => 'edit'));
			}*/
			$controls[$row['post_id']]['edit'] = s_link('mcp', array('edit', $row['post_id']));
			$controls[$row['post_id']]['delete'] = s_link('mcp', array('post', $row['post_id']));
		}
		/*elseif (!$row['post_reported'])
		{
			$controls[$row['post_id']]['report'] = s_link('report', array('post', $row['post_id']));
		}
		*/
	}
	
	$user_profile[$row['user_id']] = $comments->user_profile($row, '', $unset_user_profile);	
	
	$data = array(
		'POST_ID' => $row['post_id'],
		'POST_DATE' => $user->format_date($row['post_time']),
		'MESSAGE' => $comments->parse_message($row['post_text'], 'bold orange'),
		'PLAYING' => $row['post_np'],
		'DELETED' => $row['post_deleted'],
		'UNREAD' => ($user->data['is_member'] && $unread_topic && ($row['post_time'] > $user->data['user_lastvisit']))
	);
	
	foreach ($user_profile[$row['user_id']] as $key => $value)
	{
		$data[strtoupper($key)] = $value;
	}
	
	$template->assign_block_vars('posts.item', $data);
	$template->assign_block_vars('posts.item.' . (($row['user_id'] != GUEST) ? 'username' : 'guestuser'), array());

	if (isset($controls[$row['post_id']]))
	{
		$template->assign_block_vars('posts.item.controls', array());
		
		foreach ($controls[$row['post_id']] as $item => $url)
		{
			$template->assign_block_vars('posts.item.controls.'.$item, array('URL' => $url));
		}
	}
}

//
// Display Member topic auth
//
/*
if ($mod_auth)
{
	$mod = array((($topic_data['topic_important']) ? 'important' : 'normal'), 'delete', 'move', ((!$topic_data['topic_locked']) ? 'lock' : 'unlock'), 'split', 'merge');
	
	$mod_topic = array();
	foreach ($mod as $item)
	{
		if ($auth->option(array('forum', 'topics', $item)))
		{
			$mod_topic[strtoupper($item)] = s_link_control('topic', array('topic' => $topic_id, 'mode' => $item));
		}
	}
	
	if (sizeof($mod_topic))
	{
		$template->assign_block_vars('auth', array());
		
		foreach ($mod_topic as $k => $v)
		{
			$template->assign_block_vars('auth.item', array(
				'URL' => $v,
				'LANG' => $user->lang[$k . '_TOPIC'])
			);
		}
	}
}
*/
build_num_pagination($topic_url . 's%d/', ($topic_data['topic_replies'] + 1), $config['posts_per_page'], $start, '', 'TOPIC_');

//
// Posting box
//
if (sizeof($error))
{
	$template->assign_block_vars('post_error', array(
		'MESSAGE' => parse_error($error))
	);
}

$can_reply_closed = $auth->option(array('forum', 'topics', 'delete'));

if ((!$topic_data['forum_locked'] && !$topic_data['topic_locked']) || $can_reply_closed)
{
	
	if ($user->data['is_member'])
	{
		if ($is_auth['auth_reply'])
		{
			$s_post_action = (($reply) ? s_link('post', array($post_id, 'reply')) : $topic_url) . '#e';
			
			$template->assign_block_vars('post_box', array(
				'MESSAGE' => $post_message,
				'NP' => $post_np,
				'S_POST_ACTION' => $s_post_action)
			);
			
			if ($reply)
			{
				if (empty($post_reply_message))
				{
					$post_reply_message = $comments->remove_quotes($topic_data['post_text']);
				}

				if (!empty($post_reply_message))
				{
					$rx = array('#(^|[\n ]|\()(http|https|ftp)://([a-z0-9\-\.,\?!%\*_:;~\\&$@/=\+]+)(gif|jpg|jpeg|png)#is', '#\[yt:[0-9a-zA-Z\-\=\_]+\]#is', '#\[sb\]#is', '#\[\/sb\]#is');
					$post_reply_message = preg_replace($rx, '', $post_reply_message);
				}

				if (empty($post_reply_message))
				{
					$post_reply_message = '...';
				}
				
				$template->assign_block_vars('post_box.reply', array(
					'MESSAGE' => $post_reply_message)
				);
			}
		}
		else
		{
			$template->assign_block_vars('post_not_allowed', array(
				'LEGEND' => sprintf($user->lang['SORRY_AUTH_REPLY'], $is_auth['auth_reply_type']))
			);
		}
	}
	else
	{
		$template->assign_block_vars('post_members', array(
			'LEGEND' => sprintf($user->lang['LOGIN_TO_POST'], '', s_link('my', 'register')))
		);
	}
}
else
{
	$template->assign_block_vars('post_not_allowed', array(
		'LEGEND' => $user->lang['TOPIC_LOCKED'])
	);
}

// MOD: Featured topic
if ($user->_team_auth('mod'))
{
	$v_lang = ($topic_data['topic_featured']) ? 'REM' : 'ADD';
	
	$template->assign_block_vars('feature', array(
		'U_FEAT' => s_link('mcp', array('feature', $topic_data['topic_id'])),
		'V_LANG' => $user->lang['TOPIC_FEATURED_' . $v_lang])
	);
	
	//
	$v_lang = ($topic_data['topic_points']) ? 'REM' : 'ADD';
	$template->assign_block_vars('mcppoints', array(
		'U_FEAT' => s_link('mcp', array('points', $topic_data['topic_id'])),
		'V_LANG' => $user->lang['TOPIC_POINTS_' . $v_lang])
	);
}

//
// Send vars to template
//
$template_vars = array(
	'FORUM_NAME' => $topic_data['forum_name'],
	'TOPIC_TITLE' => $topic_data['topic_title'],
	'TOPIC_REPLIES' => $topic_data['topic_replies'],
	
	'S_TOPIC_ACTION' => $topic_url . (($start) ? 's' . $start . '/' : ''),
	'U_VIEW_FORUM' => s_link('forum', $topic_data['forum_alias'])
);

$template_file = 'topic';
if (@file_exists('./template/custom/topics_' . $forum_id . '.htm'))
{
	$template_file = 'custom/topics_' . $forum_id;
}

if (@file_exists('./template/custom/topic_' . $topic_id . '.htm'))
{
	$template_file = 'custom/topic_' . $topic_id;
}

page_layout($user->lang['FORUM'] .' | ' . $topic_data['topic_title'], $template_file, $template_vars);

?>