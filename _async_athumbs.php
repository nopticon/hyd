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
require_once(ROOT . 'interfase/artists.php');

$artists = new _artists();

$sql = 'SELECT *
	FROM _artists
	WHERE images > 0
	ORDER BY RAND()
	LIMIT 12';
if (!$selected_artists = sql_rowset($sql, 'ub')) {
	return;
}

$sql = 'SELECT *
	FROM _artists_images
	WHERE ub IN (??)
	ORDER BY RAND()';
$result = sql_rowset(sql_filter($sql, implode(',', array_keys($selected_artists))));

$random_images = array();
foreach ($result as $row) {
	if (!isset($random_images[$row['ub']])) {
		$random_images[$row['ub']] = $row['image'];
	}
}

$return_string = '<table width="100%" cellpadding="10" class="t-collapse">';

$tcol = 0;
foreach ($selected_artists as $ub => $data) {
	if (!$tcol) {
		$return_string .= '<tr>';
	}
	
	$return_string .= '<td align="center" valign="bottom"><a class="relevant_artist" href="' . s_link('a', $data['subdomain']) . '"><img class="box" src="/data/artists/' . (($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif') . '" alt="' . $data['genre'] . '" /></a><br /><div class="sep2-top"><a class="bold" href="' . s_link('a', $data['subdomain']) . '">' . $data['name'] . '</a></div><div><small>' . (($data['local']) ? 'Guatemala' : $data['location']) . '</small></div></td>';
	$tcol = ($tcol == 3) ? 0 : $tcol + 1;
	
	if (!$tcol) {
		$return_string .= '</tr>';
	}
}

$return_string .= '</table>';

echo ($return_string);

?>