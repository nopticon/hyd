<?php
// -------------------------------------------------------------
// $Id: umove.php,v 1.0 2006/09/06 23:43:00 Psychopsia Exp $
//
// STARTED   : Wed Sep 06, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$topics = request_var('topic_id', '');
	$topics = array_map('intval', explode(',', $topics));
	
	$sql = 'SELECT forum_id, topic_id
		FROM _forum_topics
		WHERE topic_id IN (' . implode(', ', $topics) . ')';
	$result = $db->sql_query($sql);
	
	$forums_id_sql = array();
	$topics_id = array();
	
	while ($row = $db->sql_fetchrow($result))
	{
		$forums_id_sql[] = (int) $row['forum_id'];
		$topics_id[] = (int) $row['topic_id'];
	}
	$db->sql_freeresult($result);
	
	$topic_id_sql = implode(',', $topics_id);
	
	//
	$sql = 'SELECT post_id
		FROM _forum_posts
		WHERE topic_id IN (' . $topic_id_sql . ')';
	$result = $db->sql_query($sql);

	$posts_id = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$posts_id[] = (int) $row['post_id'];
	}
	$db->sql_freeresult($result);
	
	$post_id_sql = implode(',', $posts_id);
	
	//
	$sql = 'SELECT vote_id
		FROM _poll_options
		WHERE topic_id IN (' . $topic_id_sql . ')';
	$result = $db->sql_query($sql);
	
	$votes_id = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$votes_id[] = (int) $row['vote_id'];
	}
	$db->sql_freeresult($result);
	
	$vote_id_sql = implode(',', $votes_id);
	
	//
	$sql = 'SELECT poster_id, COUNT(post_id) AS posts
		FROM _forum_posts
		WHERE topic_id IN (' . $topic_id_sql . ')
		GROUP BY poster_id';
	$result = $db->sql_query($sql);
	
	$members_sql = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$members_sql[] = 'UPDATE _members SET user_posts = user_posts - ' . (int) $row['posts'] . ' WHERE user_id = ' . (int) $row['poster_id'];
	}
	$db->sql_freeresult($result);
	
	foreach ($members_sql as $sql)
	{
		$db->sql_query($sql);
	}
	
	//
	// Got all required info so go ahead and start deleting everything
	//
	$sql = 'DELETE
		FROM _forum_topics
		WHERE topic_id IN (' . $topic_id_sql . ')';
	$db->sql_query($sql);
	
	$sql = 'DELETE
		FROM _forum_topics_fav
		WHERE topic_id IN (' . $topic_id_sql . ')';
	$db->sql_query($sql);
	
	if ($post_id_sql != '')
	{
		$sql = 'DELETE
			FROM _forum_posts
			WHERE post_id IN (' . $post_id_sql . ')';
		$db->sql_query($sql);
	}

	if ($vote_id_sql != '')
	{
		$sql = 'DELETE
			FROM _poll_options
			WHERE vote_id IN (' . $vote_id_sql . ')';
		$db->sql_query($sql);
		
		$sql = 'DELETE
			FROM _poll_results
			WHERE vote_id IN (' . $vote_id_sql . ')';
		$db->sql_query($sql);
		
		$sql = 'DELETE
			FROM _poll_voters
			WHERE vote_id IN (' . $vote_id_sql . ')';
		$db->sql_query($sql);
	}
	
	//
	$sql = 'DELETE FROM _members_unread
		WHERE element = 8
			AND item IN (' . $topic_id_sql . ')';
	$db->sql_query($sql);
	
	//
	foreach ($forums_id_sql as $forum_id)
	{
		sync($forum_id);
	}
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
	$db->sql_query($sql);
	
	return;
}

?>
<html>
<head>
<title>Delete posts</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
# Temas: <input type="text" name="topic_id" size="100" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>