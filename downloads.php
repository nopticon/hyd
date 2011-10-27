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
define('IN_NUCLEO', true);
require_once('./interfase/common.php');

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
		WHERE d.id = ?
			AND d.ud = ?
			AND d.ub = a.ub';
	if (!$ddata = sql_fieldrow(sql_filter($sql, $download_id, $mode_id))) {
		fatal_error();
	}
	
	$sql = 'UPDATE _dl SET views = views + 1
		WHERE id = ?';
	sql_query(sql_filter($sql, $ddata['id']));
}

//
// Get all available downloads, Audio | Video
//
$sql = 'SELECT d.*, a.name, a.subdomain
	FROM _dl d, _artists a
	WHERE d.ud = ?
		AND d.ub = a.ub
	ORDER BY a.name, d.title';
if (!$dlist = sql_rowset(sql_filter($sql, $mode_id), 'id')) {
	fatal_error();
}

$user->setup();

require_once(ROOT . 'interfase/downloads.php');
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
			WHERE ub = ?
			ORDER BY RAND()
			LIMIT 1';
		if ($imagedata = sql_field(sql_filter($sql, $data['ub']), 'image', 0)) {
			$template->assign_block_vars('item.selected.image', array(
				'A_URL' => $a_url,
				'IMAGE' => '/data/artists/' . $data['ub'] . '/thumbnails/' . $imagedata . '.jpg')
			);
		}
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

page_layout('DOWNLOADS', 'downloads', $template_vars);

?>