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
$user->setup();

$offset = request_var('offset', 0);
$category = request_var('category', '');

if (!empty($category)) {
	$sql = 'SELECT *
		FROM _terms
		WHERE slug = ?';
	if (!$term_category = sql_fieldrowo(sql_filter($sql, $category))) {
		fatal_error();
	}
	
	$sql = 'SELECT *
		FROM _posts p, _terms t, _term_relationships rs
		WHERE t.slug = ?
			AND p.post_status = ?
			AND rs.object_id = p.ID
			AND rs.term_taxonomy_id = t.term_id
		ORDER BY p.post_date DESC
		LIMIT ??, ??';
	$podcast = sql_rowset(sql_filter($sql, $category, 'publish', $offset, 25));
} else {
	$sql = 'SELECT *
		FROM _posts
		WHERE post_status = ?
		ORDER BY post_date DESC
		LIMIT ??, ??';
	$podcast = sql_rowset(sql_filter($sql, 'publish', $offset, 25));
}

foreach ($podcast as $i => $row) {
	if (!$i) $template->assign_block_vars('podcast', array());
	
	$template->assign_block_vars('podcast.row', array(
		'POST_DATE' => $row['post_date'],
		'POST_CONTENT' => $row['post_content'],
		'POST_TITLE' => $row['post_title'])
	);
}

$template_vars = array();

page_layout('BROADCAST', 'broadcast', $template_vars, false);

?>