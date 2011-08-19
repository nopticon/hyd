<?php
// -------------------------------------------------------------
// $Id: _acp.del_event.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$topic = request_var('topic', 0);
	$important = request_var('important', 0);
	
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
	
	$sql_important = ($important) ? ', topic_important = 1' : '';
	
	$sql = 'UPDATE _forum_topics
		SET topic_color = \'E1CB39\', topic_announce = 1' . $sql_important . '
		WHERE topic_id = ' . (int) $topic;
	$db->sql_query($sql);
	
	echo 'El tema <strong>' . $topicdata['topic_title'] . '</strong> ha sido anunciado.';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="topic" value="" /> <input type="checkbox" name="important" value="1" /> Importante?
<input type="submit" name="submit" value="Anunciar tema" />
</form>
