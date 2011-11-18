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

require_once(ROOT . 'interfase/facebook.php');

$user->init(false, true);

$d = decode_ht('.htf_');

$fbd = new stdClass();
foreach (w('page appid secret token') as $i => $k)
{
	$fbd->$k = _decode($d[$i]);
}
unset($d);

$facebook_page = '48722647107';

$facebook = new Facebook(array(
	'appId'  => $fbd->appid,
	'secret' => $fbd->secret)
);

foreach (w('at') as $i => $k)
{
	$htk[$k] = _decode($d[$i]);
}

$attr = array(
	'access_token' => $fbd->token
);

$likes = (object) $facebook->api($facebook_page);

if (isset($likes->likes)) {
	$cache->save('fb_likes', $likes->likes);
}

$wall = $facebook->api($facebook_page . '/feed/', $attr);

$official_posts = array();
foreach ($wall['data'] as $row) {
	if ($row['from']['id'] != $facebook_page) {
		continue;
	}
	
	$created_time = strtotime($row['created_time']);
	
	/*$sql_insert = array(
		'' => '',
	);
	$sql = 'INSERT INTO _news' . sql_build('INSERT', $sql_insert);
	sql_query($sql);
	*/
	
	
	
	//echo $row['created_time'] . ' ---- ' . $created_time . '<br>';
	
	$official_posts[] = $row;
}

_pre($likes);
_pre($official_posts, true);

?>