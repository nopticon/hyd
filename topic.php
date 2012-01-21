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
require_once('./interfase/common.php');

//
// Topic vars
//
$topic_id = request_var('t', 0);
$post_id = request_var('p', 0);
if (!$topic_id && !$post_id) {
	fatal_error();
}

//
// Load sidebar
//sidebar('events');

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
if (!$topic_data = sql_fieldrow($sql)) {
	fatal_error();
}

switch ($topic_data['forum_alias']) {
	case 'events':
		$sql = 'SELECT event_alias
			FROM _events
			WHERE event_topic = ?';
		if ($event_alias = sql_field(sql_filter($sql, $topic_data['topic_id']), 'event_alias', '')) {
			redirect(s_link('events', $event_alias));
		}
		break;
}

//
// Init member
//
$user->init();

$mod_auth = $user->_team_auth('mod');

//
// Hide deleted posts
//
if ($topic_data['post_deleted']) {
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
$submit_reply = isset($_POST['post']) ? true : false;
$submit_vote = isset($_POST['vote']) ? true : false;

$post_message = '';
$post_reply_message = '';
$post_np = '';
$current_time = time();

$error = array();
$is_auth = array();

if (!$post_id && $reply) {
	$reply = 0;
}

//
// Load user config
//
$user->setup();

//
// Start member auth
//
$is_auth = $auth->forum(AUTH_ALL, $forum_id, $topic_data);

if ($submit_reply || $submit_vote) {
	//$mod_auth = $auth->query('forum');
	$auth_key = ($submit_reply) ? 'auth_reply' : 'auth_vote';
	
	if (((!$is_auth['auth_view'] || !$is_auth['auth_read']) && $forum_id != 22) || !$is_auth[$auth_key]) {
		if (!$user->is('member')) {
			do_login();
		}
		
		$can_reply_closed = $auth->option(array('forum', 'topics', 'delete'));
		
		if (!$can_reply_closed && ($topic_data['forum_locked'] || $topic_data['topic_locked'])) {
			$error[] = 'TOPIC_LOCKED';
			
			if ($submit_vote && !$topic_data['topic_vote']) {
				$error[] = 'POST_HAS_NO_POLL';
			}
		}
		
		if (!sizeof($error)) {
			redirect($topic_url);
		}
	}
	
	if (!sizeof($error)) {
		if ($submit_vote) {
			$vote_option = request_var('vote_id', 0);
			
			if ($vote_option) {
				$sql = 'SELECT vd.vote_id    
					FROM _poll_options vd, _poll_results vr
					WHERE vd.topic_id = ?
						AND vr.vote_id = vd.vote_id 
						AND vr.vote_option_id = ?
					GROUP BY vd.vote_id';
				if ($vote_id = sql_field(sql_filter($sql, $topic_id, $vote_option), 'vote_id', 0)) {
					$sql = 'SELECT *
						FROM _poll_voters
						WHERE vote_id = ?
							AND vote_user_id = ?';
					if (!sql_fieldrow(sql_filter($sql, $vote_id, $user->data['user_id']))) {
						$sql = 'UPDATE _poll_results SET vote_result = vote_result + 1 
							WHERE vote_id = ?
								AND vote_option_id = ?';
						sql_query(sql_filter($sql, $vote_id, $vote_option));
						
						$insert_vote = array(
							'vote_id' => (int) $vote_id,
							'vote_user_id' => (int) $user->data['user_id'],
							'vote_user_ip' => $user->ip,
							'vote_cast' => (int) $vote_option
						);
						$sql = 'INSERT INTO _poll_voters' . sql_build('INSERT', $insert_vote);
						sql_query($sql);
					}
				}
			}
			
			redirect(s_link('topic', $topic_id));
		} else {
			$post_message = request_var('message', '', true);
			$post_np = request_var('np', '');
			
			if ($reply) {
				$post_reply_message = request_var('reply_message', '', true);
			}
			
			// Check message
			if (empty($post_message)) {
				$error[] = 'EMPTY_MESSAGE';
			}
			
			if (!sizeof($error) && !$mod_auth)
			{
				$sql = 'SELECT MAX(post_time) AS last_post_time
					FROM _forum_posts
					WHERE poster_id = ?';
				if ($last_post_time = sql_field(sql_filter($sql, $user->data['user_id']))) {
					if (intval($last_post_time) > 0 && ($current_time - intval($last_post_time)) < intval($config['flood_interval'])) {
						$error[] = 'FLOOD_ERROR';
					}
				}
			}
			
			if (!sizeof($error)) {
				require_once(ROOT . 'interfase/comments.php');
				$comments = new _comments();
				
				$update_topic = array();
				
				if (strstr($post_message, '-Anuncio-') && $user->_team_auth('mod')) {
					$topic_announce = 1;
					$post_message = str_replace('-Anuncio-', '', $post_message);
					$update_topic['topic_announce'] = $topic_announce;
				}
				
				if (strstr($post_message, '-Cerrado-') && $user->_team_auth('mod')) {
					$topic_locked = 1;
					$post_message = str_replace('-Cerrado-', '', $post_message);
					$update_topic['topic_locked'] = $topic_locked;
				}
				
				$post_message = $comments->prepare($post_message);
				
				if ($reply && $post_reply_message != '') {
					$post_reply_message = preg_replace('#(^|[\n ]|\()(http|https|ftp)://([a-z0-9\-\.,\?!%\*_:;~\\&$@/=\+]+)(gif|jpg|jpeg|png)#ie', '', $post_reply_message);
				}
				
				if ($reply && empty($post_reply_message)) {
					$post_reply_message = '...';
				}

				if ($reply && $post_reply_message != '') {
					$post_message = '<blockquote><strong>' . $topic_data['reply_username'] . "</strong>\n\n" . $post_reply_message . '</blockquote><br /> ' . $post_message;
				} else {
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
				if ($reply) {
					$insert_data['post_reply'] = $post_id;
				}
				
				$sql = 'INSERT INTO _forum_posts' . sql_build('INSERT', $insert_data);
				$post_id = sql_query_nextid($sql);
				
				$user->delete_unread(UH_T, $topic_id);
				$user->save_unread(UH_T, $topic_id);
				
				if (!in_array($forum_id, forum_for_team_array()) && $topic_data['topic_points']) {
					//$user->points_add(1);
				}
				
				//
				$a_list = forum_for_team_list($forum_id);
				if (count($a_list)) {
					$sql_delete_unread = 'DELETE FROM _members_unread
						WHERE element = ?
							AND item = ?
							AND user_id NOT IN (??)';
					sql_query(sql_filter($sql, 8, $topic_id, implode(', ', $a_list)));
				}
				
				$update_topic['topic_last_post_id'] = $post_id;
				
				if ($topic_locked) {
					topic_feature($topic_id, 0);
				}
				
				$sql = 'UPDATE _forums SET forum_posts = forum_posts + 1, forum_last_topic_id = ?
					WHERE forum_id = ?';
				sql_query(sql_filter($sql, $topic_id, $forum_id));
				
				$sql = 'UPDATE _forum_topics SET topic_replies = topic_replies + 1, ' . sql_build('UPDATE', $update_topic) . sql_filter('
					WHERE topic_id = ?', $topic_id);
				sql_query($sql);
				
				$sql = 'UPDATE _members SET user_posts = user_posts + 1
					WHERE user_id = ?';
				sql_query(sql_filter($sql, $user->data['user_id']));
				
				redirect(s_link('post', $post_id) . '#' . $post_id);
			}
		}
	}
}

if (!$is_auth['auth_view'] || !$is_auth['auth_read']) {
	if (!$user->is('member')) {
		do_login();
	}
	
	fatal_error();
}

if ($post_id) {
	$start = floor(($topic_data['prev_posts'] - 1) / (int) $config['posts_per_page']) * (int) $config['posts_per_page'];
	$user->data['user_topic_order'] = 0;
}

if ($user->is('member')) {
	//
	// Is user watching this topic?
	//
	$sql = 'SELECT notify_status
		FROM _forum_topics_fav
		WHERE topic_id = ?
			AND user_id = ?';
	if (!sql_field(sql_filter($sql, $topic_id, $user->data['user_id']))) {
		if (isset($_POST['watch']) ) {
			$sql_insert = array(
				'user_id' => $user->data['user_id'],
				'topic_id' => $topic_id,
				'notify_status' => 0
			);
			$sql = 'INSERT INTO _forum_topics_fav' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
			
			redirect($topic_url . (($start) ? 's' . $start . '/' : ''));
		}
		
		$template->assign_block_vars('watch_topic', array());
	}
}

//
// Get all data for the topic
//
$get_post_id = ($reply) ? 'post_id' : 'topic_id';
$get_post_data['p.' . $get_post_id] = $$get_post_id;
if (!$user->data['is_founder']) {
	$get_post_data['p.post_deleted'] = 0;
}

$sql = 'SELECT p.*, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar, u.user_posts, u.user_gender, u.user_rank, u.user_sig
	FROM _forum_posts p, _members u
	WHERE u.user_id = p.poster_id
		AND p.post_deleted = 0
		AND ' . sql_build('SELECT', $get_post_data) . '
	ORDER BY p.post_time ' . (($user->data['user_topic_order']) ? 'DESC' : 'ASC') . 
	((!$reply) ? ' LIMIT ' . (int) $start . ', ' . (int) $config['posts_per_page'] : '');
if (!$messages = sql_rowset($sql)) {
	if ($topic_data['topic_replies'] + 1) {
		fatal_error();
	}
	
	redirect(s_link('topic', $topic_id));
} 

//
// Re-count topic replies
//
if ($user->data['is_founder']) {
	$sql = 'SELECT COUNT(p.post_id) AS total
		FROM _forum_posts p, _members u 
		WHERE p.topic_id = ?
			AND u.user_id = p.poster_id';
	if ($total = sql_field(sql_filter($sql, $topic_id))) {
		$topic_data['topic_replies2'] = $total - 1;
	}
}

//
// Update the topic views
//
if (!$start && $user->data['user_id'] != 2) {
	$sql = 'UPDATE _forum_topics 
		SET topic_views = topic_views + 1
		WHERE topic_id = ?';
	sql_query(sql_filter($sql, $topic_id));
}

//
// If the topic contains a poll, then process it
//
if ($topic_data['topic_vote']) {
	$sql = 'SELECT vd.vote_id, vd.vote_text, vd.vote_start, vd.vote_length, vr.vote_option_id, vr.vote_option_text, vr.vote_result
		FROM _poll_options vd, _poll_results vr
		WHERE vd.topic_id = ?
			AND vr.vote_id = vd.vote_id
		ORDER BY vr.vote_option_order, vr.vote_option_id ASC';
	if ($vote_info = sql_rowset(sql_filter($sql, $topic_id))) {
		$sql = 'SELECT vote_id
			FROM _poll_voters
			WHERE vote_id = ?
				AND vote_user_id = ?';
		$user_voted = sql_field(sql_filter($sql, $vote_info[0]['vote_id'], $user->data['user_id']), 'vote_id', 0);
		
		$poll_expired = ($vote_info[0]['vote_length']) ? (($vote_info[0]['vote_start'] + $vote_info[0]['vote_length'] < $current_time) ? true : 0) : 0;
		
		$template->assign_block_vars('poll', array(
			'POLL_TITLE' => $vote_info[0]['vote_text'])
		);

		if ($user_voted || $poll_expired || !$is_auth['auth_vote'] || $topic_data['topic_locked']) {
			$vote_results_sum = 0;
			foreach ($vote_info as $row) {
				$vote_results_sum += $row['vote_result'];
			}
			
			$template->assign_block_vars('poll.results', array());
			
			foreach ($vote_info as $row) {
				$vote_percent = ($vote_results_sum > 0) ? $row['vote_result'] / $vote_results_sum : 0;

				$template->assign_block_vars('poll.results.item', array(
					'CAPTION' => $row['vote_option_text'],
					'RESULT' => $row['vote_result'],
					'PERCENT' => sprintf("%.1d", ($vote_percent * 100)))
				);
			}
		} else {
			$template->assign_block_vars('poll.options', array(
				'S_VOTE_ACTION' => $topic_url)
			);
			
			foreach ($vote_info as $row) {
				$template->assign_block_vars('poll.options.item', array(
					'POLL_OPTION_ID' => $row['vote_option_id'],
					'POLL_OPTION_CAPTION' => $row['vote_option_text'])
				);
			}
		}
	}
}

//
// Process the topic posts
//
require_once(ROOT . 'interfase/comments.php');
$comments = new _comments();

//
// Advanced auth
//

$controls = array();
$user_profile = array();
$unset_user_profile = array('user_id', 'user_posts', 'user_gender');

$template->assign_block_vars('posts', array());

foreach ($messages as $row) {
	if ($user->is('member')) {
		$poster = ($row['user_id'] != GUEST) ? $row['username'] : (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
		
		$controls[$row['post_id']]['reply'] = s_link('post', array($row['post_id'], 'reply'));
		
		if ($mod_auth) {
			$controls[$row['post_id']]['edit'] = s_link('mcp', array('edit', $row['post_id']));
			$controls[$row['post_id']]['delete'] = s_link('mcp', array('post', $row['post_id']));
		}
	}
	
	$user_profile[$row['user_id']] = $comments->user_profile($row, '', $unset_user_profile);	
	
	$data = array(
		'POST_ID' => $row['post_id'],
		'POST_DATE' => $user->format_date($row['post_time']),
		'MESSAGE' => $comments->parse_message($row['post_text']),
		'PLAYING' => $row['post_np'],
		'DELETED' => $row['post_deleted'],
		'UNREAD' => 0
	);
	
	foreach ($user_profile[$row['user_id']] as $key => $value) {
		$data[strtoupper($key)] = $value;
	}
	
	$template->assign_block_vars('posts.item', $data);
	$template->assign_block_vars('posts.item.' . (($row['user_id'] != GUEST) ? 'username' : 'guestuser'), array());

	if (isset($controls[$row['post_id']])) {
		$template->assign_block_vars('posts.item.controls', array());
		
		foreach ($controls[$row['post_id']] as $item => $url) {
			$template->assign_block_vars('posts.item.controls.'.$item, array('URL' => $url));
		}
	}
}

//
// Display Member topic auth
//
/*
if ($mod_auth) {
	$mod = array((($topic_data['topic_important']) ? 'important' : 'normal'), 'delete', 'move', ((!$topic_data['topic_locked']) ? 'lock' : 'unlock'), 'split', 'merge');
	
	$mod_topic = array();
	foreach ($mod as $item) {
		if ($auth->option(array('forum', 'topics', $item))) {
			$mod_topic[strtoupper($item)] = s_link_control('topic', array('topic' => $topic_id, 'mode' => $item));
		}
	}
	
	if (sizeof($mod_topic)) {
		$template->assign_block_vars('auth', array());
		
		foreach ($mod_topic as $k => $v) {
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
if (sizeof($error)) {
	$template->assign_block_vars('post_error', array(
		'MESSAGE' => parse_error($error))
	);
}

$can_reply_closed = $auth->option(array('forum', 'topics', 'delete'));

if ((!$topic_data['forum_locked'] && !$topic_data['topic_locked']) || $can_reply_closed) {
	if ($user->is('member')) {
		if ($is_auth['auth_reply']) {
			$s_post_action = (($reply) ? s_link('post', array($post_id, 'reply')) : $topic_url) . '#e';
			
			$template->assign_block_vars('post_box', array(
				'MESSAGE' => $post_message,
				'NP' => $post_np,
				'S_POST_ACTION' => $s_post_action)
			);
			
			if ($reply) {
				if (empty($post_reply_message)) {
					$post_reply_message = $comments->remove_quotes($topic_data['post_text']);
				}
				
				if (!empty($post_reply_message)) {
					$rx = array('#(^|[\n ]|\()(http|https|ftp)://([a-z0-9\-\.,\?!%\*_:;~\\&$@/=\+]+)(gif|jpg|jpeg|png)#is', '#\[yt:[0-9a-zA-Z\-\=\_]+\]#is', '#\[sb\]#is', '#\[\/sb\]#is');
					$post_reply_message = preg_replace($rx, '', $post_reply_message);
				}
				
				if (empty($post_reply_message)) {
					$post_reply_message = '...';
				}
				
				$template->assign_block_vars('post_box.reply', array(
					'MESSAGE' => $post_reply_message)
				);
			}
		}
	}
}

// MOD: Featured topic
if ($user->_team_auth('mod')) {
	$v_lang = ($topic_data['topic_featured']) ? 'REM' : 'ADD';
	
	$template->assign_block_vars('feature', array(
		'U_FEAT' => s_link('mcp', array('feature', $topic_data['topic_id'])),
		'V_LANG' => $user->lang['TOPIC_FEATURED_' . $v_lang])
	);
	
	//
	/*
	$v_lang = ($topic_data['topic_points']) ? 'REM' : 'ADD';
	$template->assign_block_vars('mcppoints', array(
		'U_FEAT' => s_link('mcp', array('points', $topic_data['topic_id'])),
		'V_LANG' => $user->lang['TOPIC_POINTS_' . $v_lang])
	);
	*/
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
if (@file_exists('./template/custom/topics_' . $forum_id . '.htm')) {
	$template_file = 'custom/topics_' . $forum_id;
}

if (@file_exists('./template/custom/topic_' . $topic_id . '.htm')) {
	$template_file = 'custom/topic_' . $topic_id;
}

page_layout($user->lang['FORUM'] .' | ' . $topic_data['topic_title'], $template_file, $template_vars);

?>