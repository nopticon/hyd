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