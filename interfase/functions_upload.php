<?php
// -------------------------------------------------------------
// $Id: functions_upload.php,v 1.2 2006/01/24 03:59:35 Psychopsia Exp $
//
// STARTED   : Sun Jan 22, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

class upload
{
	var $form_field = '';
	var $out_filename = '';
	var $out_filedir = './';
	var $max_filesize = 0;
	var $make_safe = 1;
	var $force_data_ext = '';
	var $allowed_file_ext = array();
	var $image_ext = array('gif', 'jpg', 'jpeg');
	var $file_ext = '';
	var $real_file_ext = '';
	var $error = 0;
	var $is_image = 0;
	var $orig_filename = '';
	var $parsed_filename = '';
	var $saved_filename = '';
	
	function upload()
	{
		return;
	}
	
	function clean()
	{
		$this->out_filedir = preg_replace('#/$#', '', $this->out_filedir);
	}
	
	function get_extension($file)
	{
		return strtolower(str_replace('.', '', substr($file, strrpos($file, '.'))));
	}
	
	function process()
	{
		$this->clean();
		
		$FILE_NAME = $_FILES[$this->form_field]['name'];
		$FILE_SIZE = $_FILES[$this->form_field]['size'];
		$FILE_TYPE = $_FILES[$this->form_field]['type'];
		
		//
		// If filename is empty
		//
		if ($_FILES[$this->form_field]['name'] == '' || !$_FILES[$this->form_field]['name'] || !$_FILES[$this->form_field]['size'] || ($_FILES[$this->form_field]['name'] == 'none'))
		{
			$this->error = 1;
			return;
		}
		
		$FILE_TYPE = preg_replace('/^(.+?);.*$/', '\\1', $FILE_TYPE);
		
		//
		// If $allowed_file_ext is empty
		//
		if (!is_array($this->allowed_file_ext) || !sizeof($this->allowed_file_ext))
		{
			$this->error = 2;
			return;
		}
		
		//
		// Get filename extension
		//
		$this->file_ext = strtolower($this->get_extension($FILE_NAME));
		
		if (!$this->file_ext)
		{
			$this->error = 2;
			return;
		}
		
		$this->real_file_ext = $this->file_ext;
		
		//
		// Is a valid extension in array
		//
		if (!in_array($this->file_ext, $this->allowed_file_ext))
		{
			$this->error = 2;
			return;
		}
		
		//
		// Check is filesize is lower than $max_filesize
		//
		if ($this->max_filesize && ($FILE_SIZE > $this->max_filesize))
		{
			$this->error = 3;
			return;
		}
		
		//
		// Safety
		//
		$FILE_NAME = preg_replace('/[^\w\.]/', '_', $FILE_NAME);
		
		$this->orig_filename = $FILE_NAME;
		
		//
		// Is a image
		//
		if (is_array($this->image_ext) && sizeof($this->image_ext))
		{
			if (in_array($this->file_ext, $this->image_ext))
			{ 
				$this->is_image = 1;
			}
		}
		
		//
		// Convert
		//
		if ($this->out_filename)
		{
			$this->parsed_filename = $this->out_filename;
		}
		else
		{
			$this->parsed_filename = str_replace('.' . $this->file_ext, '', $FILE_NAME);
		}
		
		//
		// Make safe
		//
		if ($this->make_safe)
		{
			if (preg_match('/\.(cgi|pl|js|asp|php|html|htm|jsp|jar|exe|dll|bat)/', $FILE_NAME))
			{
				$FILE_TYPE = 'text/plain';
				$this->file_ext = 'txt';
			}
		}
		
		//
		// Extension
		//
		if ($this->force_data_ext && !$this->is_image)
		{
			$this->file_ext = str_replace('.', '', $this->force_data_ext); 
		}
		
		$this->parsed_filename .= '.' . $this->file_ext;
		$this->saved_filename = $this->out_filedir.'/'.$this->parsed_filename;
		
		//
		// Copy
		//
		$filename = $_FILES[$this->form_field]['tmp_name'];
		if (!@move_uploaded_file($filename, $this->saved_filename))
		{
			$this->error = 4;
			return;
		}
		
		@chmod($this->saved_filename, 0644);
		
		return;
	}
}

//
// Image class
//
class image
{
	var $src_dir = '';
	var $dst_dir = '';
	
	var $src_file = '';
	var $dst_file = '';
	var $s_file = '';
	var $d_file = '';
	var $ext = '';
	
	var $max_width = 0;
	var $max_height = 0;
	
	var $error = false;
	var $alpha = false;
	
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
	
	function set($src_dir, $dst_dir, $src_file, $dst_file, $max_width, $max_height, $alpha = false)
	{
		$this->src_dir = $src_dir;
		$this->dst_dir = $dst_dir;
		$this->src_file = $src_file;
		$this->dst_file = $dst_file;
		$this->s_file = $this->src_dir . $this->src_file;
		$this->d_file = $this->dst_dir . $this->dst_file;
		
		if (@file_exists($this->d_file))
		{
			$this->dst_file = 't_' . $this->dst_file;
			$this->d_file = $this->dst_dir . '/' . $this->dst_file;
		}
		
		$this->max_width = $max_width;
		$this->max_height = $max_height;
		$this->alpha = $alpha;
	}
	
	function thumbnail()
	{
		if (!$this->max_width || !$this->max_height)
		{
			return false;
		}
		
		//
		// Get image size
		//
		list($width, $height, $type, $void) = getimagesize($this->s_file);
		
		$data = array(
			'width' => $width,
			'height' => $height,
			'max_width' => $this->max_width,
			'max_height' => $this->max_height
		);
		
		if ($width < 1 && $height < 1)
		{
			$this->error = true;
			return false;
		}
		
		if ($width <= $this->max_width && $height <= $this->max_height)
		{
//			return true;
		}
		
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
			$this->error = true;
			return false;
		}
		
		$image = @$image_func($this->s_file);
		
		if ($image)
		{
			if ($this->alpha_blending)
			{
				if (function_exists('imagealphablending'))
				{
					@imagealphablending($image, true);
				}
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
				$this->error = true;
				return false;
			}
			
			if ($type == IMG_JPG)
			{
				$created = @$image_func($thumb, $this->d_file, 60);
			}
			else
			{
				$created = @$image_func($thumb, $this->d_file);
			}
			
			if (!$created || !@file_exists($this->d_file))
			{
				$this->error = true;
				return false;
			}
			
			@chmod($this->d_file, 0644);
			@imagedestroy($thumb);
			@imagedestroy($image);
			
			$data['width'] = $scale['width'];
			$data['height'] = $scale['height'];
			
			return $data;
		}
		
		return false;
	}
}

?>