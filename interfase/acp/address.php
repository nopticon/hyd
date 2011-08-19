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

//
$sql = "SELECT *
	FROM _members
	WHERE user_type NOT IN (" . USER_IGNORE . ", " . USER_INACTIVE . ")
		AND user_email <> ''
		AND user_id NOT IN (
			SELECT ban_userid
			FROM _banlist
			WHERE ban_userid <> 0
		)
	ORDER BY username";
$result = $db->sql_query($sql);

$limit = 225;
$steps = 0;
$items = 0;
$trash = array();

while ($row = $db->sql_fetchrow($result))
{
	if (!preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is', $row['user_email']))
	{
		$trash[] = $row['user_email'];
		continue;
	}
	
	if (!$items || $items == $limit)
	{
		$items = 0;
		$steps++;
		
		$template->assign_block_vars('step', array(
			'STEPS' => $steps)
		);
	}
	
	$template->assign_block_vars('step.item', array(
		'USERNAME' => $row['username'],
		'USER_EMAIL' => $row['user_email'])
	);

	$items++;
}
$db->sql_freeresult($result);

page_layout('ACP', 'acp_address');

?>