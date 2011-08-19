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