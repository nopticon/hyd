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

require_once(ROOT . 'interfase/upload.php');
require_once(ROOT . 'interfase/zip.php');

class __event_images extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache;
		
		if ($this->submit) {
			$upload = new upload();
			
			$event_id = request_var('event_id', 0);
			
			$filepath_1 = '..' . SDATA . 'tmp/';
			$filepath_2 = '..' . SDATA . 'events/gallery/';
			$filepath_3 = $filepath_1 . $event_id . '/';
			$filepath_4 = $filepath_3 . 'thumbnails/';
			
			$f = $upload->process($filepath_1, $_FILES['add_zip'], array('zip'));
			if (!sizeof($upload->error) && $f !== false)
			{
				@set_time_limit(0);
				
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
						echo $user->lang['DIS_INVALID'];
						
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
						$sql = 'INSERT INTO _events_images' . sql_build('INSERT', $insert);
						sql_query($sql);
						
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
							$sql = 'UPDATE _events_images SET image_footer = ?
								WHERE event_id = ?
									AND image = ?';
							sql_query(sql_filter($sql, htmlencode($part[1]), $event_id, $i));
						}
					}
					
					@unlink($filepath_3 . $footer_data);
				}
				
				$sql = 'SELECT *
					FROM _events_colab
					WHERE colab_event = ?
						AND colab_uid = ?';
				if (!$row = sql_fieldrow(sql_filter($sql, $event_ud, $user->d('user_id'))))
				{
					$sql_insert = array(
						'colab_event' => $event_id,
						'colab_uid' => $user->d('user_id')
					);
					$sql = 'INSERT INTO _events_colab' . sql_build('INSERT', $sql_insert);
					sql_query($sql);
				}
				
				$sql = 'UPDATE _events SET images = images + ??
					WHERE id = ?';
				sql_query(sql_filter($sql, $count_images, $event_id));
				
				$ftp->ftp_rename($ftp->dfolder() . 'data/tmp/' . $event_id . '/', $ftp->dfolder() . 'data/events/gallery/' . $event_id . '/');
				//@rename($filepath_3, $filepath_2 . $event_id);
				$ftp->ftp_quit();
				
				redirect(s_link('events', $event_id));
			} else {
				_style('error', array(
					'MESSAGE' => parse_error($upload->error))
				);
			}
		}
		
		$sql = 'SELECT *
			FROM _events
			WHERE date < ??
			ORDER BY date DESC';
		$result = sql_rowset(sql_filter($sql, (time() + 86400)));
		
		foreach ($result as $row) {
			_style('event_list', array(
				'EVENT_ID' => $row['id'],
				'EVENT_TITLE' => (($row['images']) ? '* ' : '') . $row['title'],
				'EVENT_DATE' => $user->format_date($row['date']))
			);
		}
		
		return;
	}
}

function a_mkdir($path, $folder) {
	global $ftp;
	
	$result = false;
	if (!empty($path)) {
		$ftp->ftp_chdir($path);
	}
	
	if ($ftp->ftp_mkdir($folder)) {
		if ($ftp->ftp_site('CHMOD 0777 ' . $folder)) {
			$result = folder;
		}
	} else {
		_die('Can not create: ' . $folder);
	}
	
	return $result;
}

?>