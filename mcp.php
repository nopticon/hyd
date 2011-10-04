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
include('./interfase/common.php');
include('./interfase/emailer.php');

$user->init();
$user->setup();

$mode = request_var('mode', '');
$is_auth = $user->_team_auth('mod');
$modes = array('merge', 'move', 'post', 'edit', 'topic', 'feature', 'points');

if ($mode !== 'ucm') {
	if (!$user->data['is_member'] || !$is_auth || !in_array($mode, $modes)) {
		fatal_error();
	}
} else {
	if (!$user->data['is_member'] || $user->data['is_bot']) {
		fatal_error();
	}
}


$submit = isset($_POST['submit']);

switch ($mode) {
	case 'ucm':
		$msg_id = request_var('msg_id', 0);
		
		$sql = 'SELECT *
			FROM _members_posts
			WHERE post_id = ' . (int) $msg_id;
		if (!$pdata = sql_fieldrow(sql_filter($sql, $msg_id))) {
			fatal_error();
		}
		
		if (!$user->data['is_founder'] && $user->data['user_id'] != $pdata['userpage_id']) {
			fatal_error();
		}
		
		$sql = 'SELECT username_base
			FROM _members
			WHERE user_id = ' . (int) $pdata['userpage_id'];
		$username_base = sql_field(sql_filter($sql, $pdata['userpage_id']), 'username_base', '');
		
		$sql = 'DELETE FROM _members_posts
			WHERE post_id = ?';
		sql_query(sql_filter($sql, $msg_id));
		
		$sql = 'UPDATE _members
			SET userpage_posts = userpage_posts - 1
			WHERE user_id = ?';
		sql_query(sql_filter($sql, $pdata['userpage_id']));
		
		$user->delete_unread(UH_UPM, $msg_id);
		
		if ($pdata['post_time'] > points_start_date() && $pdata['post_time'] < 1203314400) {
			//$user->points_remove(1, $pdata['poster_id']);
		}
		
		$alld = array_merge($pdata, $updata);
		smail($user->data, $alld, $to = 'b');
		
		redirect(s_link('m', array($username_base, 'messages')));
		break;
	case 'feature':
		$topic_id = request_var('msg_id', 0);
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?';
		if (!$td = sql_fieldrow(sql_filter($sql, $topic_id))) {
			fatal_error();
		}
		
		$new_value = ($td['topic_featured']) ? 0 : 1;
		topic_feature($topic_id, $new_value);
		
		//
		$emailer = new emailer();
		
		$emailer->from('info@rockrepublik.net');
		$emailer->set_subject('Featured topic by ' . $user->data['username']);
		$emailer->use_template('mcp_delete', $config['default_lang']);
		$emailer->email_address('bot.a@rockrepublik.net');
		
		$message = $user->data['username'] . ' ha modificado un tema de portada.' . "\n\n" . $td['topic_title'] . "\n\n" . s_link('topic', $topic_id) . "\n\n" . 'Nuevo valor [' . $new_value . ']';
		
		$emailer->assign_vars(array(
			'MESSAGE' => $message,
			'TIME' => $user->format_date(time(), 'r'))
		);
		$emailer->send();
		$emailer->reset();
		
		//
		redirect(s_link('topic', $topic_id));
		break;
	case 'points':
		$topic_id = request_var('msg_id', 0);
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?';
		if (!$td = sql_fieldrow(sql_filter($sql, $topic_id))) {
			fatal_error();
		}
		
		$new_value = ($td['topic_points']) ? 0 : 1;
		topic_arkane($topic_id, $new_value);
		
		//
		$emailer = new emailer();
		
		$emailer->from('info@rockrepublik.net');
		$emailer->set_subject('Arkaned topic by ' . $user->data['username']);
		$emailer->use_template('mcp_delete', $config['default_lang']);
		$emailer->email_address('bot.a@rockrepublik.net');
		
		$message = $user->data['username'] . ' ha modificado los puntos de un tema.' . "\n\n" . $td['topic_title'] . "\n\n" . s_link('topic', $topic_id) . "\n\n" . 'Nuevo valor [' . $new_value . ']';
		
		$emailer->assign_vars(array(
			'MESSAGE' => $message,
			'TIME' => $user->format_date(time(), 'r'))
		);
		$emailer->send();
		$emailer->reset();
		
		//
		redirect(s_link('topic', $topic_id));
		break;
	case 'merge':
		if ($submit) {
			$from_topic = request_var('from_topic', 0);
			$to_topic = request_var('to_topic', 0);
			
			if (!$from_topic || !$to_topic) {
				fatal_error();
			}
		
			$sql = 'SELECT forum_id, topic_vote
				FROM _forum_topics
				WHERE topic_id = ?';
			if (!$row = sql_fieldrow(sql_filter($sql, $from_topic))) {
				fatal_error();
			}
			
			$from_forum_id = (int) $row['forum_id'];
			$from_poll = (int) $row['topic_vote'];
			
			$sql = 'SELECT forum_id, topic_vote
				FROM _forum_topics
				WHERE topic_id = ?';
			if (!$row = sql_fieldrow(sql_filter($sql, $to_topic))) {
				fatal_error();
			}
			
			$to_forum_id = (int) $row['forum_id'];
			$to_poll = (int) $row['topic_vote'];
			
			//
			if ($from_topic == $to_topic) {
				fatal_error();
			}
			
			if ($from_poll) {
				if ($to_poll) {
					$sql = 'SELECT vote_id
						FROM _poll_options
						WHERE topic_id = ?';
					if ($vote_id = sql_field(sql_filter($sql, $from_topic), 'vote_id', 0)) {
						$sql = array(
							'DELETE FROM _poll_voters WHERE vote_id = ?',
							'DELETE FROM _poll_results WHERE vote_id = ?',
							'DELETE FROM _poll_options WHERE vote_id = ?'
						);
						
						foreach ($sql as $item) {
							sql_query(sql_filter($item, $vote_id));
						}
					}
				} else {
					$sql = 'UPDATE _poll_options
						SET topic_id = ?
						WHERE topic_id = ?';
					sql_query(sql_filter($sql, $to_topic, $from_topic));
				}
			}
			
			//
			$sql = 'SELECT user_id
				FROM _forum_topics_fav
				WHERE topic_id = ?';
			$user_ids = sql_rowset(sql_filter($sql, $to_topic), false, 'user_id');
			
			$sql_user = (sizeof($user_ids)) ? ' AND user_id NOT IN (' . implode(', ', $user_ids) . ')' : '';
			
			$sql = 'UPDATE _forum_topics_fav
				SET topic_id = ' . (int) $to_topic . '
				WHERE topic_id = ' . (int) $from_topic . $sql_user;
			sql_query($sql);
			
			$sql = 'DELETE FROM _forum_topics_fav
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $from_topic));
			
			$sql = 'UPDATE _forum_posts
				SET forum_id = ?, topic_id = ?
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $to_forum_id, $to_topic, $from_topic));
			
			$sql = 'SELECT *
				FROM _forum_topics
				WHERE topic_id = ?';
			$topic_data = sql_fieldrow(sql_filter($sql, $from_topic));
			
			$sql = 'DELETE FROM _forum_topics
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $from_topic));
			
			// Update the poll status
			if ($from_poll && !$to_poll) {
				$sql = 'UPDATE _forum_topics
					SET topic_vote = 1
					WHERE topic_id = ?';
				sql_query(sql_filter($sql, $to_topic));
			}
			
			sync_merge('topic', $to_topic);
			sync_merge('forum', $to_forum_id);
			
			if ($from_forum_id != $to_forum_id) {
				sync_merge('forum', $from_forum_id);
			}
		}
		
		?>
		<html>
<head>
<title>Merge topics</title>
</head>

<body>
<form action="<?php echo basename(__FILE__); ?>" method="post">
Tema origen: <input type="text" name="from_topic" /><br /><br />
Tema destino: <input type="text" name="to_topic" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>
		<?php
		break;
	case 'move':
		$t = request_var('msg_id', 0);
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?';
		if (!$tdata = sql_fieldrow(sql_filter($sql, $t))) {
			fatal_error();
		}
		
		if ($submit) {
			$f = request_var('forum_id', 0);
			if (!$f) {
				fatal_error();
			}
			
			//
			$sql = 'SELECT *
				FROM _forums
				WHERE forum_id = ?';
			if (!$fdata = sql_fieldrow(sql_filter($sql, $f))) {
				fatal_error();
			}
			
			//
			$sql = 'UPDATE _forum_topics
				SET forum_id = ?
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $f, $t));
			
			$sql = 'UPDATE _forum_posts
				SET forum_id = ?
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $f, $t));
			
			sync_move($f);
			sync_move($tdata['forum_id']);
			
			redirect(s_link('topic', $t));
		}
		?>
		<html>
<head>
<title>Move topics</title>
</head>

<body>
<form action="/mcp/move/" method="post">
<input type="hidden" name="msg_id" value="<?php echo $t; ?>" />
<select name="forum_id">
<?php

$sql = 'SELECT *
	FROM _forums';
$result = sql_rowset($sql);

foreach ($result as $row) {
	echo '<option value="' . $row['forum_id'] . '">' . $row['forum_name'] . '</option>';
}

?>
</select>
<br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>
		<?php
		break;
	case 'edit':
		$msg_id = request_var('msg_id', 0);
		
		$sql = 'SELECT *
			FROM _forum_posts
			WHERE post_id = ?';
		if (!$row = sql_fieldrow(sql_filter($sql, $msg_id))) {
			fatal_error();
		}
		
		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?';
		if (!$topic = sql_fieldrow(sql_filter($sql, $row['topic_id']))) {
			fatal_error();
		}
		
		if ($submit)
		{
			require('./interfase/comments.php');
			$comments = new _comments();
			
			$topic_title = request_var('topic_title', '');
			$post_message = request_var('message', '', true);
			$post_message = $comments->prepare($post_message);
			
			if (!empty($topic_title) && $topic_title != $topic['topic_title']) {
				$sql = 'UPDATE _forum_topics SET topic_title = ?
					WHERE topic_id = ?';
				sql_query(sql_filter($sql, $topic_title, $topic['topic_id']));
				
				$sql = 'SELECT id
					FROM _events
					WHERE event_topic = ?';
				if ($row_event = sql_field(sql_filter($sql, $topic['topic_id']), 'id', 0)) {
					$sql = 'UPDATE _events SET title = ?
						WHERE id = ?';
					sql_query(sql_filter($sql, $topic_title, $row_event));
				}
			}
			
			if ($post_message != $row['post_text']) {
				$sql = 'UPDATE _forum_posts SET post_text = ?
					WHERE post_id = ?';
				sql_query(sql_filter($sql, $post_message, $msg_id));
				
				$rev = array(
					'rev_post' => (int) $msg_id,
					'rev_uid' => (int) $user->data['user_id'],
					'rev_time' => time(),
					'rev_ip' => $user->ip,
					'rev_text' => $row['post_text']
				);
				$sql = 'INSERT INTO _forum_posts_rev' . sql_build('INSERT', $rev);
				sql_query($sql);
			}
			
			redirect(s_link('post', $msg_id) . '#' . $msg_id);
		}
		
		$tv = array(
			'V_TOPIC' => ($user->data['is_founder']) ? $topic['topic_title'] : '',
			'V_MESSAGE' => $row['post_text'],
			'S_ACTION' => s_link('mcp', array('edit', $msg_id))
		);
		page_layout('Editar', 'modcp.edit', $tv);
		break;
	case 'post':
		$post_id = request_var('msg_id', 0);
		if (!$post_id) {
			fatal_error();
		}
		
		$sql = 'SELECT f.*, t.*, p.*, m.*
			FROM _forum_posts p, _forum_topics t, _forums f, _members m
			WHERE p.post_id = ?
				AND t.topic_id = p.topic_id
				AND f.forum_id = p.forum_id
				AND m.user_id = p.poster_id';
		if (!$post_info = sql_fieldrow(sql_filter($sql, $post_id))) {
			fatal_error();
		}
		
		$forum_id = $post_info['forum_id'];
		$topic_id = $post_info['topic_id'];
		
		$post_data = array(
			'poster_post' => ($post_info['poster_id'] == $userdata['user_id']) ? true : false,
			'first_post' => ($post_info['topic_first_post_id'] == $post_id) ? true : false,
			'last_post' => ($post_info['topic_last_post_id'] == $post_id) ? true : false,
			'last_topic' => ($post_info['forum_last_topic_id'] == $topic_id) ? true : false,
			'has_poll' => ($post_info['topic_vote']) ? true : false
		);
		
		if ($post_data['first_post']) {
			redirect(s_link('mcp', array('topic', $topic_id)));
		}
		
		if ($post_data['first_post'] && $post_data['has_poll']) {
			$sql = 'SELECT vote_id
				FROM _poll_options vd, _poll_results vr
				WHERE vd.topic_id = ?
					AND vr.vote_id = vd.vote_id
				ORDER BY vr.vote_option_id';
			$poll_id = sql_field(sql_filter($sql, $topic_id), 'vote_id', 0);
		}
		
		//
		// Process
		//
		$sql = 'DELETE FROM _forum_posts
			WHERE post_id = ?';
		sql_query(sql_filter($sql, $post_id));
		
		if ($post_data['first_post'] && $post_data['last_post']) {
			$sql = 'DELETE FROM _forum_topics
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $topic_id));
			
			$sql = 'DELETE FROM _forum_topics_fav
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $topic_id));
		}
		
		if (!in_array($forum_id, forum_for_team_array()) && $post_info['topic_points'] && $post_info['post_time'] > points_start_date()) {
			//$user->points_remove(1, $post_info['poster_id']);
		}
		
		/*if ($post_data['has_poll'])
		{
			$sql = 'DELETE FROM _poll_options
				WHERE topic_id = ?';
			sql_query(sql_filter($sql, $topic_id));
			
			$sql = 'DELETE FROM _poll_results
				WHERE vote_id = ?';
			sql_query(sql_filter($sql, $poll_id));
			
			$sql = 'DELETE FROM _poll_voters
				WHERE vote_id = ?';
			sql_query(sql_filter($sql, $poll_id));
		}*/
		
		//
		// Update stats
		//
		$forum_update_sql = 'forum_posts = forum_posts - 1';
		$topic_update_sql = '';
	
		if ($post_data['last_post']) {
			if ($post_data['first_post']) {
				$forum_update_sql .= ', forum_topics = forum_topics - 1';
			} else {
				$topic_update_sql .= 'topic_replies = topic_replies - 1';
				
				$sql = 'SELECT MAX(post_id) AS last_post_id
					FROM _forum_posts
					WHERE topic_id = ?';
				if ($last_post_id = sql_field(sql_filter($sql, $topic_id), 'last_post_id', 0)) {
					$topic_update_sql .= ', topic_last_post_id = ' . $last_post_id;
				}
			}
	
			if ($post_data['last_topic']) {
				$sql = 'SELECT MAX(topic_id) AS last_topic_id
					FROM _forum_topics
					WHERE forum_id = ?';
				if ($last_topic_id = sql_field(sql_filter($sql, $forum_id), 'last_topic_id', 0)) {
					$forum_update_sql .= ', forum_last_topic_id = ' . $last_topic_id;
				}
			}
		} else if ($post_data['first_post']) {
			$sql = 'SELECT MIN(post_id) AS first_post_id
				FROM _forum_posts
				WHERE topic_id = ?';
			if ($first_post_id = sql_field(sql_filter($sql, $topic_id), 'first_post_id', 0)) {
				$topic_update_sql .= 'topic_replies = topic_replies - 1, topic_first_post_id = ' . $first_post_id;
			}
		} else {
			$topic_update_sql .= 'topic_replies = topic_replies - 1';
		}
	
		$sql = 'UPDATE _forums
			SET ' . $forum_update_sql . '
			WHERE forum_id = ' . (int) $forum_id;
		sql_query($sql);
		
		if ($topic_update_sql != '') {
			$sql = 'UPDATE _forum_topics
				SET ' . $topic_update_sql . '
				WHERE topic_id = ' . (int) $topic_id;
			sql_query($sql);
		}
	
		$sql = 'UPDATE _members
			SET user_posts = user_posts - 1
			WHERE user_id = ?';
		sql_query(sql_filter($sql, $post_info['poster_id']));
		
		smail($user->data, $post_info);
		
		redirect(s_link('topic', $post_info['topic_id']));
		break;
	case 'topic':
		$topic_id = request_var('msg_id', '');
		
		$sql = 'SELECT f.*, t.*
			FROM _forum_topics t, _forums f
			WHERE t.topic_id = ?
				AND f.forum_id = t.forum_id';
		if (!$topic_data = sql_fieldrow(sql_filter($sql, $topic_id))) {
			fatal_error();
		}
		
		$sql = 'SELECT t.*, p.*
			FROM _forum_posts p, _forum_topics t
			WHERE t.topic_id = p.topic_id
				AND t.topic_id = ?
			ORDER BY p.post_time';
		$result = sql_rowset(sql_filter($sql, $topic_id));
		
		$posts_id = array();
		$topic_posts = array();
		
		foreach ($result as $row) {
			$topic_posts[] = $row;
			$posts_id[] = $row['post_id'];
		}
		
		$post_id_array = implode(',', $posts_id);
		
		//
		$sql = 'SELECT poster_id, COUNT(post_id) AS posts
			FROM _forum_posts
			WHERE topic_id = ?
			GROUP BY poster_id';
		$result = sql_rowset(sql_filter($sql, $topic_id));
		
		$members_sql = array();
		foreach ($result as $row) {
			if (!in_array($topic_data['forum_id'], forum_for_team_array()) && $topic_data['topic_time'] > points_start_date())
			{
				//$user->points_remove($row['posts'], $row['poster_id']);
			}
			
			$sql = 'UPDATE _members SET user_posts = user_posts - ?
				WHERE user_id = ?';
			sql_query(sql_filter($sql, $row['posts'], $row['poster_id']));
		}
		
		//$user->points_remove(1, $topic_data['topic_poster']);
		
		//
		// Got all required info so go ahead and start deleting everything
		//
		$sql = 'DELETE FROM _forum_topics
			WHERE topic_id = ?';
		sql_query(sql_filter($sql, $topic_id));
		
		$sql = 'DELETE FROM _forum_topics_fav
			WHERE topic_id = ?';
		sql_query(sql_filter($sql, $topic_id));
		
		$sql = 'DELETE FROM _forum_posts
			WHERE post_id = ?';
		sql_query(sql_filter($sql, $topic_id));
		
		$sql = 'DELETE FROM _members_unread
			WHERE element = ?
				AND item = ?';
		sql_query(sql_filter($sql, UH_T, $topic_id));
		
		//
		$sql = 'SELECT vote_id
			FROM _poll_options
			WHERE topic_id = ?';
		$poll = sql_rowset(sql_filter($sql, $topic_id), false, 'vote_id');
		
		if (count($poll)) {
			$poll_ary = implode(',', $poll);
			
			$sql = 'DELETE FROM _poll_options
				WHERE vote_id IN (??)';
			sql_query(sql_filtert($sql, $poll_ary));
			
			$sql = 'DELETE FROM _poll_results
				WHERE vote_id IN (??)';
			sql_query(sql_filtert($sql, $poll_ary));
			
			$sql = 'DELETE FROM _poll_voters
				WHERE vote_id IN (??)';
			sql_query(sql_filtert($sql, $poll_ary));
		}
	
		//
		sync_topic($topic_data['forum_id']);
		smail($user->data, $topic_posts);
		
		redirect(s_link('forum', $topic_data['forum_alias']));
		break;
}

function smail_ary($data, $first = true) {
	$message = '';
	foreach ($data as $k => $v)
	{
		if (preg_match('/^\d+$/', $k)) {
			if (!$first) {
				continue;
			}
		}
		if (is_array($v)) {
			$message .= "\n\n" . '---------------------------------------------------------------------' . "\n" . smail_ary($v, false);
		} else {
			$message .= (($message != '') ? "\n" : '') . $k . ' > ' . $v;
		}
	}
	
	return $message;
}

function smail($member, $data, $to = 'a') {
	global $user, $config;
	
	$emailer = new emailer();
	
	$emailer->from('info@rockrepublik.net');
	$emailer->set_subject('Deleted by ' . $member['username']);
	$emailer->use_template('mcp_delete', $config['default_lang']);
	$emailer->email_address('bot.' . $to . '@rockrepublik.net');
	
	$message = smail_ary($data);
	
	$emailer->assign_vars(array(
		'MESSAGE' => $message,
		'TIME' => $user->format_date(time(), 'r'))
	);
	$emailer->send();
	$emailer->reset();
}

function sync_merge($type, $id = false) {
	switch($type) {
		case 'all forums':
			$sql = 'SELECT forum_id
				FROM _forums';
			$result = sql_rowset($sql);
			
			foreach ($result as $row) {
				sync_merge('forum', $row['forum_id']);
			}
			break;
		case 'all topics':
			$sql = 'SELECT topic_id
				FROM _forum_topics';
			$result = sql_rowset($sql);
			
			foreach ($result as $row) {
				sync_merge('topic', $row['topic_id']);
			}
			break;
		case 'forum':
			$sql = 'SELECT COUNT(post_id) AS total
				FROM _forum_posts
				WHERE forum_id = ?';
			$total_posts = sql_field(sql_filter($sql, $id), 'total', 0);
			
			$sql = 'SELECT topic_id
				FROM _forum_posts
				WHERE forum_id = ?
				ORDER BY post_time DESC';
			$last_topic = sql_field(sql_filter($sql, $id), 'topic_id', 0);
			
			$sql = 'SELECT COUNT(topic_id) AS total
				FROM _forum_topics
				WHERE forum_id = ?';
			$total_topics = sql_field(sql_filter($sql, $id), 'total', 0);
			
			$sql = 'UPDATE _forums SET forum_last_topic_id = ?, forum_posts = ?, forum_topics = ?
				WHERE forum_id = ?';
			sql_query(sql_filter($sql, $last_topic, $total_posts, $total_topics, $id));
			break;
		case 'topic':
			$sql = 'SELECT MAX(post_id) AS last_post, MIN(post_id) AS first_post, COUNT(post_id) AS total_posts
				FROM _forum_posts
				WHERE topic_id = ?';
			if ($row = sql_fieldrow(sql_filter($sql, $id))) {
				if ($row['total_posts']) {
					$sql = 'UPDATE _forum_topics SET topic_replies = ?, topic_first_post_id = ?, topic_last_post_id = ?
						WHERE topic_id = ?';
					$sql = sql_filter($sql, ($row['total_posts'] - 1), $row['first_post'], $row['last_post'], $id);
				} else {
					$sql = 'DELETE FROM _forum_topics WHERE topic_id = ';
					$sql = sql_filter($sql, $id);
				}
				sql_query($sql);
			}
			break;
	}
	
	return true;
}

function sync_move($id) {
	$last_topic = 0;
	$total_posts = 0;
	$total_topics = 0;
	
	//
	$sql = 'SELECT COUNT(post_id) AS total 
		FROM _forum_posts
		WHERE forum_id = ?';
	$total_posts = sql_field(sql_filter($sql, $id), 'total', 0);
	
	$sql = 'SELECT MAX(topic_id) as last_topic, COUNT(topic_id) AS total
		FROM _forum_topics
		WHERE forum_id = ?';
	if ($row = sql_fieldrow(sql_filter($sql, $id))) {
		$last_topic = $row['last_topic'];
		$total_topics = $row['total'];
	}
	
	//
	$sql = 'UPDATE _forums SET forum_last_topic_id = ?, forum_posts = ?, forum_topics = ?
		WHERE forum_id = ?';
	sql_query(sql_filter($sql, $last_topic, $total_posts, $total_topics, $id));
	
	return;
}

function sync_post($id) {
	$last_topic = 0;
	$total_posts = 0;
	$total_topics = 0;
	
	//
	$sql = 'SELECT COUNT(post_id) AS total 
		FROM _forum_posts
		WHERE forum_id = ?';
	$total_posts = sql_field(sql_filter($sql, $id), 'total', 0);
	
	$sql = 'SELECT MAX(topic_id) as last_topic, COUNT(topic_id) AS total
		FROM _forum_topics
		WHERE forum_id = ?';
	if ($row = sql_fieldrow(sql_filter($sql, $id))) {
		$last_topic = $row['last_topic'];
		$total_topics = $row['total'];
	}
	
	//
	$sql = 'UPDATE _forums SET forum_last_topic_id = ?, forum_posts = ?, forum_topics = ?
		WHERE forum_id = ?';
	sql_query(sql_filter($sql, $last_topic, $total_posts, $total_topics, $id));
	
	return;
}

function sync_topic($id) {
	$last_topic = 0;
	$total_posts = 0;
	$total_topics = 0;
	
	//
	$sql = 'SELECT COUNT(post_id) AS total 
		FROM _forum_posts
		WHERE forum_id = ?';
	$total_posts = sql_field(sql_filter($sql, $id), 'total', 0);
	
	$sql = 'SELECT MAX(topic_id) as last_topic, COUNT(topic_id) AS total
		FROM _forum_topics
		WHERE forum_id = ?';
	if ($row = sql_fieldrow(sql_filter($sql, $id))) {
		$last_topic = $row['last_topic'];
		$total_topics = $row['total'];
	}
	
	//
	$sql = 'UPDATE _forums SET forum_last_topic_id = ?, forum_posts = ?, forum_topics = ?
		WHERE forum_id = ?';
	sql_query(sql_filter($sql, $last_topic, $total_posts, $total_topics, $id));
		
	return true;
}

?>
