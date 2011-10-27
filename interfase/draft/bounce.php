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

//
// Get data
//
$bounce_id = intval(request_var('id', 0));
$bounce_mode = request_var('mode', '');

if ($bounce_id && $bounce_mode) {
	switch ($bounce_mode) {
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
	if (!$bounce_data = sql_fieldrow($sql)) {
		fatal_error();
	}
	
	switch ($bounce_mode) {
		case 'f':
			$bounce_data['redirect_url'] = 'http://' . $bounce_data['url'];
			break;
		case 'u':
			if ($bounce_data['user_website'] != '') {
				$bounce_data['redirect_url'] = $bounce_data['user_website'];
			}
			break;
	}
	
	if ($bounce_data['redirect_url'] == '') {
		redirect(s_link('bounce'));
	}
	
	if ($bounce_mode == 'f') {
		$sql = 'UPDATE _links SET views = views + 1
			WHERE id = ?';
		sql_query(sql_filter($sql, $bounce_id));
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
$links = sql_rowset($sql);
$f_total = sizeof($links);

$sql = "SELECT * 
	FROM _links
	WHERE image = '' 
	ORDER BY text ASC";
$links = sql_rowset($sql);
$f_total = sizeof($links);

if ($f_total) {
	$template->assign_block_vars('block', array(
		'LANG' => $user->lang['LINKS_FRIENDS'])
	);
	
	for ($i = 0; $i < $f_total; $i++) {
		$image_exists = (($links[$i]['image'] != '') && @file_exists('../data/web/' . $links[$i]['image'])) ? true : false;
		$url = s_link('bounce', array('f', $links[$i]['id']));
		
		$template->assign_block_vars('block.item', array(
			'TEXT' => (!$image_exists) ? $links[$i]['url'] : '',
			'U_GOTO' => $url)
		);
		
		if ($image_exists) {
			$template->assign_block_vars('block.item.image', array(
				'SRC' => '/data/web/' . $links[$i]['image']
			));
		} else {
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
$sql = "SELECT subdomain, name, www
	FROM _artists
	WHERE www <> ''
	ORDER BY name";
$artists = sql_rowset($sql);

foreach ($artists as $i => $row) {
	if (!$i) {
		$template->assign_block_vars('block', array(
			'LANG' => $user->lang['UB'])
		);
	}
	
	$template->assign_block_vars('block.item', array(
		'TEXT' => $row['www'],
		'U_GOTO' => s_link('a', array($row['subdomain'], 14)))
	);
	
	$template->assign_block_vars('block.item.name', array(
		'CLASS' => 'bold',
		'URL' => s_link('a', $row['subdomain']),
		'TEXT' => $row['name'])
	);
}

//
// USERS
//
$sql = "SELECT user_id, username, username_base, user_color, user_website 
	FROM _members
	WHERE user_website <> '' 
	ORDER BY username";
$members = sql_rowset($sql);

foreach ($members as $i => $row) {
	if (!$i) {
		$template->assign_block_vars('block', array(
			'LANG' => $user->lang['USERS'])
		);
	}
	
	$template->assign_block_vars('block.item', array(
		'TEXT' => $row['user_website'],
		'U_GOTO' => s_link('bounce', array('u', $row['user_id'])))
	);
	
	$template->assign_block_vars('block.item.name', array(
		'COLOR' => $row['user_color'],
		'URL' => s_link('m', $row['username_base']),
		'TEXT' => $row['username'])
	);
}

page_layout('LINKS_FRIENDS', 'bounce_body');

?>