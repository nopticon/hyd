<?php
// -------------------------------------------------------------
// $Id: bounce.php,v 1.3 2006/02/16 04:47:52 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();

//
// Get data
//
$bounce_id = intval(request_var('id', 0));
$bounce_mode = request_var('mode', '');

if ($bounce_id && $bounce_mode)
{
	switch ($bounce_mode)
	{
		case 'f':
			$sql = 'SELECT *
				FROM _links
				WHERE id = ' . (int) $bounce_id;
			break;
		case 'u':
			$sql = 'SELECT user_website
				FROM _members
				WHERE user_id = ' . (int) $bounce_id . '
					AND user_id <> ' . GUEST;
			break;
		default:
			fatal_error();
			break;
	}
	$result = $db->sql_query($sql);
	
	if (!$bounce_data = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	switch ($bounce_mode)
	{
		case 'f':
			$bounce_data['redirect_url'] = 'http://' . $bounce_data['url'];
			break;
		case 'u':
			if ($bounce_data['user_website'] != '')
			{
				$bounce_data['redirect_url'] = $bounce_data['user_website'];
			}
			break;
	}
	
	if ($bounce_data['redirect_url'] == '')
	{
		redirect(s_link('bounce'));
	}
	
	if ($bounce_mode == 'f')
	{
		$db->sql_query('UPDATE _links SET views = views + 1 WHERE id = ' . (int) $bounce_id);
	}
	
	redirect($bounce_data['redirect_url']);
}

//
// SETUP USER SETTINGS
//
$user->setup();

//
// SHOW LINKS LIST
//
$f_total = 0;
$a_total = 0;
$u_total = 0;

$sql_in = '';
$links = array();

//
// FRIENDS
//
$sql = "SELECT * 
	FROM _links
	WHERE image <> '' 
	ORDER BY image ASC";
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	do
	{
		$links[] = $row;
		$f_total++;
	}
	while ($row = $db->sql_fetchrow($result));
	
	$db->sql_freeresult($result);
}

$sql = "SELECT * 
	FROM _links
	WHERE image = '' 
	ORDER BY text ASC";
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	do
	{
		$links[] = $row;
		$f_total++;
	}
	while ($row = $db->sql_fetchrow($result));
	
	$db->sql_freeresult($result);
}

if ($f_total)
{
	$template->assign_block_vars('block', array(
		'LANG' => $user->lang['LINKS_FRIENDS'])
	);
	
	for ($i = 0; $i < $f_total; $i++)
	{
		$image_exists = (($links[$i]['image'] != '') && @file_exists('../data/web/' . $links[$i]['image'])) ? TRUE : FALSE;
		$url = s_link('bounce', array('f', $links[$i]['id']));
		
		$template->assign_block_vars('block.item', array(
			'TEXT' => (!$image_exists) ? $links[$i]['url'] : '',
			'U_GOTO' => $url)
		);
		
		if ($image_exists)
		{
			$template->assign_block_vars('block.item.image', array(
				'SRC' => '/data/web/' . $links[$i]['image']
			));
		}
		else
		{
			$template->assign_block_vars('block.item.name', array(
				'CLASS' => 'bold',
				'URL' => $url,
				'TEXT' => $links[$i]['text'],
				'BLANK' => 1
			));
		}
	}
}

//
// ARTISTS
//
$sql = 'SELECT subdomain, name, www
	FROM _artists
	WHERE www <> \'\'
	ORDER BY name';
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('block', array(
		'LANG' => $user->lang['UB']
	));
	
	do
	{
		$template->assign_block_vars('block.item', array(
			'TEXT' => $row['www'],
			'U_GOTO' => s_link('a', array($row['subdomain'], 14)))
		);
		
		$template->assign_block_vars('block.item.name', array(
			'CLASS' => 'bold',
			'URL' => s_link('a', $row['subdomain']),
			'TEXT' => $row['name']
		));
	}
	while ($row = $db->sql_fetchrow($result));
}

//
// USERS
//
$sql = "SELECT user_id, username, username_base, user_color, user_website 
	FROM _members
	WHERE user_website <> '' 
	ORDER BY username";
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('block', array(
		'LANG' => $user->lang['USERS']
	));
	
	do
	{
		$template->assign_block_vars('block.item', array(
			'TEXT' => $row['user_website'],
			'U_GOTO' => s_link('bounce', array('u', $row['user_id']))
		));
		
		$template->assign_block_vars('block.item.name', array(
			'COLOR' => $row['user_color'],
			'URL' => s_link('m', $row['username_base']),
			'TEXT' => $row['username']
		));
	}
	while ($row = $db->sql_fetchrow($result));
}

//
// OUTPUT THE PAGE
//
page_layout('LINKS_FRIENDS', 'bounce_body');

?>