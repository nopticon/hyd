<?php
// -------------------------------------------------------------
// $Id: _mcc.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
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
	$post_id = request_var('pid', '');
	$post_message = request_var('post_text', '', true);
	if (empty($post_id) || empty($post_message))
	{
		_die();
	}
	
	$sql = 'SELECT *
		FROM _forum_posts
		WHERE post_id = ' . (int) $post_id;
	$result = $db->sql_query($sql);
	
	if (!$postdata = $db->sql_fetchrow($result))
	{
		_die('El mensaje no existe.');
	}
	$db->sql_freeresult($result);
	
	//
	require('./interfase/comments.php');
	$comments = new _comments();
	
	$post_message = $comments->prepare($post_message);
	
	//
	$sql = "UPDATE _forum_posts
		SET post_text = '" . $db->sql_escape($post_message) . "'
		WHERE post_id = " . (int) $post_id;
	$db->sql_query($sql);
	
	redirect(s_link('post', $post_id));
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="pid" value="" size="8" /><br />
<textarea name="post_text" cols="50" rows="15"></textarea><br />
<input type="submit" name="submit" value="Cambiar" />
</form>