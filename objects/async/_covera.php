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
if (!defined('IN_APP')) exit;

$sql = 'SELECT ub, name, subdomain, location, genre, image
	FROM _artists a
	INNER JOIN _artists_images i ON a.ub = i.ub
	WHERE a.a_active = 1
		AND i.image_default = 1
	ORDER BY RAND()
	LIMIT 4';
$artists = sql_rowset($sql);

foreach ($artists as $i => $row) {
	if (!$i) _style('thumbnails');

	_style('thumbnails.item', array(
		'NAME' => $row->name,
		'IMAGE' => $config->artists_url . $row->ub . '/thumbnails/' . $row->image . '.jpg',
		'URL' => s_link('a', $row->subdomain),
		'LOCATION' => ($row->local) ? 'Guatemala' : $row->location,
		'GENRE' => $row->genre)
	);
}

// require_once(ROOT . 'interfase/artists.php');

/*$artists = new artists();
$artists->get_data();

$a_ary = w();
for ($i = 0; $i < 4; $i++) {
	$_a = array_rand($artists->adata);
	if (!$artists->adata[$_a]->images || isset($a_ary[$_a])) {
		$i--;
		continue;
	}
	$a_ary[$_a] = $artists->adata[$_a];
}

if (count($a_ary)) {
	$sql = 'SELECT *
		FROM _artists_images
		WHERE ub IN (??)
		ORDER BY RAND()';
	$result = sql_rowset(sql_filter($sql, implode(',', array_keys($a_ary))));
	
	$random_images = w();
	foreach ($result as $row) {
		if (!isset($random_images[$row->ub])) {
			$random_images[$row->ub] = $row->image;
		}
	}
	
	$i = 0;
	$html_format = '<td class="%s"><a href="%s">%s</a><br/ ><small>%s</small><br /><div><a href="%s"><img src="' . $config->artists_assets . '%s/thumbnails/%s.jpg" title="%s" /></a></div></td>';

	$return_string = '';
	foreach ($a_ary as $ub => $data) {
		$i++;

		$url = s_link('a', $data['subdomain']);
		$return_string .= sprintf($html_format, (($i % 2) ? 'dark-color' : ''), $url, $data->name, (($data->local) ? 'Guatemala' : $data->location), $url, $ub, $random_images[$ub], $data->genre);
	}
	
	echo rawurlencode('<table width="100%" class="t-collapse"><tr>' .  $return_string. '</tr></table>');
}*/