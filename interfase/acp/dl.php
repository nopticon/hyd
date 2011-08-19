<?php
// -------------------------------------------------------------
// $Id: hidden_eu.php,v 1.0 2006/05/24 00:00:00 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$qqq = set_time_limit(0);

$i_size = intval(ini_get('upload_max_filesize'));
$i_size *= 1048576;
$error = array();

if ($submit)
{
	require('./interfase/upload.php');
	$upload = new upload();
	
	$a_id = request_var('artist', 0);
	$filepath = '..' . SDATA . 'artists/' . $a_id . '/';
	$filepath_1 = $filepath . 'media/';
	
	$f = $upload->process($filepath_1, $_FILES['add_dl'], array('mp3'), $i_size);
	
	if (!sizeof($upload->error) && $f !== false)
	{
		require('./interfase/id3/getid3/getid3.php');
		$getID3 = new getID3;
		
		$sql = 'SELECT MAX(id) AS total
			FROM _dl';
		$result = $db->sql_query($sql);
		
		$a = 0;
		if ($row = $db->sql_fetchrow($result))
		{
			$a = $row['total'];
		}
		$db->sql_freeresult($result);
		
		$proc = 0;
		foreach ($f as $row)
		{
			$a++;
			$proc++;
			
			$filename = $upload->rename($row, $a);
			$tags = $getID3->analyze($filename);
			
			$clean = array('title', 'genre', 'album', 'year');
			foreach ($clean as $i)
			{
				${'clean_' . $i} = (isset($tags['tags']['id3v1'][$i][0])) ? htmlencode($tags['tags']['id3v1'][$i][0]) : '';
			}
			
			$clean_album = ($clean_album != '') ? $clean_album : 'Single';
			$clean_genre = ($clean_genre != '') ? $clean_genre : '-';
			$clean_year = ($clean_year != '') ? $clean_year : '-';
			
			$insert_dl = array(
				'ud' => 1,
				'ub' => $a_id,
				'title' => $clean_title,
				'views' => 0,
				'downloads' => 0,
				'votes' => 0,
				'posts' => 0,
				'date' => time(),
				'filesize' => @filesize($filename),
				'duration' => $tags['playtime_string'],
				'genre' => $clean_genre,
				'album' => $clean_album,
				'year' => $clean_year
			);
			$db->sql_query('INSERT INTO _dl' . $db->sql_build_array('INSERT', $insert_dl));
			$dl_id = $db->sql_nextid();
			
			// Alice notify
			$sql = 'SELECT *
				FROM _forum_posts
				WHERE post_id = 125750';
			$result = $db->sql_query($sql);
			
			$topic_id = 0;
			if ($row3 = $db->sql_fetchrow($result))
			{
				$sql = 'SELECT name, subdomain
					FROM _artists
					WHERE ub = ' . (int) $a_id;
				$result2 = $db->sql_query($sql);
				
				$a_name = '';
				$a_subd = '';
				if ($row2 = $db->sql_fetchrow($result2))
				{
					$a_name = $row2['name'];
					$a_subd = $row2['subdomain'];
				}
				$db->sql_freeresult($result2);
				
				$a_intro = 'En esta secci&oacute;n encontrar&aacute;s la actualizaci&oacute;n de las &uacute;ltimas descargas agregadas a Rock Republik.' . "\n\n";
				$a_format = "[sb] <strong> %s </strong>\n%s (%s)\n\n%s\n%s\n\nEnlace para escuchar:\n%s [/sb]";
				$a_location = ($local) ? ((($location != '') ? $location . ', ' : '') . 'Guatemala') : $location;
				$a_link = 'http://www.rockrepublik.net/a/' . $a_subd . '/9/' . $dl_id . '/';
				
				$a_data = sprintf($a_format, $a_name, ':d' . $dl_id . ':', $tags['playtime_string'], $clean_album, $clean_genre, $a_link);
				$row3['post_text'] = str_replace("\r", '', $row3['post_text']);
				$a_post = $a_intro . $a_data . str_replace($a_intro, '', $row3['post_text']);
				
				$sql = "UPDATE _forum_posts
					SET post_text = '" . $db->sql_escape($a_post) . "', post_time = " . time() . "
					WHERE post_id = " . (int) $row3['post_id'];
				$db->sql_query($sql);
				
				$sql = 'UPDATE _forum_topics
					SET topic_time = ' . time() . '
					WHERE topic_id = ' . (int) $row3['topic_id'];
				$db->sql_query($sql);
				
				$user->save_unread(UH_T, $row3['topic_id']);
				$topic_id = $row3['topic_id'];
			}
			$db->sql_freeresult($result);
		}
		
		$sql = 'UPDATE _artists SET um = um + ' . (int) $proc . '
			WHERE ub = ' . (int) $a_id;
		$db->sql_query($sql);
		
		$cache->delete('downloads_list');
		redirect(s_link('topic', $topic_id));
	}
	else
	{
		$template->assign_block_vars('error', array(
			'MESSAGE' => parse_error($upload->error))
		);
	}
}

$select_a = '';
$sql = 'SELECT *
	FROM _artists
	ORDER BY name';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$select_a .= '<option value="' . $row['ub'] . '">' . $row['name'] . '</option>';
}
$db->sql_freeresult($result);

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size,
	'MAX_FILESIZE2' => ($i_size / 1024 / 1024),
	'SELECT_A' => $select_a
);

page_layout('DL', 'acp/a_download', $template_vars, false);

?>