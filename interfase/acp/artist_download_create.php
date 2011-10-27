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
if (!defined('IN_NUCLEO')) exit;

_auth('founder');

$qqq = set_time_limit(0);

$i_size = intval(ini_get('upload_max_filesize'));
$i_size *= 1048576;
$error = array();

if ($submit)
{
	require_once(ROOT . 'interfase/upload.php');
	$upload = new upload();
	
	$a_id = request_var('artist', 0);
	$filepath = '..' . SDATA . 'artists/' . $a_id . '/';
	$filepath_1 = $filepath . 'media/';
	
	$f = $upload->process($filepath_1, $_FILES['add_dl'], array('mp3'), $i_size);
	
	if (!sizeof($upload->error) && $f !== false)
	{
		require_once(ROOT . 'interfase/id3/getid3/getid3.php');
		$getID3 = new getID3;
		
		$sql = 'SELECT MAX(id) AS total
			FROM _dl';
		$a = sql_field($sql, 'total', 0);
		
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
			$sql = 'INSERT INTO _dl' . sql_build('INSERT', $insert_dl);
			$dl_id = sql_query_nextid();
			
			// Alice notify
			$topic_id = 0;
			
			$sql = 'SELECT *
				FROM _forum_posts
				WHERE post_id = 125750';
			if ($row3 = sql_fieldrow($sql)) {
				$a_name = '';
				$a_subd = '';
				
				$sql = 'SELECT name, subdomain
					FROM _artists
					WHERE ub = ?';
				if ($row2 = sql_fieldrow(sql_filter($sql, $a_id))) {
					$a_name = $row2['name'];
					$a_subd = $row2['subdomain'];
				}
				
				$a_intro = 'En esta secci&oacute;n encontrar&aacute;s la actualizaci&oacute;n de las &uacute;ltimas descargas agregadas a Rock Republik.' . "\n\n";
				$a_format = "[sb] <strong> %s </strong>\n%s (%s)\n\n%s\n%s\n\nEnlace para escuchar:\n%s [/sb]";
				$a_location = ($local) ? ((($location != '') ? $location . ', ' : '') . 'Guatemala') : $location;
				$a_link = 'http://www.rockrepublik.net/a/' . $a_subd . '/9/' . $dl_id . '/';
				
				$a_data = sprintf($a_format, $a_name, ':d' . $dl_id . ':', $tags['playtime_string'], $clean_album, $clean_genre, $a_link);
				$row3['post_text'] = str_replace("\r", '', $row3['post_text']);
				$a_post = $a_intro . $a_data . str_replace($a_intro, '', $row3['post_text']);
				
				$sql = 'UPDATE _forum_posts SET post_text = ?, post_time = ?
					WHERE post_id = ?';
				sql_query(sql_filter($sql, $a_post, time(), $row3['post_id']));
				
				$sql = 'UPDATE _forum_topics SET topic_time = ?
					WHERE topic_id = ?';
				sql_query(sql_filter($sql, time(), $row3['topic_id']));
				
				$user->save_unread(UH_T, $row3['topic_id']);
				$topic_id = $row3['topic_id'];
			}
		}
		
		$sql = 'UPDATE _artists SET um = um + ??
			WHERE ub = ?';
		sql_query(sql_filter($sql, $proc, $a_id));
		
		$cache->delete('downloads_list');
		redirect(s_link('topic', $topic_id));
	} else {
		$template->assign_block_vars('error', array(
			'MESSAGE' => parse_error($upload->error))
		);
	}
}

$select_a = '';
$sql = 'SELECT *
	FROM _artists
	ORDER BY name';
$result = sql_rowset($sql);

foreach ($result as $row) {
	$select_a .= '<option value="' . $row['ub'] . '">' . $row['name'] . '</option>';
}

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size,
	'MAX_FILESIZE2' => ($i_size / 1024 / 1024),
	'SELECT_A' => $select_a
);

page_layout('DL', 'acp/a_download', $template_vars, false);

?>