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

class cover
{
	var $msg;
	
	function news()
	{
		global $cache, $user, $template;
		
		$news = array();
		if (!$news = $cache->get('news'))
		{
			global $db;
			
			$sql = 'SELECT n.news_id, n.post_time, n.poster_id, n.post_subject, n.post_desc, c.*
				FROM _news n, _news_cat c
				WHERE n.cat_id = c.cat_id
				ORDER BY n.post_time DESC
				LIMIT 3';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				$news[] = $row;
			}
			$db->sql_freeresult($result);
			
			if (sizeof($news))
			{
				$cache->save('news', $news);
			}
		}
		
		if (!sizeof($news))
		{
			return;
		}
		
		include('./interfase/comments.php');
		$comments = new _comments();
		$images_dir = SDATA . 'news/';
		
		$template->assign_block_vars('news', array());
		
		foreach ($news as $row)
		{
			$image = $images_dir . $row['news_id'] . '.jpg';
			$image = (@file_exists('..' . $image)) ? $image : $images_dir . 'default.jpg';
			
			$template->assign_block_vars('news.item', array(
				'TIMESTAMP' => $user->format_date($row['post_time'], 'd M'),
				'URL' => s_link('news', $row['news_id']),
				'SUBJECT' => $row['post_subject'],
				'CAT' => $row['cat_name'],
				'U_CAT' => s_link('news', $row['cat_url']),
				'MESSAGE' => $comments->parse_message($row['post_desc']),
				'IMAGE' => $image)
			);
		}
		
		return;
	}
	
	function twitter()
	{
		global $template;
		
		require_once('./interfase/twitter/gagawa-1.0.php');
		require_once('./interfase/twitter/son.php');
		require_once('./interfase/twitter/tcache.php');
		
		$json = new Services_JSON();
		
		$tc = new TwitterCacher("info@rockrepublik.net", "93624739", 'json', './cache/');
		$tc->setUserAgent("Mozilla/5.0 (compatible; Rock Republik; +http://www.rockrepublik.net)");
		
		$timeline = $json->decode($tc->getUserTimeline());
		
		$count = 1;
		$ul = new Ul();
		
		foreach ($timeline as $tweet)
		{
			// Only show original posts, not my replies to
			// other tweets.
			//if(!empty($tweet->in_reply_to_user_id)){
			//	continue;
			//}
			
			$text = $tweet->text;
			$date = '<br /><a href="http://www.twitter.com/rock_republik/status/' . $tweet->id . '">' . date('d.m.y, g:i a', strtotime($tweet->created_at)) . '</a>';
			//$date = date('M j @ H:i', strtotime($tweet->created_at));
			$source = $tweet->source;
			
			$li = new Li();
			$ul->appendChild($li);
			
			// Turn links into links
			$text = eregi_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_\+.~#?&//=]+)', '<a href="\\1" target="_blank">\\1</a>', $text); 
			
			// Turn twitter @username into links to the users Twitter page
			$text = eregi_replace('@([-a-zA-Z0-9_]+)', '@<a href="http://twitter.com/\\1" target="_blank">\\1</a>', $text); 
			
			$li->appendChild( new Text($text));
			
			$em = new Em();
			$em->appendChild( new Text($date) );
			$li->appendChild($em);
			
			$count += 1;
		}
		
		if (count($timeline))
		{
			$template->assign_block_vars('twitter_timeline', array(
				'TWEET' => $ul->write())
			);
		}
		
		return;
	}

	function banners()
	{
		global $db, $cache, $user, $template;
		
		$banners = array();
		if (!$banners = $cache->get('banners'))
		{
			$sql = 'SELECT *
				FROM _banners
				ORDER BY banner_order';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				$banners[$row['banner_id']] = $row;
			}
			$db->sql_freeresult($result);
			
			if (sizeof($banners))
			{
				$cache->save('banners', $banners);
			}
		}
		
		if (!sizeof($banners)) return;
		
		$template->assign_block_vars('banners', array());
		foreach ($banners as $item)
		{
			$template->assign_block_vars('banners.item', array(
				'URL' => (!empty($item['banner_url'])) ? $item['banner_url'] : '',
				'IMAGE' => SDATA . 'base/' . $item['banner_id'] . '.gif',
				'ALT' => $item['banner_alt'])
			);
		}
		
		return;
	}
	
	function founders()
	{
		global $cache, $user, $template;
		
		$founders = array();
		if (!$founders = $cache->get('founders'))
		{
			global $db, $config;
			
			$sql = 'SELECT user_id, username, username_base, user_color, user_email, user_avatar
				FROM _members
				WHERE user_id IN (2,3)
				ORDER BY user_id';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				$row['realname'] = ($row['user_id'] == 2) ? 'Guillermo Azurdia' : 'Gerardo Medina';
				$row['user_avatar'] = $config['avatar_path'] . '/' . $row['user_avatar'];
				$row['user_profile'] = s_link('m', $row['username_base']);
				
				$founders[$row['user_id']] = $row;
			}
			$db->sql_freeresult($result);
			
			$cache->save('founders', $founders);
		}
		
		foreach ($founders as $user_id => $data)
		{
			$template->assign_block_vars('founders', array(
				'REALNAME' => $data['realname'],
				'USERNAME' => $data['username'],
				'RANK' => $user->lang['COMM_FOUNDER'],
//				'RANK' => ($data['user_id'] == 2) ? $user->lang['COMM_FOUNDER'] : $user->lang['COMM_ADMIN'],
				'COLOR' => $data['user_color'],
				'AVATAR' => $data['user_avatar'],
				'PROFILE' => $data['user_profile'])
			);
		}
	}
	
	function extra()
	{
		global $config, $user, $template;
		
		$ttime = time();
		
		$start_date = $user->format_date($config['board_startdate'], $user->lang['DATE_FORMAT']);
		$boarddays = number_format((($ttime - $config['board_startdate']) / 86400));
		
		$template->assign_vars(array(
			'TOTAL_USERS' => $config['max_users'],
			'TOTAL_ARTISTS' => $config['max_artists'],
			'START_DATE' => $start_date,
			'BOARDDAYS' => $boarddays)
		);
		
		return;
	}
	
	//
	// RECENT BOARD POSTS
	//
	function board()
	{
		global $db, $user, $config, $template;
		
		$sql = 'SELECT t.topic_id, t.topic_title, t.forum_id, t.topic_replies, t.topic_color, f.forum_alias, f.forum_name, p.post_id, p.post_username, p.post_time, u.user_id, u.username, u.username_base, u.user_color
			FROM _forums f, _forum_topics t, _forum_posts p, _members u
			WHERE t.forum_id = f.forum_id
				AND p.post_deleted = 0
				AND p.post_id = t.topic_last_post_id
				AND p.poster_id = u.user_id
				AND t.topic_featured = 1
			ORDER BY t.topic_announce DESC, p.post_time DESC
			LIMIT ' . $config['main_topics'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('forum', array(
				'L_TOP_POSTS' => sprintf($user->lang['TOP_FORUM'], $db->sql_numrows($result)),
				'POSTS' => $config['max_posts'],
				'TOPICS' => $config['max_topics'])
			);
			
			do
			{
				$username = ($row['user_id'] != GUEST) ? $row['username'] : (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
				
				$template->assign_block_vars('forum.item', array(
					'U_TOPIC' => ($row['topic_replies']) ? s_link('post', $row['post_id']) . '#' . $row['post_id'] : s_link('topic', $row['topic_id']),
					//'TOPIC_TITLE' => substr_replace(substr($row['topic_title'],0,55),' ...',55,0),
					'TOPIC_TITLE' => _substr($row['topic_title'], 65),
					'TOPIC_REPLIES' => $row['topic_replies'],
					'TOPIC_COLOR' => $row['topic_color'],
					'FORUM_NAME' => $row['forum_name'],
					'FORUM_URL' => s_link('forum', $row['forum_alias']),
					'POST_TIME' => $user->format_date($row['post_time'], 'H:i'),
					'USER_ID' => $row['user_id'],
					'USER_COLOR' => $row['user_color'],
					'USERNAME' => $username,
					'PROFILE' => s_link('m', $row['username_base']))
				);
			}
			while ($row = $db->sql_fetchrow($result));
		}
		$db->sql_freeresult($result);
	}
	
	//
	// LAST POLL
	//
	function poll()
	{
		global $db, $user, $auth, $config, $cache, $template;
		
		if (!$topic_id = $cache->get('last_poll_id'))
		{
			$sql = 'SELECT t.topic_id
				FROM _forum_topics t
				LEFT JOIN _poll_options v ON t.topic_id = v.topic_id
				WHERE t.forum_id = ' . (int) $config['main_poll_f'] . '
					AND t.topic_locked = 0
					AND t.topic_vote = 1 
				ORDER BY t.topic_time DESC 
				LIMIT 1';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$topic_id = $row['topic_id'];
				$cache->save('last_poll_id', $topic_id);
			}
			$db->sql_freeresult($result);
		}
		$topic_id = (int) $topic_id;
		
		if (!$topic_id)
		{
			return;
		}
		
		$sql = 'SELECT t.topic_id, t.topic_locked, t.topic_time, t.topic_replies, t.topic_important, t.topic_vote, f.forum_locked, f.forum_id, f.auth_view, f.auth_read, f.auth_post, f.auth_reply, f.auth_announce, f.auth_pollcreate, f.auth_vote
			FROM _forum_topics t, _forums f
			WHERE t.topic_id = ' . (int) $topic_id . '
				AND f.forum_id = t.forum_id';
		$result = $db->sql_query($sql);

		if (!$topic_data = $db->sql_fetchrow($result))
		{
			return;
		}
		$db->sql_freeresult($result);
		
		$forum_id = (int) $topic_data['forum_id'];
		
		$sql = 'SELECT vd.*, vr.*
			FROM _poll_options vd, _poll_results vr
			WHERE vd.topic_id = ' . $topic_id . '
				AND vr.vote_id = vd.vote_id 
			ORDER BY vr.vote_option_id ASC';
		$result = $db->sql_query($sql);
		
		if ($vote_info = $db->sql_fetchrowset($result))
		{
			$db->sql_freeresult($result);
			$vote_options = sizeof($vote_info);
			
			if ($user->data['is_member'])
			{
				$is_auth = array();
				$is_auth = $auth->forum(AUTH_VOTE, $forum_id, $topic_data);
				
				$sql = 'SELECT *
					FROM _poll_voters
					WHERE vote_id = ' . (int) $vote_info[0]['vote_id'] . '
						AND vote_user_id = ' . (int) $user->data['user_id'];
				$result2 = $db->sql_query($sql);
				
				$user_voted = ($row = $db->sql_fetchrow($result2)) ? TRUE : FALSE;
				$db->sql_freeresult($result2);
			}
			
			$poll_expired = ($vote_info[0]['vote_length']) ? (($vote_info[0]['vote_start'] + $vote_info[0]['vote_length'] < $current_time) ? TRUE : 0) : 0;
			
			$template->assign_block_vars('poll', array(
				'U_POLL_TOPIC' => s_link('topic', $topic_id),
				'S_REPLIES' => $topic_data['topic_replies'],
				'U_POLL_FORUM' => s_link('forum', $config['main_poll_f']),
				'POLL_TITLE' => $vote_info[0]['vote_text'])
			);
			
			if (!$user->data['is_member'] || $user_voted || $poll_expired || !$is_auth['auth_vote'] || $topic_data['topic_locked'])
			{
				$vote_results_sum = 0;
				for ($i = 0; $i < $vote_options; $i++)
				{
					$vote_results_sum += $vote_info[$i]['vote_result'];
				}
				
				$template->assign_block_vars('poll.results', array());
				for ($i = 0; $i < $vote_options; $i++)
				{
					$vote_percent = ($vote_results_sum) ? $vote_info[$i]['vote_result'] / $vote_results_sum : 0;

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
					'S_VOTE_ACTION' => s_link('topic', $topic_id))
				);

				for ($i = 0; $i < $vote_options; $i++)
				{
					$template->assign_block_vars('poll.options.item', array(
						'POLL_OPTION_ID' => $vote_info[$i]['vote_option_id'],
						'POLL_OPTION_CAPTION' => $vote_info[$i]['vote_option_text'])
					);
				} // FOR
			} // IF
		} // IF
	}
}

?>