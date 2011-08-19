<?php
// -------------------------------------------------------------
//
// $Id: functions_validate.php,v 1.1.1.1 2006/01/06 03:36:48 Psychopsia Exp $
//
// FILENAME  : functions_validate.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001 Rock Republik NET
// WWW       : http://www.rockrepublik.net/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//
// Check to see if the username has been taken, or if it is disallowed.
// Also checks if it includes the " character, which we don't allow in usernames.
// Used for registering, changing names, and posting anonymously with a username
//
function validate_username($username)
{
	global $db, $user;

	// Remove doubled up spaces
	$username = preg_replace('#\s+#', ' ', trim($username)); 
	$username = phpbb_clean_username($username);

	$sql = "SELECT username
		FROM _members
		WHERE LOWER(username) = '" . $db->sql_escape(strtolower($username)) . "'";
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		if (($user->data['is_member'] && $row['username'] != $userdata['username']) || !$user->data['is_member'])
		{
			$db->sql_freeresult($result);
			return array('error' => true, 'error_msg' => $user->lang['USERNAME_TAKEN']);
		}
	}
	$db->sql_freeresult($result);

	$sql = "SELECT group_name
		FROM _groups
		WHERE LOWER(group_name) = '" . $db->sql_escape(strtolower($username)) . "'";
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		$db->sql_freeresult($result);
		return array('error' => true, 'error_msg' => $user->lang['USERNAME_TAKEN']);
	}
	$db->sql_freeresult($result);

	$sql = 'SELECT disallow_username
		FROM _disallow';
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		do
		{
			if (preg_match("#\b(" . str_replace("\*", ".*?", preg_quote($row['disallow_username'], '#')) . ")\b#i", $username))
			{
				$db->sql_freeresult($result);
				return array('error' => true, 'error_msg' => $user->lang['USERNAME_DISALLOWED']);
			}
		}
		while($row = $db->sql_fetchrow($result));
	}
	$db->sql_freeresult($result);

	// Don't allow " and ALT-255 in username.
	if (strstr($username, '"') || strstr($username, 'ñ') || strstr($username, 'Ñ') || strstr($username, '&quot;') || strstr($username, chr(160)))
	{
		return array('error' => true, 'error_msg' => $user->lang['USERNAME_INVALID']);
	}

	return array('error' => false, 'error_msg' => '');
}

//
// Check to see if email address is banned
// or already present in the DB
//
function validate_email($email)
{
	global $db, $user;

	if ($email != '')
	{
		if (preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is', $email))
		{
			$sql = 'SELECT ban_email
				FROM _banlist';
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				do
				{
					$match_email = str_replace('*', '.*?', $row['ban_email']);
					if (preg_match('/^' . $match_email . '$/is', $email))
					{
						$db->sql_freeresult($result);
						return array('error' => true, 'error_msg' => $user->lang['EMAIL_BANNED']);
					}
				}
				while($row = $db->sql_fetchrow($result));
			}
			$db->sql_freeresult($result);

			$sql = "SELECT user_email
				FROM _members
				WHERE user_email = '" . $db->sql_escape($email) . "'";
			$result = $db->sql_query($sql);
		
			if ($row = $db->sql_fetchrow($result))
			{
				return array('error' => true, 'error_msg' => $user->lang['EMAIL_TAKEN']);
			}
			$db->sql_freeresult($result);

			return array('error' => false, 'error_msg' => '');
		}
	}

	return array('error' => true, 'error_msg' => $user->lang['EMAIL_INVALID']);
}

//
// Does supplementary validation of optional profile fields. This expects common stuff like trim() and strip_tags()
// to have already been run. Params are passed by-ref, so we can set them to the empty string if they fail.
//
function validate_optional_fields(&$icq, &$aim, &$msnm, &$yim, &$website, &$location, &$occupation, &$interests, &$sig)
{
	$check_var_length = array('aim', 'msnm', 'yim', 'location', 'occupation', 'interests', 'sig');

	for($i = 0; $i < count($check_var_length); $i++)
	{
		if (strlen($$check_var_length[$i]) < 2)
		{
			$$check_var_length[$i] = '';
		}
	}

	// ICQ number has to be only numbers.
	if (!preg_match('/^[0-9]+$/', $icq))
	{
		$icq = '';
	}
	
	// website has to start with http://, followed by something with length at least 3 that
	// contains at least one dot.
	if ($website != "")
	{
		if (!preg_match('#^http[s]?:\/\/#i', $website))
		{
			$website = 'http://' . $website;
		}

		if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $website))
		{
			$website = '';
		}
	}

	return;
}

?>