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
define('NO_A_META', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$smilies = array();
if (!$smilies = $cache->get('smilies')) {
	$sql = 'SELECT *
		FROM _smilies
		ORDER BY LENGTH(code) DESC';
	if ($smilies = sql_rowset($sql)) {
		$cache->save('smilies', $smilies);
	}
}

foreach ($smilies as $smile_url => $data) {
	$template->assign_block_vars('smilies_row', array(
		'CODE' => $data['code'],
		'IMAGE' => $config['smilies_path'] . '/' . $data['smile_url'],
		'DESC' => $data['emoticon'])
	);
}

page_layout('EMOTICONS', 'emoticons_body');

?>