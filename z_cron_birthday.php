<?php
// -------------------------------------------------------------
// $Id: _acp.cron_news.php,v 1.0 2007/07/02 21:11:00 Psychopsia Exp $
//
// STARTED   : Mon Jul 02, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------
define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init(false, true);

$max_email = 10;
@set_time_limit(120);

require('./interfase/emailer.php');
$emailer = new emailer();

$sql = 'SELECT *
	FROM _members
	WHERE user_type NOT IN (' . USER_IGNORE . ', ' . USER_INACTIVE . ")
		AND user_id NOT IN (SELECT ban_userid FROM _banlist)
		AND user_birthday LIKE '%" . date('md') . "'
		AND user_birthday_last < " . (int) date('Y') . "
	ORDER BY username
	LIMIT " . (int) $max_email;
$result = $db->sql_query($sql);

$done = array();
$usernames = array();
while ($row = $db->sql_fetchrow($result))
{
	$emailer->from('notify@rockrepublik.net');
	$emailer->use_template('user_birthday');
	$emailer->email_address($row['user_email']);
	if (!empty($row['user_public_email']) && $row['user_email'] != $row['user_public_email'])
	{
		$emailer->cc($row['user_public_email']);
	}
	
	$emailer->assign_vars(array(
		'USERNAME' => $row['username'])
	);
	$emailer->send();
	$emailer->reset();
	
	$done[] = $row['user_id'];
	$usernames[] = $row['username'];
}
$db->sql_freeresult($result);

if (count($done))
{
	$sql = 'UPDATE _members
		SET user_birthday_last = ' . (int) date('Y') . '
		WHERE user_id IN (' . implode(',', $done) . ')';
	$db->sql_query($sql);
}

_die('Done. @ ' . implode(', ', $usernames));

?>