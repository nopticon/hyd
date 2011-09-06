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

_auth('founder');

if ($submit)
{
	$post_id = request_var('post_id', 0);
	
	if (!$post_id)
	{
		die('@ empty');
	}
	
	$sql = 'SELECT f.*, t.topic_id, t.topic_first_post_id, t.topic_last_post_id, t.topic_vote, p.post_id, p.poster_id, m.user_id
		FROM _forum_posts p, _forum_topics t, _forums f, _members m
		WHERE p.post_id = ' . (int) $post_id . '
			AND t.topic_id = p.topic_id
			AND f.forum_id = p.forum_id
			AND m.user_id = p.poster_id';
	$result = $db->sql_query($sql);
	
	if (!$post_info = $db->sql_fetchrow($result))
	{
		die('@ post');
	}
	$db->sql_freeresult($result);
	
	$forum_id = $post_info['forum_id'];
	$topic_id = $post_info['topic_id'];
	
	$post_data = array(
		'poster_post' => ($post_info['poster_id'] == $userdata['user_id']) ? true : false,
		'first_post' => ($post_info['topic_first_post_id'] == $post_id) ? true : false,
		'last_post' => ($post_info['topic_last_post_id'] == $post_id) ? true : false,
		'last_topic' => ($post_info['forum_last_topic_id'] == $topic_id) ? true : false,
		'has_poll' => ($post_info['topic_vote']) ? true : false
	);
	
	if ($post_data['first_post'] && $post_data['has_poll'])
	{
		$sql = 'SELECT *
			FROM _poll_options vd, _poll_results vr
			WHERE vd.topic_id = ' . (int) $topic_id . '
				AND vr.vote_id = vd.vote_id
			ORDER BY vr.vote_option_id';
		$result = $db->sql_query($sql);

		if ($row = $db->sql_fetchrow($result))
		{
			$poll_id = $row['vote_id'];
		}
		$db->sql_freeresult($result);
	}
	
	//
	// Process
	//
	$sql = 'DELETE FROM _forum_posts
		WHERE post_id = ' . (int) $post_id;
	$db->sql_query($sql);
	
	if ($post_data['first_post'] && $post_data['last_post'])
	{
		$sql = 'DELETE FROM _forum_topics
			WHERE topic_id = ' . (int) $topic_id;
		$db->sql_query($sql);
		
		$sql = 'DELETE FROM _forum_topics_fav
			WHERE topic_id = ' . (int) $topic_id;
		$db->sql_query($sql);
	}
	
	/*if ($post_data['first_post'] && $post_data['has_poll'])
	{
		$sql = 'DELETE FROM _poll_options
			WHERE topic_id = ' . (int) $topic_id;
		$db->sql_query($sql);
		
		$sql = 'DELETE FROM _poll_results
			WHERE vote_id = ' . (int) $poll_id;
		$db->sql_query($sql);
		
		$sql = 'DELETE FROM _poll_voters
			WHERE vote_id = ' . (int) $poll_id;
		$db->sql_query($sql);
	}*/
	
	//
	// Update stats
	//
	$forum_update_sql = 'forum_posts = forum_posts - 1';
	$topic_update_sql = '';

	if ($post_data['last_post'])
	{
		if ($post_data['first_post'])
		{
			$forum_update_sql .= ', forum_topics = forum_topics - 1';
		}
		else
		{
			$topic_update_sql .= 'topic_replies = topic_replies - 1';
			
			$sql = 'SELECT MAX(post_id) AS last_post_id
				FROM _forum_posts
				WHERE topic_id = ' . (int) $topic_id;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$topic_update_sql .= ', topic_last_post_id = ' . $row['last_post_id'];
			}
			$db->sql_freeresult($result);
		}

		if ($post_data['last_topic'])
		{
			$sql = 'SELECT MAX(topic_id) AS last_topic_id
				FROM _forum_topics
				WHERE forum_id = ' . (int) $forum_id;
			$result = $db->sql_query($sql);
				
			if ($row = $db->sql_fetchrow($result))
			{
				$forum_update_sql .= ', forum_last_topic_id = ' . (($row['last_topic_id']) ? $row['last_topic_id'] : 0);
			}
			$db->sql_freeresult($result);
		}
	}
	else if ($post_data['first_post']) 
	{
		$sql = 'SELECT MIN(post_id) AS first_post_id
			FROM _forum_posts
			WHERE topic_id = ' . (int) $topic_id;
		$result = $db->sql_query($sql);
			
		if ($row = $db->sql_fetchrow($result))
		{
			$topic_update_sql .= 'topic_replies = topic_replies - 1, topic_first_post_id = ' . $row['first_post_id'];
		}
		$db->sql_freeresult($result);
	}
	else
	{
		$topic_update_sql .= 'topic_replies = topic_replies - 1';
	}

	$sql = 'UPDATE _forums
		SET ' . $forum_update_sql . '
		WHERE forum_id = ' . (int) $forum_id;
	$db->sql_query($sql);
	
	if ($topic_update_sql != '')
	{
		$sql = 'UPDATE _forum_topics
			SET ' . $topic_update_sql . '
			WHERE topic_id = ' . (int) $topic_id;
		$db->sql_query($sql);
	}

	$sql = 'UPDATE _members
		SET user_posts = user_posts - 1
		WHERE user_id = ' . (int) $post_info['poster_id'];
	$db->sql_query($sql);
	
	//
	echo 'Deleted:<br /><br />';
	print_r($post_info);
	echo '<br /><br />';
	print_r($post_data);
	echo '<br /><br />';
}

/*

FUNCTIONS

*/
function query($sql)
{
	global $db;
	
	//echo $sql . '<br /><br />';
	return $db->sql_query($sql);
}

function sync($id)
{
	global $db;
	
	$last_topic = 0;
	$total_posts = 0;
	$total_topics = 0;
	
	//
	$sql = 'SELECT COUNT(post_id) AS total 
		FROM _forum_posts
		WHERE forum_id = ' . (int) $id;
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		$total_posts = $row['total'];
	}
	$db->sql_freeresult($result);
	
	$sql = 'SELECT MAX(topic_id) as last_topic, COUNT(topic_id) AS total
		FROM _forum_topics
		WHERE forum_id = ' . (int) $id;
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		$last_topic = $row['last_topic'];
		$total_topics = $row['total'];
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'UPDATE _forums
		SET forum_last_topic_id = ' . (int) $last_topic . ', forum_posts = ' . (int) $total_posts . ', forum_topics = ' . (int) $total_topics . '
		WHERE forum_id = ' . (int) $id;
	query($sql);
	
	return;
}

?>

<html>
<head>
<title>Delete posts</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
# Mensaje: <input type="text" name="post_id" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>