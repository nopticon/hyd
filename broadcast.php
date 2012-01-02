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

header('Content-type: text/html; charset=utf-8');

$user->init();
$user->setup();

$offset = request_var('offset', 0);
$category = request_var('category', '');

if (!empty($category)) {
	$sql = 'SELECT *
		FROM _terms
		WHERE slug = ?';
	if (!$term_category = sql_fieldrow(sql_filter($sql, $category))) {
		fatal_error();
	}
	
	$sql = 'SELECT *
		FROM _posts p, _postmeta pm, _terms t, _term_relationships rs
		WHERE t.slug = ?
			AND p.post_status = ?
			AND p.ID = pm.post_id
			AND pm.meta_key = ?
			AND rs.object_id = p.ID
			AND rs.term_taxonomy_id = t.term_id
		ORDER BY p.post_date DESC
		LIMIT ??, ??';
	$podcast = sql_rowset(sql_filter($sql, $category, 'publish', '_podPressMedia', $offset, 25));
	
	foreach ($podcast as $i => $row) {
		if (!$i) $template->assign_block_vars('podcast', array());
		
		$dmedia = array_key(unserialize($row['meta_value']), 0);
		
		$title = htmlentities(utf8_encode($row['post_title']), ENT_COMPAT, 'utf-8');
		$artist = htmlentities(utf8_encode($row['name']), ENT_COMPAT, 'utf-8');
		
		$template->assign_block_vars('podcast.row', array(
			'MP3' => $dmedia['URI'],
			'OGG' => '',
			'TITLE' => $title,
			'ARTIST' => $artist,
			'COVER' => $row['slug'],
			'DURATION' => $dmedia['duration'])
		);
	}
	
	$str = utf8_encode($term_category['name']);
	$str = htmlentities($str, ENT_COMPAT, 'utf-8');
	
	page_layout($str, 'broadcast_play');
}

$programs = array('supernova', 'invasionrock', 'antifm', 'metalebrios', 'themetalroom');

foreach ($programs as $i => $row) {
	if (!$i) $template->assign_block_vars('programs', array());
	
	$template->assign_block_vars('programs.row', array(
		'IMAGE' => $row,
		'URL' => s_link('broadcast', $row))
	);
}

$sql = 'SELECT *
	FROM _posts p
	INNER JOIN _term_relationships tr ON tr.object_id = p.ID
	INNER JOIN _term_taxonomy tx ON tr.term_taxonomy_id = tx.term_taxonomy_id
	INNER JOIN _terms t ON tx.term_id = t.term_id
	WHERE post_status = ?
	ORDER BY post_date DESC
	LIMIT ??, ??';
$podcast = sql_rowset(sql_filter($sql, 'publish', $offset, 10));

foreach ($podcast as $i => $row) {
	if (!$i) $template->assign_block_vars('podcast', array());
	
	$title = htmlentities(utf8_encode($row['post_title']), ENT_COMPAT, 'utf-8');
	
	$template->assign_block_vars('podcast.row', array(
		'POST_DATE' => $row['post_date'],
		'POST_URL' => s_link('broadcast', $row['slug']),
		'POST_CONTENT' => $row['post_content'],
		'POST_TITLE' => $title)
	);
}

page_layout('BROADCAST', 'broadcast');

?>