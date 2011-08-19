<?php
// -------------------------------------------------------------
// $Id: merge.php,v 1.7 2006/08/24 02:34:54 Psychopsia Exp $
//
// STARTED   : Sat Nov 19, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$submit = isset($HTTP_POST_VARS['submit']);

// submission
if ($submit)
{
	$topic_id = request_var('topic_id', 0);
	
	if (!$topic_id)
	{
		die();
	}

	$sql = 'SELECT *
		FROM _forum_topics
		WHERE topic_id = ' . (int) $topic_id;
	$result = $db->sql_query($sql);
	
	if (!$data = $db->sql_fetchrow($result))
	{
		die('NO FROM TOPIC: ' . $sql);
	}
	$db->sql_freeresult($result);
	
	$title = ucfirst(strtolower($data['topic_title']));
	
	$sql = "UPDATE _forum_topics
		SET topic_title = '" . $db->sql_escape($title) . "'
		WHERE topic_id = " . (int) $topic_id;
	$db->sql_query($sql);
	
	echo $data['topic_title'] . '<br /><br />';
	echo $title . '<br /><br />';
}
/* */

?><html>
<head>
<title>Topic title case</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Tema a cambiar: <input type="text" name="topic_id" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>