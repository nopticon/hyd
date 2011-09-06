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

_auth('mod');

// submission
if ($submit)
{
	$from_topic = request_var('from_topic', 0);
	$to_topic = request_var('to_topic', 0);
	
	if (!$from_topic || !$to_topic || $from_topic == $to_topic)
	{
		_die();
	}

	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $from_topic;
	$result = $db->sql_query($sql);
	
	if (!$row = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $to_topic;
	$result = $db->sql_query($sql);
	
	if (!$row = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	$from_forum_id = (int) $row['forum_id'];
	$from_poll = (int) $row['topic_vote'];
	$to_forum_id = (int) $row['forum_id'];
	$to_poll = (int) $row['topic_vote'];
	
	if ($from_poll)
	{
		if ($to_poll)
		{
			$vote_id = 0;
			$sql = 'SELECT vote_id
				FROM _poll_options
				WHERE topic_id = ' . (int) $from_topic;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$vote_id = $row['vote_id'];
			}
			$db->sql_freeresult($result);
			
			if ($vote_id)
			{
				$sql = array(
					'DELETE FROM _poll_voters WHERE vote_id = ' . (int) $vote_id,
					'DELETE FROM _poll_results WHERE vote_id = ' . (int) $vote_id,
					'DELETE FROM _poll_options WHERE vote_id = ' . (int) $vote_id
				);
				$db->sql_query($sql);
			}
		}
		else
		{
			$sql = 'UPDATE _poll_options
				SET topic_id = ' . (int) $to_topic . '
				WHERE topic_id = ' . (int) $from_topic;
			$db->sql_query($sql);
		}
	}
	
	// Update destination toic
	$sql = 'SELECT topic_views
		FROM _forum_topics
		WHERE topic_id = ' . (int) $from_topic;
	$result = $db->sql_query($sql);
	
	if ($view_row = $db->sql_fetchrow($result))
	{
		$sql = 'UPDATE _forum_topics SET topic_views = topic_views + ' . (int) $view_row['topic_views'] . '
			WHERE topic_id = ' . (int) $to_topic;
		$db->sql_query($sql);
	}
	$db->sql_freeresult($result);
	
	//
	//
	$sql = 'SELECT *
		FROM _forum_topics_fav
		WHERE topic_id = ' . (int) $to_topic;
	$result = $db->sql_query($sql);
	
	$user_ids = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$user_ids[] = $row['user_id'];
	}
	$db->sql_freeresult($result);
	
	$sql_user = (sizeof($user_ids)) ? ' AND user_id NOT IN (' . implode(', ', $user_ids) . ')' : '';
	
	$sql = array(
		'UPDATE _forum_topics_fav SET topic_id = ' . (int) $to_topic . ' WHERE topic_id = ' . (int) $from_topic . $sql_user,
		'DELETE FROM _forum_topics_fav WHERE topic_id = ' . (int) $from_topic,
		'UPDATE _forum_posts SET forum_id = ' . (int) $to_forum_id . ', topic_id = ' . (int) $to_topic . ' WHERE topic_id = ' . (int) $from_topic,
		'DELETE FROM _forum_topics WHERE topic_id = ' . (int) $from_topic,
		'DELETE FROM _members_unread WHERE element = ' . UH_T . ' AND item = ' . (int) $from_topic,
	);
	if ($from_poll && !$to_poll)
	{
		$sql[] = 'UPDATE _forum_topics SET topic_vote = 1 WHERE topic_id = ' . (int) $to_topic;
	}
	$db->sql_query($sql);
	
	$user->save_unread(UH_T, $to_topic);
	
	if (in_array($to_forum_id, array(20, 39)))
	{
		topic_feature($to_topic, 0);
		topic_arkane($to_topic, 0);
	}
	
	sync('topic', $to_topic);
	sync('forum', $to_forum_id);
	
	if ($from_forum_id != $to_forum_id)
	{
		sync('forum', $from_forum_id);
	}
}

/* */
function sync($type, $id = false)
{
	global $db;

	switch($type)
	{
		case 'all forums':
			$sql = 'SELECT forum_id
				FROM _forums';
			$result = $db->sql_query($sql);
			
			while ($row = $db->sql_fetchrow($result))
			{
				sync('forum', $row['forum_id']);
			}
			$db->sql_freeresult($result);
			break;
		case 'all topics':
			$sql = 'SELECT topic_id
				FROM _forum_topics';
			$result = $db->sql_query($sql);
			
			while( $row = $db->sql_fetchrow($result) )
			{
				sync('topic', $row['topic_id']);
			}
			$db->sql_freeresult($result);
			break;
		case 'forum':
			$sql = 'SELECT COUNT(post_id) AS total
				FROM _forum_posts
				WHERE forum_id = ' . (int) $id;
			$result = $db->sql_query($sql);
			
			$total_posts = 0;
			if ($row = $db->sql_fetchrow($result))
			{
				$total_posts = $row['total'];
			}
			$db->sql_freeresult($result);
			
			$last_topic = 0;
			$sql = 'SELECT post_id, topic_id
				FROM _forum_posts
				WHERE forum_id = ' . (int) $id . '
				ORDER BY post_time DESC';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$last_topic = $row['topic_id'];
			}
			$db->sql_freeresult($result);
			
			$sql = 'SELECT COUNT(topic_id) AS total
				FROM _forum_topics
				WHERE forum_id = ' . (int) $id;
			$result = $db->sql_query($sql);
			
			$total_topics = 0;
			if ($row = $db->sql_fetchrow($result))
			{
				$total_topics = $row['total'];
			}
			$db->sql_freeresult(result);
			
			$sql = 'UPDATE _forums
				SET forum_last_topic_id = ' . (int) $last_topic . ', forum_posts = ' . (int) $total_posts . ', forum_topics = ' . (int) $total_topics . '
				WHERE forum_id = ' . (int) $id;
			$db->sql_query($sql);
			break;
		case 'topic':
			$sql = 'SELECT MAX(post_id) AS last_post, MIN(post_id) AS first_post, COUNT(post_id) AS total_posts
				FROM _forum_posts
				WHERE topic_id = ' . (int) $id;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				if ($row['total_posts'])
				{
					$sql = 'UPDATE _forum_topics
						SET topic_replies = ' . (int) ($row['total_posts'] - 1) . ', topic_first_post_id = ' . (int) $row['first_post'] . ', topic_last_post_id = ' . (int) $row['last_post'] . '
						WHERE topic_id = ' . (int) $id;
				}
				else
				{
					$sql = 'DELETE FROM _forum_topics WHERE topic_id = ' . (int) $id;
				}
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result);
									
			break;
	}
	
	return true;
}
/* */

page_layout('Unir temas', 'acp/a_merge', false);

?>
