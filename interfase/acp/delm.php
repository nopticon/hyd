<?php
// -------------------------------------------------------------
// $Id: _acp.ban.php,v 1.0 2007/06/26 08:22:00 Psychopsia Exp $
//
// STARTED   : Tue Jun 22, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$username = request_var('username', '');
	$username = get_username_base($username);
	
	$sql = "SELECT *
		FROM _members
		WHERE username_base = '" . $db->sql_escape($username) . "'";
	$result = $db->sql_query($sql);
	
	if (!$userdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = array(
		'DELETE FROM _members WHERE user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _banlist WHERE ban_userid = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_ban WHERE user_id = ' . (int) $userdata['user_id'] . ' OR banned_user = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_friends WHERE user_id = ' . (int) $userdata['user_id'] . ' OR buddy_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_group WHERE user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_iplog WHERE log_user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_ref_assoc WHERE ref_uid = ' . (int) $userdata['user_id'] . ' OR ref_orig = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_ref_invite WHERE invite_uid = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_unread WHERE user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _members_viewers WHERE viewer_id = ' . (int) $userdata['user_id'] . ' OR user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _poll_voters WHERE vote_user_id = ' . (int) $userdata['user_id'],
		'UPDATE _members_posts SET poster_id = 1 WHERE poster_id = ' . (int) $userdata['user_id'],
		'UPDATE _news_posts SET poster_id = 1 WHERE poster_id = ' . (int) $userdata['user_id'],
		
		'UPDATE _artists_posts SET poster_id = 1 WHERE poster_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _artists_auth WHERE user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _artists_viewers WHERE user_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _artists_voters WHERE user_id = ' . (int) $userdata['user_id'],
		'UPDATE _dl_posts SET poster_id = 1 WHERE poster_id = ' . (int) $userdata['user_id'],
		'DELETE FROM _dl_voters WHERE user_id = ' . (int) $userdata['user_id'],
		'UPDATE _events_posts SET poster_id = 1 WHERE poster_id = ' . (int) $userdata['user_id'],
		'UPDATE _forum_posts SET poster_id = 1 WHERE poster_id = ' . (int) $userdata['user_id'],
		'UPDATE _forum_topics SET topic_poster = 1 WHERE topic_poster = ' . (int) $userdata['user_id']
	);
	$db->sql_query($sql);
	/*echo '<pre>';
	print_r($sql);
	echo '</pre>';*/
	
	_die('El registro de <strong>' . $userdata['username'] . '</strong> fue eliminado.');
	
}

?>
<html>
<head>
<title>Delete users</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Nombre de usuario: <input type="text" name="username" size="100" />
<input type="submit" name="submit" value="Consultar" />
</form>
</body>
</html>