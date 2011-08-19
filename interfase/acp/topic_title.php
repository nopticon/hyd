<?php
// -------------------------------------------------------------
// $Id: _acp.del_event.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$topic = request_var('topic', 0);
	$title = request_var('title', '');
	
	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $topic;
	$result = $db->sql_query($sql);
	
	$topicdata = array();
	if (!$topicdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = "UPDATE _forum_topics
		SET topic_title = '" . $db->sql_escape($title) . "'
		WHERE topic_id = " . (int) $topic;
	$db->sql_query($sql);
	
	echo 'El titulo del tema <strong>' . $topicdata['topic_title'] . '</strong> ha sido cambiado por <strong>' . $title . '</strong>.';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="topic" value="" size="5" />
<input type="text" name="title" value="" size="50" />
<input type="submit" name="submit" value="Cambiar titulo" />
</form>