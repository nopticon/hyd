<?php
// -------------------------------------------------------------
// $Id: s_covera.php,v 1.1 2006/03/23 00:04:37 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
define('ROOT', './../');
require('./../interfase/common.php');
require('./../interfase/artists.php');

$user->init(false);

$artists = new _artists();

$artists->get_data();

$a_ary = array();
for ($i = 0; $i < 4; $i++)
{
	$_a = array_rand($artists->adata);
	if (!$artists->adata[$_a]['images'] || isset($a_ary[$_a]))
	{
		$i--;
		continue;
	}
	$a_ary[$_a] = $artists->adata[$_a];
}

if (sizeof($a_ary))
{
	$sql = 'SELECT *
		FROM _artists_images
		WHERE ub IN (' . implode(',', array_keys($a_ary)) . ')
		ORDER BY RAND()';
	$result = $db->sql_query($sql);
	
	$random_images = array();
	while ($row = $db->sql_fetchrow($result))
	{
		if (!isset($random_images[$row['ub']]))
		{
			$random_images[$row['ub']] = $row['image'];
		}
	}
	$db->sql_freeresult($result);
	
	$return_string = '<table width="100%" class="t-collapse"><tr>';
	
	$i = 0;
	foreach ($a_ary as $ub => $data)
	{
		$url = s_link('a', $data['subdomain']);
		$return_string .= '<td class="' . (($i % 2) ? 'dark-color' : '') . ' pad6"><a class="orange bold" href="' . $url . '">' . $data['name'] . '</a><br/ ><small>' . (($data['local']) ? 'Guatemala' : $data['location']) . '</small><br /><div class="sep2-top"><a href="' . $url . '"><img class="box" src="/data/artists/' . $ub . '/thumbnails/' . $random_images[$ub] . '.jpg" title="' . $data['genre'] . '" /></a></div></td>';
		$i++;
	}
	
	$return_string .= '</tr></table>';
	echo rawurlencode($return_string);
}

?>