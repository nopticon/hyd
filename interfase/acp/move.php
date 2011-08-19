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
	$t = request_var('topic_id', 0);
	$f = request_var('forum_id', 0);
	
	if (!$f || !$t)
	{
		_die();
	}
	
	//
	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $t;
	$result = $db->sql_query($sql);
	
	if (!$tdata = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'SELECT *
		FROM _forums
		WHERE forum_id = ' . (int) $f;
	$result = $db->sql_query($sql);
	
	if (!$fdata = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'UPDATE _forum_topics
		SET forum_id = ' . (int) $f . '
		WHERE topic_id = ' . $t;
	$db->sql_query($sql);
	
	$sql = 'UPDATE _forum_posts
		SET forum_id = ' . (int) $f . '
		WHERE topic_id = ' . (int) $t;
	$db->sql_query($sql);
	
	if (in_array($f, array(20, 39)))
	{
		topic_feature($t, 0);
		topic_arkane($t, 0);
	}
	
	sync($f);
	sync($tdata['forum_id']);
	
	//
	//redirect(s_link('forum', $f));
}

/*
FUNCTIONS
*/
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
<title>Move topics</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
# Tema: <input type="text" name="topic_id" /><br /><br />
Foro: <select name="forum_id">
<?php

$sql = 'SELECT forum_id, forum_name
	FROM _forums
	ORDER BY forum_order';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['forum_id'] . '">' . $row['forum_name'] . '</option>';
}
$db->sql_freeresult($result);

?>
</select>

<br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>