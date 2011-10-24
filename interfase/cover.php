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

class cover {
	public $msg;
	
	public function news() {
		global $cache, $user, $template;
		
		$news = array();
		if (!$news = $cache->get('news')) {
			$sql = 'SELECT n.news_id, n.post_time, n.poster_id, n.post_subject, n.post_desc, c.*
				FROM _news n, _news_cat c
				WHERE n.cat_id = c.cat_id
				ORDER BY n.post_time DESC
				LIMIT 4';
			if ($news = sql_rowset($sql)) {
				$cache->save('news', $news);
			}
		}
		
		if (!sizeof($news)) {
			return;
		}
		
		include_once(ROOT . 'interfase/comments.php');
		$comments = new _comments();
		$images_dir = SDATA . 'news/';
		
		$template->assign_block_vars('news', array());
		
		foreach ($news as $row) {
			$image = $images_dir . $row['news_id'] . '.jpg';
			$image = (@file_exists('..' . $image)) ? $image : $images_dir . 'default.jpg';
			
			$template->assign_block_vars('news.row', array(
				'TIMESTAMP' => $user->format_date($row['post_time'], 'j \d\e F Y'),
				'URL' => s_link('news', $row['news_id']),
				'SUBJECT' => $row['post_subject'],
				'CAT' => $row['cat_name'],
				'U_CAT' => s_link('news', $row['cat_url']),
				'MESSAGE' => $comments->parse_message($row['post_desc']),
				'IMAGE' => $image)
			);
		}
		
		if ($user->_team_auth('mod')) {
			$template->assign_block_vars('news.create', array(
				'U_NEWS_CREATE' => s_link('news', 'create'))
			);
		}
		
		return;
	}

	public function twitter() {
		/*
		foreach ($timeline as $tweet) {
			$date = '<br /><a href="http://www.twitter.com/rock_republik/status/' . $tweet->id . '">' . date('d.m.y, g:i a', strtotime($tweet->created_at)) . '</a>';
			//$date = date('M j @ H:i', strtotime($tweet->created_at));
			
			// Turn links into links
			$text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="\\1" target="_blank">\\1</a>', $text); 
			
			// Turn twitter @username into links to the users Twitter page
			$text = eregi_replace('@([-a-zA-Z0-9_]+)', '@<a href="http://twitter.com/\\1" target="_blank">\\1</a>', $text); 
		}
		*/
	}
	
	public function banners() {
		global $cache, $user, $template;
		
		$banners = array();
		if (!$banners = $cache->get('banners')) {
			$sql = 'SELECT *
				FROM _banners
				ORDER BY banner_order';
			if ($banners = sql_rowset($sql, 'banner_id')) {
				$cache->save('banners', $banners);
			}
		}
		
		if (!sizeof($banners)) return;
		
		$template->assign_block_vars('banners', array());
		foreach ($banners as $item) {
			$template->assign_block_vars('banners.item', array(
				'URL' => (!empty($item['banner_url'])) ? $item['banner_url'] : '',
				'IMAGE' => SDATA . 'base/' . $item['banner_id'] . '.gif',
				'ALT' => $item['banner_alt'])
			);
		}
		
		return;
	}
	
	public function board_general() {
		global $user, $config, $template;
		
		$sql = 'SELECT t.topic_id, t.topic_title, t.topic_color, t.topic_replies, p.post_id, p.post_time, u.user_id, u.username, u.username_base
			FROM _forums f, _forum_topics t, _forum_posts p, _members u
			WHERE t.topic_id NOT IN (
					SELECT e.event_topic FROM _events e WHERE e.event_topic > 0
				)
				AND t.forum_id = f.forum_id
				AND p.post_deleted = 0
				AND p.post_id = t.topic_last_post_id
				AND p.poster_id = u.user_id
				AND t.topic_featured = 1
			ORDER BY t.topic_announce DESC, p.post_time DESC
			LIMIT ??';
		if ($result = sql_rowset(sql_filter($sql, $config['main_topics']))) {
			$template->assign_block_vars('board_general', array(
				'L_TOP_POSTS' => sprintf($user->lang['TOP_FORUM'], count($result)))
			);
			
			foreach ($result as $row) {
				$username = ($row['user_id'] != GUEST) ? $row['username'] : (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
				
				$template->assign_block_vars('board_general.item', array(
					'U_TOPIC' => ($row['topic_replies']) ? s_link('post', $row['post_id']) . '#' . $row['post_id'] : s_link('topic', $row['topic_id']),
					'TOPIC_TITLE' => $row['topic_title'],
					'TOPIC_COLOR' => $row['topic_color'],
					'POST_TIME' => $user->format_date($row['post_time'], 'H:i'),
					'USER_ID' => $row['user_id'],
					'USERNAME' => $username,
					'PROFILE' => s_link('m', $row['username_base']))
				);
			}
		}
		
		return true;
	}
	
	public function board_events() {
		global $user, $config, $template;
		
		$sql = 'SELECT t.topic_id, t.topic_title, t.topic_color, p.post_id, p.post_time, u.user_id, u.username, u.username_base, e.id, e.date
			FROM _forum_topics t, _forum_posts p, _events e, _members u
			WHERE p.post_deleted = 0
				AND t.topic_featured = 1
				AND t.topic_id = e.event_topic
				AND p.post_id = t.topic_last_post_id
				AND p.poster_id = u.user_id
			ORDER BY t.topic_announce DESC, p.post_time DESC
			LIMIT ??';
		if ($result = sql_rowset(sql_filter($sql, $config['main_topics']))) {
			$template->assign_block_vars('board_events', array(
				'L_TOP_POSTS' => sprintf($user->lang['TOP_FORUM'], count($result)))
			);
			
			foreach ($result as $row) {
				$username = ($row['user_id'] != GUEST) ? $row['username'] : (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
				
				$template->assign_block_vars('board_events.item', array(
					'U_TOPIC' => s_link('events', $row['id']),
					'TOPIC_TITLE' => $row['topic_title'],
					'TOPIC_COLOR' => $row['topic_color'],
					'EVENT_DATE' => $user->format_date($row['date'], $user->lang['DATE_FORMAT']),
					'POST_TIME' => $user->format_date($row['post_time'], 'H:i'),
					'USER_ID' => $row['user_id'],
					'USERNAME' => $username,
					'PROFILE' => s_link('m', $row['username_base']))
				);
			}
		}
		
		return true;
	}
	
	public function poll() {
		global $user, $auth, $config, $cache, $template;
		
		if (!$topic_id = $cache->get('last_poll_id')) {
			$sql = 'SELECT t.topic_id
				FROM _forum_topics t
				LEFT JOIN _poll_options v ON t.topic_id = v.topic_id
				WHERE t.forum_id = ?
					AND t.topic_locked = 0
					AND t.topic_vote = 1 
				ORDER BY t.topic_time DESC 
				LIMIT 1';
			if ($row = sql_fieldrow(sql_filter($sql, $config['main_poll_f']))) {
				$topic_id = $row['topic_id'];
				$cache->save('last_poll_id', $topic_id);
			}
		}
		
		$topic_id = (int) $topic_id;
		
		if (!$topic_id) {
			return;
		}
		
		$sql = 'SELECT t.topic_id, t.topic_locked, t.topic_time, t.topic_replies, t.topic_important, t.topic_vote, f.forum_locked, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_announce, f.auth_pollcreate, f.auth_vote
			FROM _forum_topics t, _forums f
			WHERE t.topic_id = ?
				AND f.forum_id = t.forum_id';
		if (!$topic_data = sql_fieldrow(sql_filter($sql, $topic_id))) {
			return false;
		}
		
		$forum_id = (int) $topic_data['forum_id'];
		
		$sql = 'SELECT vd.*, vr.*
			FROM _poll_options vd, _poll_results vr
			WHERE vd.topic_id = ?
				AND vr.vote_id = vd.vote_id 
			ORDER BY vr.vote_option_id ASC';
		if (!$vote_info = sql_rowset(sql_filter($sql, $topic_id))) {
			return false;
		}
		
		if ($user->data['is_member']) {
			$is_auth = array();
			$is_auth = $auth->forum(AUTH_VOTE, $forum_id, $topic_data);
			
			$sql = 'SELECT vote_user_id
				FROM _poll_voters
				WHERE vote_id = ?
					AND vote_user_id = ?';
			$user_voted = (sql_field(sql_filter($sql, $vote_info[0]['vote_id'], $user->data['user_id']), 'vote_user_id', false)) ? true : false;
		}
		
		$poll_expired = ($vote_info[0]['vote_length']) ? (($vote_info[0]['vote_start'] + $vote_info[0]['vote_length'] < $current_time) ? true : 0) : 0;
		
		$template->assign_block_vars('poll', array(
			'U_POLL_TOPIC' => s_link('topic', $topic_id),
			'S_REPLIES' => $topic_data['topic_replies'],
			'U_POLL_FORUM' => s_link('forum', $config['main_poll_f']),
			'POLL_TITLE' => $vote_info[0]['vote_text'])
		);
		
		if (!$user->data['is_member'] || $user_voted || $poll_expired || !$is_auth['auth_vote'] || $topic_data['topic_locked']) {
			$vote_results_sum = 0;
			foreach ($vote_info as $row) {
				$vote_results_sum += $row['vote_result'];
			}
			
			$template->assign_block_vars('poll.results', array());
			
			foreach ($vote_info as $row) {
				$vote_percent = ($vote_results_sum) ? $row['vote_result'] / $vote_results_sum : 0;
				
				$template->assign_block_vars('poll.results.item', array(
					'CAPTION' => $row['vote_option_text'],
					'RESULT' => $row['vote_result'],
					'PERCENT' => sprintf("%.1d", ($vote_percent * 100)))
				);
			}
		} else {
			$template->assign_block_vars('poll.options', array(
				'S_VOTE_ACTION' => s_link('topic', $topic_id))
			);
			
			foreach ($vote_info as $row) {
				$template->assign_block_vars('poll.options.item', array(
					'POLL_OPTION_ID' => $row['vote_option_id'],
					'POLL_OPTION_CAPTION' => $row['vote_option_text'])
				);
			}
		}
		
		return true;
	}
}

?>