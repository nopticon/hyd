<?php
// -------------------------------------------------------------
// $Id: mass_email.php,v 1.12 2006/11/02 00:20:00 Psychopsia Exp $
//
// STARTED   : Mon Oct 23, 2006
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

require('./interfase/comments.php');
$comments = new _comments();

$message = '';
$subject = '';

//
// Do the job ...
//
if ($submit)
{
	$post_mode = request_var('post_mode', 0);
	$post_subject = request_var('post_subject', '');
	$post_message = request_var('post_message', '', true);
	$post_skip = request_var('post_skip', '', true);
	$post_reply = request_var('post_reply', 0);
	
	$post_message = $comments->prepare($post_message);
	
	$skip_list = '';
	if (!empty($post_skip))
	{
		$e_skip = explode("\n", $post_skip);
		
		foreach ($e_skip as $i => $row)
		{
			$row = get_username_base($row);
			$e_skip[$i] = "'" . $db->sql_escape($row) . "'";
		}
		
		$sql = 'SELECT user_id
			FROM _members
			WHERE username_base IN (' . implode(',', $e_skip) . ')';
		$result = $db->sql_query($sql);
		
		$user_skip = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$user_skip[] = $row['user_id'];
		}
		$db->sql_freeresult($result);
		
		$skip_list = ' AND u.user_id NOT IN (' . implode(', ', $user_skip) . ') ';
	}
	
	switch ($post_mode)
	{
		case 1:
			$sql = 'SELECT u.user_id, u.username
				FROM _members u
				WHERE u.user_type <> 2
					AND u.user_id NOT IN (SELECT ban_userid FROM _banlist)
					AND u.user_id <> ' . (int) $user->data['user_id'] . $skip_list . '
				ORDER BY u.username';
			break;
		case 2:
			$sql = 'SELECT u.user_id, u.username
				FROM _members u
				WHERE u.user_type = 6
					AND u.user_id NOT IN (SELECT ban_userid FROM _banlist)
					AND u.user_id <> ' . (int) $user->data['user_id'] . $skip_list . '
				ORDER BY u.username';
			break;
		case 3:
			$sql = 'SELECT u.user_id, u.username
				FROM _members_friends b, _members u
				WHERE b.buddy_id = ' . (int) $user->data['user_id'] . $skip_list . '
					AND b.user_id = u.user_id
					AND u.user_id NOT IN (SELECT ban_userid FROM _banlist)
				ORDER BY u.username';
			break;
	}
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result))
	{
		$row_message = str_replace('[username]', $row['username'], $post_message);
		
		$insert = array(
			'privmsgs_subject' => $post_subject,
			'privmsgs_from_userid' => (int) $user->data['user_id'],
			'privmsgs_to_userid' => (int) $row['user_id'],
			'privmsgs_date' => $user->time,
			'msg_ip' => $user->ip,
			'msg_can_reply' => (int) $post_reply,
			'privmsgs_mass' => 1,
			'privmsgs_text' => $row_message
		);
		
		$db->sql_query('INSERT INTO _dc' . $db->sql_build_array('INSERT', $insert));
		$dc_id = $db->sql_nextid();
		
		$sql = 'UPDATE _dc SET parent_id = ' . (int) $dc_id . ', last_msg_id = ' . (int) $dc_id . ', msg_deleted = ' . (int) $user->data['user_id'] . '
			WHERE msg_id = ' . (int) $dc_id;
		$db->sql_query($sql);
		
		$user->save_unread(UH_NOTE, $dc_id, 0, $row['user_id']);
		
		echo $row['username'] . '<br />';
		flush();
	}
	$db->sql_freeresult($result);
	
	_die();
}

$template_vars = array(
	'S_ACTION' => $u
);
page_layout('MCONV', 'acp/mconv', $template_vars, false);

?>