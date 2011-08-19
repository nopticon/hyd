<?php
// -------------------------------------------------------------
// $Id: event_update.php,v 1.0 2008/10/14 20:48:00 Psychopsia Exp $
//
// STARTED   : Tue Oct 14, 2008
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
	
	$event_id = request_var('event_id', 0);
	$filepath_1 = '..' . SDATA . 'events/future/';
	$filepath_2 = '..' . SDATA . 'events/future/thumbnails/';
	
	$f = $upload->process($filepath_1, $_FILES['add_image'], array('jpg', 'jpeg'), $i_size);
	
	if (!sizeof($upload->error) && $f !== false)
	{
		foreach ($f as $row)
		{
			$xa = $upload->resize($row, $filepath_1, $filepath_1, $event_id, array(600, 400), false, false, true);
			if ($xa === false)
			{
				continue;
			}
			$xb = $upload->resize($row, $filepath_1, $filepath_2, $event_id, array(100, 75), false, false);
		}
		
		redirect(s_link('events') . '#' . $event_id);
	}
	else
	{
		$template->assign_block_vars('error', array(
			'MESSAGE' => parse_error($upload->error))
		);
	}
}

$sql = 'SELECT *
	FROM _events
	WHERE date > ' . time() . '
	ORDER BY date DESC';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('event_list', array(
		'EVENT_ID' => $row['id'],
		'EVENT_TITLE' => $row['title'],
		'EVENT_DATE' => $user->format_date($row['date']))
	);
}
$db->sql_freeresult($result);

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size
);
page_layout('EVENTS', 'acp/event_update', $template_vars, false);

?>