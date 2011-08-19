<?php
// -------------------------------------------------------------
// $Id: downloads.php,v 1.5 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Thr Dec 15, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();

$mode = request_var('mode', '');
$download_id = request_var('download_id', 0);

if ($mode != 'audio' && $mode != 'video')
{
	$mode = 'audio';
}
$mode_id = ($mode == 'audio') ? 1 : 2;

//
// Get data of selected download
//
if ($download_id)
{
	$sql = 'SELECT d.*
		FROM _dl d, _artists a
		WHERE d.id = ' . (int) $download_id . '
			AND d.ud = ' . (int) $mode_id . '
			AND d.ub = a.ub';
	$result = $db->sql_query($sql);
	
	if (!$ddata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$db->sql_query('UPDATE _dl SET views = views + 1 WHERE id = ' . (int) $ddata['id']);
}

//
// Get all available downloads, Audio | Video
//
$sql = 'SELECT d.*, a.name, a.subdomain
	FROM _dl d, _artists a
	WHERE d.ud = ' . (int) $mode_id . '
		AND d.ub = a.ub
	ORDER BY a.name, d.title';
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$dlist = array();
	do
	{
		$dlist[$row['id']] = $row;
	}
	while ($row = $db->sql_fetchrow($result));
	$db->sql_freeresult($result);
}
else
{
	fatal_error();
}

$user->setup();

require('./interfase/downloads.php');
$downloads = new downloads();

foreach ($dlist as $id => $data)
{
	$template->assign_block_vars('item', array(
		'DOWNLOAD_ID' => $id,
		'D_TITLE' => $data['title'])
	);
	
	if ($download_id && ($download_id == $id))
	{
		$ddata += $downloads->dl_type($ddata['ud']);
		
		$a_url = s_link('a', $data['subdomain']);
		
		$stats_text = '';
		foreach (array('views' => 'VIEW', 'downloads' => 'DL') as $item => $stats_lang)
		{
			$stats_text .= (($stats_text != '') ? ', ' : '') . '<strong>' . $ddata[$item] . '</strong> ' . $user->lang[$stats_lang] . (($ddata[$item] > 1) ? 's' : '');
		}
		
		$template->assign_block_vars('item.selected', array(
			'S_ACTION' => s_link('a', array($data['subdomain'], 9, $ddata['id'], 'save')),
			'S_DIRECT' => '/data/artists/' . $ddata['extension'] . '/' . $ddata['id'] . '.' . $ddata['extension'],
			
			'A_NAME' => $data['name'],
			'D_DURATION' => $ddata['duration'],
			'D_ALBUM' => $ddata['album'],
			'D_YEAR' => $ddata['year'],
			'D_POSTS' => $ddata['posts'],
			'D_VOTES' => $ddata['votes'],
			'D_FILESIZE' => $downloads->format_filesize($ddata['filesize']),
			'D_STATS' => $stats_text)
		);
		
		$sql = 'SELECT image
			FROM _artists_images
			WHERE ub = ' . (int) $data['ub'] . '
			ORDER BY RAND()
			LIMIT 1';
		$result = $db->sql_query($sql);
		
		if ($imagedata = $db->sql_fetchrow($result))
		{
			$template->assign_block_vars('item.selected.image', array(
				'A_URL' => $a_url,
				'IMAGE' => '/data/artists/' . $data['ub'] . '/thumbnails/' . $imagedata['image'] . '.jpg')
			);
		}
		$db->sql_freeresult($result);
	}
	else
	{
		$template->assign_block_vars('item.header', array(
			'U_DOWNLOAD_ID' => s_link('dl', array($mode, $id)) . '#' . $id,
			'A_NAME' => $data['name'])
		);
	}
}

$template_vars = array(
	'URL_AUDIO' => s_link('dl', 'audio'),
	'URL_VIDEO' => s_link('dl', 'video')
);

page_layout('DOWNLOADS', 'downloads_body', $template_vars);

?>