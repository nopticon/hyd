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

$submit2 = isset($_POST['submit2']);

if ($submit || $submit2)
{
	$news_id = request_var('news_id', 0);
	
	$sql = 'SELECT *
		FROM _news
		WHERE news_id = ' . (int) $news_id;
	$result = $db->sql_query($sql);
	
	if (!$news_data = $db->sql_fetchrow($result))
	{
		_die('La noticia no existe.');
	}
	$db->sql_freeresult($result);
	
	if ($submit2)
	{
		$post_subject = request_var('post_subject', '');
		$post_desc = request_var('post_desc', '', true);
		$post_message = request_var('post_text', '', true);
		if (empty($post_desc) || empty($post_message))
		{
			_die('Campos requeridos.');
		}
		
		require('./interfase/comments.php');
		$comments = new _comments();
		
		$post_message = $comments->prepare($post_message);
		$post_desc = $comments->prepare($post_desc);
		
		//
		$sql = "UPDATE _news
			SET post_subject = '" . $db->sql_escape($post_subject) . "', post_desc = '" . $db->sql_escape($post_desc) . "', post_text = '" . $db->sql_escape($post_message) . "'
			WHERE news_id = " . (int) $news_id;
		$db->sql_query($sql);
		
		$cache->delete('news');
		redirect(s_link('news', $news_id));
	}
	
	if ($submit)
	{
		$template->assign_block_vars('edit', array(
			'ID' => $news_data['news_id'],
			'SUBJECT' => $news_data['post_subject'],
			'DESC' => $news_data['post_desc'],
			'TEXT' => $news_data['post_text'])
		);
	}
}

if (!$submit)
{
	$template->assign_block_vars('field', array());
}

$t_vars = array(
	'S_ACTION' => $u
);
page_layout('EVENTS', 'acp/enews', $t_vars, false);

?>