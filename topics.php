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
// Init vars
//
$forum_id = request_var('f', '');
$start = intval(request_var('offset', 0));
$submit_topic = isset($_POST['post']) ? true : false;

if (empty($forum_id)) {
	fatal_error();
}
$is_int_forumid = false;
if (preg_match('#^([0-9]+)$#is', $forum_id))
{
	$is_int_forumid = true;
	$forum_id = intval($forum_id);
	
	$sql = 'SELECT *
		FROM _forums
		WHERE forum_id = ?';
	$sql = sql_filter($sql, $forum_id);
}
else
{
	$sql = 'SELECT *
		FROM _forums
		WHERE forum_alias = ?';
	$sql = sql_filter($sql, $forum_id);
}

if (!$forum_row = sql_fieldrow($sql)) {
	fatal_error();
}

if ($is_int_forumid) {
	redirect(s_link('forum', $forum_row['forum_alias']), true);
} else {
	$forum_id = $forum_row['forum_id'];
}

//
// Start session management
//
$user->init();
$user->setup();

//
// Start auth check
//
$is_auth = array();
$is_auth = $auth->forum(AUTH_ALL, $forum_id, $forum_row);

if (!$is_auth['auth_view'] || !$is_auth['auth_read']) {
	if (!$user->data['is_member']) {
		do_login();
	}
	
	fatal_error();
}

// Auth: Kick DJ
$ajax = request_var('ajax', 0);
if (($config['request_method'] == 'post') && $forum_id == $config['forum_for_radio'] && $user->_team_auth('radio') && $ajax) {
	$config['kick_script'] = true;
	include('./shoutcast_kick.php');
}

$error_msg = '';
$post_title = '';
$post_message = '';
$post_np = '';
$poll_title = '';
$poll_options = '';
$poll_length = '';
$current_time = time();

if ($submit_topic)
{
	$topic_important = (isset($_POST['topictype'])) ? true : false;
	$auth_key = ($topic_important) ? 'auth_announce' : 'auth_post';
	
	if ($forum_row['forum_locked'] && !$is_auth['auth_mod']) {
		$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['FORUM_LOCKED'];
	}
	
	if (!$is_auth[$auth_key]) {
		if (!$user->data['is_member']) {
			do_login();
		}
		
		if (empty($error_msg)) {
			redirect($topic_url);
		}
	}
	
	if (empty($error_msg)) {
		$post_title = request_var('topic_title', '');
		$post_message = request_var('message', '', true);
		$post_np = request_var('np', '', true);
		$poll_title = '';
		$poll_options = '';
		$poll_length = 0;
		
		if ($is_auth['auth_pollcreate']) {
			$poll_title = request_var('poll_title', '');
			$poll_options = request_var('poll_options', '');
			$poll_length = request_var('poll_length', 0);
		}
		
		// Check subject
		if (empty($post_title)) {
			$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['EMPTY_SUBJECT'];
		}
		
		// Check message
		if (empty($post_message)) {
			$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['EMPTY_MESSAGE'];
		}
		
		if (!empty($poll_options)) {
			$real_poll_options = array();
			$poll_options = explode("\n", $poll_options);
			
			foreach ($poll_options as $option) {
				if ($option != '') {
					$real_poll_options[] = $option;
				}
			}
			
			$sizeof_poll_options = sizeof($real_poll_options);
			
			if ($sizeof_poll_options < 2) {
				$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['FEW_POLL_OPTIONS'];
			} else if ($sizeof_poll_options > $config['max_poll_options']) {
				$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['MANY_POLL_OPTIONS'];
			} else if ($poll_title == '') {
				$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['EMPTY_POLL_TITLE'];
			}
		}
		
		if (empty($error_msg) && !$is_auth['auth_mod']) {
			$sql = 'SELECT MAX(post_time) AS last_post_time
				FROM _forum_posts
				WHERE poster_id = ?';
			if ($last_post_time = sql_field(sql_filter($sql, $user->data['user_id']))) {
				if (intval($last_post_time) > 0 && ($current_time - intval($last_post_time)) < intval($config['flood_interval'])) {
					$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['FLOOD_ERROR'];
				}
			}
		}
		
		if (empty($error_msg)) {
			require('./interfase/comments.php');
			$comments = new _comments();
			
			$topic_announce = 0;
			$topic_locked = 0;
			
			if ((strstr($post_message, '-Anuncio-') && $user->_team_auth('all')) || in_array($forum_id, array(15, 16, 17))) {
				$topic_announce = 1;
				$post_message = str_replace('-Anuncio-', '', $post_message);
			}
			
			if (strstr($post_message, '-Cerrado-') && $user->_team_auth('mod')) {
				$topic_locked = 1;
				$post_message = str_replace('-Cerrado-', '', $post_message);
			}
			
			$post_message = $comments->prepare($post_message);
			$topic_vote = (!empty($poll_title) && $sizeof_poll_options >= 2) ? 1 : 0;
			
			if (!$user->data['is_founder']) {
				$post_title = strnoupper($post_title);
			}
			
			$insert_data['TOPIC'] = array(
				'topic_title' => $post_title,
				'topic_poster' => (int) $user->data['user_id'],
				'topic_time' => (int) $current_time,
				'forum_id' => (int) $forum_id,
				'topic_locked' => $topic_locked,
				'topic_announce' => $topic_announce,
				'topic_important' => (int) $topic_important,
				'topic_vote' => (int) $topic_vote,
				'topic_featured' => 1,
				'topic_points' => 1
			);
			$sql = 'INSERT INTO _forum_topics' . sql_build('INSERT', $insert_data['TOPIC']);
			$topic_id = sql_query_nextid($sql);
			
			$insert_data['POST'] = array(
				'topic_id' => (int) $topic_id,
				'forum_id' => (int) $forum_id,
				'poster_id' => (int) $user->data['user_id'],
				'post_time' => (int) $current_time,
				'poster_ip' => $user->ip,
				'post_text' => $post_message,
				'post_np' => $post_np
			);
			$sql = 'INSERT INTO _forum_posts' . sql_build('INSERT', $insert_data['POST']);
			$post_id = sql_query_nextid($sql);
			
			if ($topic_vote) {
				$insert_data['POLL'] = array(
					'topic_id' => (int) $topic_id,
					'vote_text' => $poll_title,
					'vote_start' => (int) $current_time,
					'vote_length' => (int) ($poll_length * 86400)
				);
				$sql = 'INSERT INTO _poll_options' . sql_build('INSERT', $insert_data['POLL']);
				$poll_id = sql_query_nextid($sql);
				
				$poll_option_id = 1;
				foreach ($real_poll_options as $option) {
					$insert_data['POLLRESULTS'][$poll_option_id] = array(
						'vote_id' => (int) $poll_id,
						'vote_option_id' => (int) $poll_option_id,
						'vote_option_text' => $option,
						'vote_result' => 0
					);
					$sql = 'INSERT INTO _poll_results' . sql_build('INSERT', $insert_data['POLLRESULTS'][$poll_option_id]);
					sql_query($sql);
					
					$poll_option_id++;
				}
				
				if ($forum_id == $config['main_poll_f']) {
					$cache->delete('last_poll_id');
				}
			}
			
			$user->save_unread(UH_T, $topic_id);
			
			if (!in_array($forum_id, forum_for_team_array())) {
				//$user->points_add(2);
			}
			
			$a_list = forum_for_team_list($forum_id);
			if (count($a_list)) {
				$sql_delete_unread = 'DELETE FROM _members_unread
					WHERE element = ?
						AND item = ?
						AND user_id NOT IN (??)';
				sql_query(sql_filter($sql_delete_unread, 8, $topic_id, implode(', ', $a_list)));
			}
			
			if (count($a_list) || in_array($forum_id, array(20, 39))) {
				topic_feature($topic_id, 0);
				topic_arkane($topic_id, 0);
			}

			$sql = 'UPDATE _forums SET forum_posts = forum_posts + 1, forum_last_topic_id = ?, forum_topics = forum_topics + 1
				WHERE forum_id = ?';
			sql_query(sql_filter($sql, $topic_id, $forum_id));
			
			$sql = 'UPDATE _forum_topics SET topic_first_post_id = ?, topic_last_post_id = ?
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $post_id, $post_id, $topic_id));
			
			$sql = 'UPDATE _members SET user_posts = user_posts + 1
				WHERE user_id = ?';
			sql_query(sql_filter($sql, $user->data['user_id']));
			
			$sql = 'SELECT SUM(forum_topics) AS topic_total, SUM(forum_posts) AS post_total 
				FROM _forums';
			if ($row = sql_fieldrow($sql)) {
				set_config('max_posts', $row['post_total']);
				set_config('max_topics', $row['topic_total']);
			}
			
			redirect(s_link('topic', $topic_id));
		}
	}
}
//
// END SUBMIT TOPIC
//

$topics_count = ($forum_row['forum_topics']) ? $forum_row['forum_topics'] : 1;
$topic_rowset = array();

//
// All announcement data
//
$sql = 'SELECT t.*, u.user_id, u.username, u.username_base, u.user_color, u2.user_id as user_id2, u2.username as username2, u2.username_base as username_base2, u2.user_color as user_color2, p.post_time, p.post_username as post_username2
	FROM _forum_topics t, _members u, _forum_posts p, _members u2
	WHERE t.forum_id = ?
		AND t.topic_poster = u.user_id
		AND p.post_id = t.topic_last_post_id
		AND p.poster_id = u2.user_id
		AND t.topic_announce = 1
	ORDER BY t.topic_last_post_id DESC';
$result = sql_rowset(sql_filter($sql, $forum_id));

$total_announcements = 0;
foreach ($result as $row) {
	$topic_rowset[] = $row;
	$total_announcements++;
}

//
// Grab all the topics data for this forum
//
$sql = 'SELECT t.*, u.user_id, u.username, u.username_base, u.user_color, u2.user_id as user_id2, u2.username as username2, u2.username_base as username_base2, u2.user_color as user_color2, p.post_username, p2.post_username AS post_username2, p2.post_time
	FROM _forum_topics t, _members u, _forum_posts p, _forum_posts p2, _members u2
	WHERE t.forum_id = ?
		AND t.topic_poster = u.user_id
		AND p.post_id = t.topic_first_post_id
		AND p2.post_id = t.topic_last_post_id
		AND u2.user_id = p2.poster_id
		AND t.topic_announce = 0
	ORDER BY t.topic_important DESC, /*t.topic_last_post_id*/p2.post_time DESC
	LIMIT ??, ??';
$result = sql_rowset(sql_filter($sql, $forum_id, $start, $config['topics_per_page']));

$total_topics = 0;
foreach ($result as $row) {
	$topic_rowset[] = $row;
	$total_topics++;
}

//
// Total topics ...
//
$total_topics += $total_announcements;

//
// Post URL generation for templating vars
//
if ($is_auth['auth_post'] || $is_auth['auth_mod']) {
	$template->assign_block_vars('create_topic', array());
}

//
// Dump out the page header and load viewforum template
//
$template->assign_vars(array(
	'FORUM_ID' => $forum_id,
	'FORUM_NAME' => $forum_row['forum_name'],
	'U_VIEW_FORUM' => s_link('forum', $forum_row['forum_alias']),

	'L_POST_NEW_TOPIC' => ($forum_row['forum_locked']) ? $user->lang['FORUM_LOCKED'] : $user->lang['POST_NEWTOPIC'])
);
//
// End header
//

//
// Okay, lets dump out the page ...
//
if ($total_topics) {
	$template->assign_block_vars('topics', array());
	
	for ($i = 0; $i < $total_topics; $i++) {
		$topic_id = $topic_rowset[$i]['topic_id'];
		$topic_title = $topic_rowset[$i]['topic_title'];
		$replies = $topic_rowset[$i]['topic_replies'];
		//$topic_type = $topic_rowset[$i]['topic_important'];
		$topic_featured = $topic_rowset[$i]['topic_featured'];
		$topic_author_id = $topic_rowset[$i]['user_id'];
		
		/*$topic_type = '';
		if ($topic_type)
		{
			$topic_type = $user->lang['TOPIC_ANNOUNCEMENT'] . ' ';
		}

		if ($topic_rowset[$i]['topic_vote'])
		{
			$topic_type .= $user->lang['TOPIC_POLL'] . ' ';
		}
		
		if (!$topic_featured && $user->data['is_founder'])
		{
			$topic_type .= '^ ';
		}*/
		
		if ($topic_author_id != GUEST) {
			$topic_author = '<a class="relevant uunique" href="' . s_link('m', $topic_rowset[$i]['username_base2']) . '">' . $topic_rowset[$i]['username2'] . '</a>';
		} else {
			$topic_author = '<span class="relevant">*' . (($topic_rowset[$i]['post_username2'] != '') ? $topic_rowset[$i]['post_username2'] : $user->lang['GUEST']) . '</span>';
		}
		
		if ($topic_rowset[$i]['user_id2'] != GUEST) {
			$last_post_author = '<a class="relevant uunique" href="' . s_link('m', $topic_rowset[$i]['username_base2']) . '">' . $topic_rowset[$i]['username2'] . '</a>';
		} else {
			$last_post_author = '<span class="relevant">*' . (($topic_rowset[$i]['post_username2'] != '') ? $topic_rowset[$i]['post_username2'] : $user->lang['GUEST']) . '</span>';
		}
		
		$template->assign_block_vars('topics.item', array(
			'FORUM_ID' => $forum_id,
			'TOPIC_ID' => $topic_id,
			'TOPIC_AUTHOR' => $topic_author, 
			'REPLIES' => $replies,
			'VIEWS' => ($user->data['is_member'] && ($user->data['user_type'] == USER_FOUNDER)) ? $topic_rowset[$i]['topic_views'] : '',
			'TOPIC_TITLE' => $topic_title,
			//'TOPIC_TYPE' => $topic_type,
			'TOPIC_CREATION_TIME' => $user->format_date($topic_rowset[$i]['topic_time']),
			'LAST_POST_TIME' => $user->format_date($topic_rowset[$i]['post_time']),
			'LAST_POST_AUTHOR' => $last_post_author, 

			'U_TOPIC' => s_link('topic', $topic_id))
		);
	}

	$topics_count -= $total_announcements;

	build_num_pagination(s_link('forum', array($forum_row['forum_alias'], 's%d')), $topics_count, $config['topics_per_page'], $start, '', 'TOPICS_');
} else {
	if ($start) {
		redirect(s_link('forum', $forum_row['forum_alias']), true);
	}
	$template->assign_block_vars('no_topics', array() );
}

//
// Posting box
//
if (!empty($error_msg) || (!$is_auth['auth_mod'] && $forum_row['forum_locked']) || (!$is_auth['auth_post'] && $forum_row['auth_post'] == AUTH_REG) || $is_auth['auth_post']) {
	$template->assign_block_vars('posting_box', array());
	
	if (!empty($error_msg)) {
		$template->assign_block_vars('posting_box.error', array(
			'MESSAGE' => $error_msg)
		);
	}
	
	if (!$is_auth['auth_mod'] && $forum_row['forum_locked']) {
		$template->assign_block_vars('posting_box.not_allowed', array(
			'LEGEND' => $user->lang['FORUM_LOCKED'])
		);
	} else if (!$is_auth['auth_post'] && $forum_row['auth_post'] == AUTH_REG) {
		$template->assign_block_vars('posting_box.only_registered', array(
			'LEGEND' => sprintf($user->lang['LOGIN_TO_POST'], '', s_link('my', 'register'))
		));
	} else if ($is_auth['auth_post']) {
		if (!empty($poll_options)) {
			$poll_options = implode("\n", $poll_options);
		}
		
		$template->assign_block_vars('posting_box.box', array(
			'TOPIC_TITLE' => $post_title,
			'MESSAGE' => $post_message,
			'NP' => $post_np,
			'POLL_TITLE' => $poll_title,
			'POLL_OPTIONS' => $poll_options,
			'POLL_LENGTH' => $poll_length,
			'S_POST_ACTION' => s_link('forum', $forum_row['forum_alias']) . '#e')
		);
		
		if ($is_auth['auth_pollcreate']) {
			$template->assign_block_vars('posting_box.box.addpoll', array());
			
			if (empty($poll_options)) {
				$template->assign_block_vars('posting_box.box.addpoll.hide', array());
			}
		}
	}
}

// Auth: Kick DJ
if ($forum_id == $config['forum_for_radio'] && $user->_team_auth('radio')) {
	$template->assign_block_vars('rdjk', array(
		'U_KICK' => s_link('forum', 'djs'),
		'V_HASH' => _encode($user->data['username_base'] . '.' . $user->data['username_base']))
	);
}

$template_file = 'topics';
if (@file_exists('./template/custom/forum_' . $forum_id . '.htm')) {
	$template_file = 'custom/forum_' . $forum_id;
}

page_layout($forum_row['forum_name'], $template_file);

?>