<?php
// -------------------------------------------------------------
// $Id: emoticons.php,v 1.3 2006/02/16 04:47:53 Psychopsia Exp $
//
// STARTED   : Mon Oct 18, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
define('NO_A_META', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$smilies = array();
if (!$smilies = $cache->get('smilies'))
{
	$sql = 'SELECT *
		FROM _smilies
		ORDER BY LENGTH(code) DESC';
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		do
		{
			$smilies[] = $row;
		}
		while ($row = $db->sql_fetchrow($result));
		$db->sql_freeresult($result);
		
		$cache->save('smilies', $smilies);
	}
}

foreach ($smilies as $smile_url => $data)
{
	$template->assign_block_vars('smilies_row', array(
		'CODE' => $data['code'],
		'IMAGE' => $config['smilies_path'] . '/' . $data['smile_url'],
		'DESC' => $data['emoticon'])
	);
}

page_layout('EMOTICONS', 'emoticons_body');

?>