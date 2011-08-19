<?php
// -------------------------------------------------------------
// $Id: _acp.xevent.php,v 1.0 2007/06/10 20:49:00 Psychopsia Exp $
//
// STARTED   : Sun Jun 10, 2007
// COPYRIGHT : � 2007 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$i_size = intval(ini_get('upload_max_filesize'));
$i_size *= 1048576;

if ($submit)
{
	require('./interfase/upload.php');
	$upload = new upload();
	
	$event_id = request_var('event_id', 0);
	
	$filepath_1 = '..' . SDATA . 'tmp/';
	$filepath_2 = '..' . SDATA . 'events/gallery/';
	$filepath_3 = $filepath_1 . $event_id . '/';
	$filepath_4 = $filepath_3 . 'thumbnails/';
	
	$f = $upload->process($filepath_1, $_FILES['add_zip'], array('zip'), $i_size);
	if (!sizeof($upload->error) && $f !== false)
	{
		@set_time_limit(0);
		
		require('./interfase/f_zip.php');
		require('./interfase/ftp.php');
		$ftp = new ftp();
		
		if (!$ftp->ftp_connect())
		{
			_die('Can not connnect');
		}
		
		if (!$ftp->ftp_login())
		{
			$ftp->ftp_quit();
			_die('Can not login');
		}
		
		// /httpdocs/data/tmp/
		
		foreach ($f as $row)
		{
			$zip_folder = unzip($filepath_1 . $row['filename'], $filepath_3, true);
			@unlink($filepath_1 . $row['filename']);
		}
		
		if (!empty($zip_folder))
		{
			$zip_folder = substr($zip_folder, 0, -1);
			
			$fp = @opendir($filepath_3 . $zip_folder);
			while ($file = @readdir($fp))
			{
				if ($file != '.' && $file != '..')
				{
					$ftp->ftp_rename($ftp->dfolder() . 'data/tmp/' . $event_id . '/' . $zip_folder . '/' . $file, $ftp->dfolder() . 'data/tmp/' . $event_id . '/' . $file);
					//@rename($filepath_3 . $zip_folder . '/' . $file, $filepath_3 . $file);
				}
			}
			@closedir($fp);
			
			@unlink($filepath_3 . $zip_folder);
		}
		
		if (!@file_exists($filepath_4))
		{
			a_mkdir($ftp->dfolder() . 'data/tmp/' . $event_id, 'thumbnails');
		}
		
		$footer_data = '';
		$filerow_list = array();
		$count_images = $img = $event_pre = 0;
		
		$check_is = array();
		if (@file_exists($filepath_2 . $event_id))
		{
			$fp = @opendir($filepath_2 . $event_id);
			while ($filerow = @readdir($fp))
			{
				if (preg_match('#([0-9]+)\.(jpg)#is', $filerow))
				{
					$dis = getimagesize($filepath_2 . $event_id . $filerow);
					$disd = intval(_decode('4e6a4177'));
					if (($dis[0] > $dis[1] && $dis[0] < $disd) || ($dis[1] > $dis[0] && $dis[1] < $disd))
					{
						$check_is[] = $filerow;
						continue;
					}
					
					$event_pre++;
				}
			}
			@closedir($fp);
			
			if (count($check_is))
			{
				echo $user->lang['DIS'.'_INV'.'ALID'];
				
				foreach ($check_is as $row)
				{
					echo $row . '<br />';
				}
				die();
			}
			
			$img = $event_pre;
		}
		
		$fp = @opendir($filepath_3);
		while ($filerow = @readdir($fp))
		{
			$filerow_list[] = $filerow;
		}
		@closedir($fp);
		
		if (count($filerow_list) > 100)
		{
			
		}
		
		array_multisort($filerow_list, SORT_ASC, SORT_NUMERIC);
		
		foreach ($filerow_list as $filerow)
		{
			if (preg_match('#([0-9]+)\.(jpg)#is', $filerow))
			{
				$row = $upload->_row($filepath_3, $filerow);
				if (!@copy($filepath_3 . $filerow, $row['filepath']))
				{
					continue;
				}
				
				$img++;
				$xa = $upload->resize($row, $filepath_3, $filepath_3, $img, array(600, 450), false, true, true, 'w2');
				if ($xa === false)
				{
					continue;
				}
				$xb = $upload->resize($row, $filepath_3, $filepath_4, $img, array(100, 75), false, false);
				
				$insert = array(
					'event_id' => (int) $event_id,
					'image' => (int) $img,
					'width' => (int) $xa['width'],
					'height' => (int) $xa['height'],
					'allow_dl' => 1
				);
				$sql = 'INSERT INTO _events_images' . $db->sql_build_array('INSERT', $insert);
				$db->sql_query($sql);
				
				$count_images++;
			}
			elseif (preg_match('#(info)\.(txt)#is', $filerow))
			{
				$footer_data = $filerow;
			}
		}
		
		if (!empty($footer_data) && @file_exists($filepath_3 . $footer_data))
		{
			$footer_info = @file($filepath_3 . $footer_data);
			foreach ($footer_info as $linerow)
			{
				$part = explode(':', $linerow);
				$part = array_map('trim', $part);
				
				$numbs = explode('-', $part[0]);
				$numbs[1] = (isset($numbs[1])) ? $numbs[1] : $numbs[0];
				
				for ($i = ($numbs[0] + $event_pre), $end = ($numbs[1] + $event_pre + 1); $i < $end; $i++)
				{
					$sql = "UPDATE _events_images SET image_footer = '" . $db->sql_escape(htmlencode($part[1])) . "'
						WHERE event_id = " . (int) $event_id . ' AND image = ' . (int) $i;
					$db->sql_query($sql);
				}
			}
			
			@unlink($filepath_3 . $footer_data);
		}
		
		$sql = 'SELECT *
			FROM _events_colab
			WHERE colab_event = ' . (int) $event_id . '
				AND colab_uid = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if (!$row = $db->sql_fetchrow($result))
		{
			$sql = 'INSERT INTO _events_colab (colab_event, colab_uid)
				VALUES (' . (int) $event_id . ', ' . (int) $user->data['user_id'] . ')';
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);
		
		$sql = 'UPDATE _events SET images = images + ' . (int) $count_images . '
			WHERE id = ' . (int) $event_id;
		$db->sql_query($sql);
		
		$ftp->ftp_rename($ftp->dfolder() . 'data/tmp/' . $event_id . '/', $ftp->dfolder() . 'data/events/gallery/' . $event_id . '/');
		//@rename($filepath_3, $filepath_2 . $event_id);
		$ftp->ftp_quit();
		
		redirect(s_link('events', $event_id));
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
	WHERE date < ' . (time() + 86400) . '
	ORDER BY date DESC';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$template->assign_block_vars('event_list', array(
		'EVENT_ID' => $row['id'],
		'EVENT_TITLE' => (($row['images']) ? '* ' : '') . $row['title'],
		'EVENT_DATE' => $user->format_date($row['date']))
	);
}
$db->sql_freeresult($result);

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size
);
page_layout('EVENTS', 'acp/event_images', $template_vars, false);

function a_mkdir($path, $folder)
{
	global $ftp;
	
	$result = false;
	if (!empty($path))
	{
		$ftp->ftp_chdir($path);
	}
	
	if ($ftp->ftp_mkdir($folder))
	{
		if ($ftp->ftp_site('CHMOD 0777 ' . $folder))
		{
			$result = folder;
		}
	}
	else
	{
		_die('Can not create: ' . $folder);
	}
	
	return $result;
}

?>