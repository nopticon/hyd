<?php
// -------------------------------------------------------------
// $Id: hidden_eu.php,v 1.0 2006/05/24 00:00:00 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

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
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('news_list', array(
		'NEWS_ID' => $row['news_id'],
		'NEWS_TITLE' => $row['post_subject'])
	);
}
$db->sql_freeresult($result);

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size
);
page_layout('NEWS IMAGES UPLOADER', 'hidden_ni_body', $template_vars, false);

?>
