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

class xavatar
{
	var $filename_new;
	var $filename_old;
	var $image;
	var $info;
	var $config = array();
	
	function xavatar($field)
	{
		global $config;
		
		$this->config = array(
			'filesize' => $config['avatar_filesize'] / 1024,
			'filesize_real' => $config['avatar_filesize'],
			'ext' => array('jpg', 'jpeg', 'gif'),
			'dim' => array(70, 70)
		);
		
		return;
	}
	
	function get_extension($filename)
	{
		return strtolower(str_replace('.', '', substr($filename, strrpos($filename, '.'))));
	}
	
	function process()
	{
		global $user, $config, $error;
		
		$this->info = $_FILES['avatar'];
		
		if (empty($this->info['name']) || !$this->info['name'] || ($this->info['name'] == 'none'))
		{
			return;
		}
		
		if ($this->info['error'])
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			
			return;
		}
		
		if ($this->info['size'] > $this->config['filesize_real'])
		{
			$error[] = sprintf($user->lang['AVATAR_FILESIZE'], $this->config['filesize']);
			return;
		}
		
		if (!@is_uploaded_file($this->info['tmp_name']))
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			return;
		}
		
		//
		// Get filename extension
		//
		$this->info['ext'] = $this->get_extension($this->info['name']);
		
		if (!$this->info['ext'] || !in_array($this->info['ext'], $this->config['ext']))
		{
			$error[] = 'AVATAR_FILETYPE';
			return;
		}
		
		$this->info['temp_avatar'] = '..' . $config['avatar_path'] . '/' . md5(unique_id()) . '.' . $this->info['ext'];
		$this->info['temp_avatar2'] = '..' . $config['avatar_path'] . '/' . md5(unique_id()) . '.' . $this->info['ext'];
		$this->info['just_file'] = $user->data['username_base'] . '.' . $this->info['ext'];
		$this->info['new_avatar'] = '..' . $config['avatar_path'] . '/' . $this->info['just_file'];
		$this->info['current_avatar'] = '..' . $config['avatar_path'] . '/' . $user->data['user_avatar'];
		
		if (!@move_uploaded_file($this->info['tmp_name'], $this->info['temp_avatar']))
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			return;
		}
		
		list($width, $height, $type, $void) = getimagesize($this->info['temp_avatar']);
		
		if ($width < 1 && $height < 1)
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			return;
		}
		
		$data = array(
			'width' => $width,
			'height' => $height,
			'max_width' => $this->config['dim'][0],
			'max_height' => $this->config['dim'][1]
		);
		$scale = $this->scale($data);
		
		switch ($type)
		{
			case IMG_GIF:
				$image_func = 'imagecreatefromgif';
				$this->type = 'gif';
				break;
			case IMG_JPG:
				$image_func = 'imagecreatefromjpeg';
				$this->type = 'jpg';
				break;
			case IMG_PNG:
				$image_func = 'imagecreatefrompng';
				$this->type = 'png';
				break;
		}
		
		if (!function_exists($image_func))
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			return false;
		}
		
		$image = @$image_func($this->info['temp_avatar']);
		
		if (function_exists('imagealphablending'))
		{
			@imagealphablending($image, true);
		}
		
		$thumb = @imagecreatetruecolor($scale['width'], $scale['height']);
		@imagecopyresampled($thumb, $image, 0, 0, 0, 0, $scale['width'], $scale['height'], $width, $height);
		
		switch ($type)
		{
			case IMG_GIF:
				$image_func = 'imagegif';
				break;
			case IMG_JPG:
				$image_func = 'imagejpeg';
				break;
			case IMG_PNG:
				$image_func = 'imagepng';
				break;
		}
		
		if (!function_exists($image_func))
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			return false;
		}
		
		if ($type == IMG_JPG)
		{
			$created = @$image_func($thumb, $this->info['temp_avatar2'], 60);
		}
		else
		{
			$created = @$image_func($thumb, $this->info['temp_avatar2']);
		}
		
		if (!$created || !@file_exists($this->info['temp_avatar2']))
		{
			$error[] = 'AVATAR_GENERAL_ERROR';
			return false;
		}
		
		@chmod($this->info['temp_avatar2'], 0644);
		@imagedestroy($thumb);
		@imagedestroy($image);
		
		@unlink($this->info['temp_avatar']);
		
		if (@file_exists($this->info['current_avatar']))
		{
			@unlink($this->info['current_avatar']);
		}
		
		@rename($this->info['temp_avatar2'], $this->info['new_avatar']);
		
		return true;
	}
	
	function scale($data)
	{
		if ($data['width'] > $data['height'])
		{
			return array(
				'width' => round($data['width'] * ($data['max_width'] / $data['width'])),
				'height' => round($data['height'] * ($data['max_width'] / $data['width']))
			);
		} 
		else 
		{
			return array(
				'width' => round($data['width'] * ($data['max_width'] / $data['height'])),
				'height' => round($data['height'] * ($data['max_width'] / $data['height']))
			);
		}
	}
	
	function file()
	{
		return $this->info['just_file'];
	}
}

$xavatar = new xavatar();

?>