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

_auth('all');

$i_size = intval(ini_get('upload_max_filesize'));
$i_size *= 1048576;
$error = array();

if ($submit)
{
	require('./interfase/upload.php');
	$upload = new upload();
	
	$news_id = request_var('news_id', 0);
	$filepath_1 = '..' . SDATA . 'news/';
	$f = $upload->process($filepath_1, $_FILES['add_image'], array('jpg', 'jpeg'), $i_size);
	
	if (!sizeof($upload->error) && $f !== false)
	{
		foreach ($f as $row)
		{
			$xa = $upload->resize($row, $filepath_1, $filepath_1, $news_id, array(100, 75), false, false, true);
		}
		
		redirect(s_link());
	}
	else
	{
		$template->assign_block_vars('error', array(
			'MESSAGE' => parse_error($upload->error))
		);
	}
}

$sql = 'SELECT *
	FROM _news
	ORDER BY post_time DESC';
$result = sql_rowset($sql);

foreach ($result as $row) {
	$template->assign_block_vars('news_list', array(
		'NEWS_ID' => $row['news_id'],
		'NEWS_TITLE' => $row['post_subject'])
	);
}

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size
);
page_layout('NEWS IMAGES UPLOADER', 'news_images_body', $template_vars, false);

?>
