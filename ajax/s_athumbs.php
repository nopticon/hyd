<?php
// -------------------------------------------------------------
// $Id: s_athumbs.php,v 1.1 2006/03/23 00:04:37 Psychopsia Exp $
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

$sql = 'SELECT *
	FROM _artists
	WHERE images > 0
	ORDER BY RAND() LIMIT 12';
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$selected_artists = array();
	do
	{
		$selected_artists[$row['ub']] = $row;
	}
	while ($row = $db->sql_fetchrow($result));
	$db->sql_freeresult($result);

	$sql = 'SELECT *
		FROM _artists_images
		WHERE ub IN (' . implode(',', array_keys($selected_artists)) . ')
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
	
	$return_string = '<table width="100%" cellpadding="10" class="t-collapse">';
	
	$tcol = 0;
	foreach ($selected_artists as $ub => $data)
	{
		if (!$tcol)
		{
			$return_string .= '<tr>';
		}
		
		$return_string .= '<td align="center" valign="bottom"><a href="' . s_link('a', $data['subdomain']) . '"><img class="box" src="/data/artists/' . (($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif') . '" alt="' . $data['genre'] . '" /></a><br /><div class="sep2-top"><a class="bold" href="' . s_link('a', $data['subdomain']) . '">' . $data['name'] . '</a></div><div><small>' . (($data['local']) ? 'Guatemala' : $data['location']) . '</small></div></td>';
		$tcol = ($tcol == 3) ? 0 : $tcol + 1;
		
		if (!$tcol)
		{
			$return_string .= '</tr>';
		}
	}
	
	$return_string .= '</table>';
	
	echo ($return_string);
}

?>