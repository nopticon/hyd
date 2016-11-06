<?php
namespace App;

class Upload {
    public $error = array();

    public function __construct() {
        return;
    }

    public function array_merge($files) {
        $file_ary = w();

        if (!is_array($files)) {
            return $file_ary;
        }

        $a_keys = array_keys($files);
        for ($i = 0, $end = sizeof($files['name']); $i < $end; $i++) {
            foreach ($a_keys as $k) {
                $file_ary[$i][$k] = $files[$k][$i];
            }
        }

        $check = array(
            'name'  => '',
            'name'  => 'none',
            'size'  => 0,
            'error' => 4
        );

        foreach ($file_ary as $i => $row) {
            foreach ($check as $k => $v) {
                if ($row[$k] === $v) {
                    unset($file_ary[$i]);
                }
            }
        }

        return array_values($file_ary);
    }

    public function rename($a, $b) {
        $filename = str_replace($a->random, $b, $a->filepath);
        @rename($a->filepath, $filename);

        _chmod($filename);
        return $filename;
    }

    public function _row($filepath, $filename) {
        $row = (object) array(
            'extension' => extension($filename),
            'name'      => strtolower($filename),
            'random'    => time() . '_' . substr(md5(unique_id()), 0, 10)
        );

        $row->filename = $row->random . '.' . $row->extension;
        $row->filepath = $filepath . $row->filename;

        return $row;
    }

    public function remote($filepath, $locations, $extension, $filesize = false, $safe = true) {
        global $user;

        $files = w();
        $umask = umask(0);

        if (!sizeof($locations)) {
            $this->error[] = 'FILES_NO_FILES';
            return false;
        }

        foreach ($locations as $location) {
            $row = $this->_row($filepath, $location);

            if (!in_array($row->extension, $extension)) {
                $this->error[] = sprintf(lang('upload_invalid_ext'), $row->name);
                $row->error = 1;
                continue;
            }

            if ($safe) {
                if (preg_match('/\.(cgi|pl|js|asp|php|html|htm|jsp|jar|exe|dll|bat)/', $row->name)) {
                    $row->extension = 'txt';
                }
            }

            if (!@copy($location, $row->filepath)) {
                $this->error[] = sprintf(lang('upload_failed'), $row->name);
                $row->error = 1;
                continue;
            }

            _chmod($row->filepath);

            $files[] = $row;
        }

        @umask($umask);
        return (count($files)) ? $files : false;
    }

    public function avatar_process($alias, &$_fields, &$error) {
        global $user;

        $path = config('avatar_path');

        $send = $this->process($path, 'avatar');

        if (count($this->error)) {
            $error = array_merge($error, $this->error);
            return;
        }

        if ($send !== false) {
            foreach ($send as $row) {
                $size = $this->resize($row, $path, $path, _encode($alias) . time(), array(70, 70), false, false, true);
                if ($size === false) {
                    continue;
                }

                if ($user->d('avatar')) {
                    _rm($path . $user->d('avatar'));
                }

                $_fields->avatar = $row->filename;
            }
        }

        return;
    }

    public function process($filepath, $files, $extension = 'gif png jpg jpeg', $filesize = false, $safe = true) {
        global $user;

        if (!is_array($files)) {
            $files = request_var('files:' . $files);

            if ($files === false) {
                return false;
            }
        }

        if (isset($files['name']) && !is_array($files['name'])) {
            foreach ($files as $i => $row) {
                $files[$i] = array($row);
            }
        }

        $umask = umask(0);
        $files = $this->array_merge($files);

        if (!is_array($extension)) {
            $extension = w($extension);
        }

        if (!sizeof($files)) {
            $this->error[] = lang('files_no_files');
            return false;
        }

        if ($filesize === false) {
            $filesize = upload_maxsize();
        }

        foreach ($files as $i => $row) {
            if ($row['error']) {
                if ($row['error'] == 4) {
                    unset($files[$i]);
                }

                continue;
            }

            $r = $this->_row($filepath, $row['name']);

            $r->size = $row['size'];
            $r->tmp = $row['tmp_name'];

            if ($safe && preg_match('/\.(cgi|pl|js|asp|php|html|htm|jsp|jar|exe|dll|bat)/', $r->name)) {
                $r->extension = 'txt';
            }

            if (!in_array($r->extension, $extension)) {
                $this->error[] = sprintf(lang('upload_invalid_ext'), $r->name);
                $r->error = 1;
                continue;
            }

            if ($r->size > $filesize) {
                $this->error[] = sprintf(lang('upload_too_big'), $r->name, ($filesize / 1048576));
                $r->error = 1;
                continue;
            }

            if (!@is_writable($filepath)) {
                $this->error[] = 'Reading error.';
                $r->error = 1;
                continue;
            }

            if (!@move_uploaded_file($r->tmp, $r->filepath)) {
                $this->error[] = sprintf(lang('upload_failed'), $r->name);
                $r->error = 1;
                continue;
            }

            // _pre($r);
            // _pre($row, true);

            _chmod($r->filepath);

            if (@filesize($r->filepath) > $filesize) {
                _rm($r->filepath);

                $this->error[] = sprintf(lang('upload_too_big'), $r->name, ($filesize / 1048576));
                $r->error = 1;
                continue;
            }

            $files[$i] = $r;
        }

        @umask($umask);
        return (count($files)) ? $files : false;
    }

    public function resize(
        &$row,
        $folder_a,
        $folder_b,
        $filename,
        $measure,
        $do_scale = true,
        $watermark = true,
        $remove = false,
        $watermark_file = false
    ) {
        $t = (object) array(
            'filename' => $filename . '.' . $row->extension,
            'source' => $folder_a . $row->filename
        );

        if (!@is_readable($t->source)) {
            $row->error = 'not_readable';
            return false;
        }

        $t->destination = $folder_b . $t->filename;

        foreach ($t as $tk => $tv) {
            $row->$tk = $tv;
        }

        // Get source image data
        $dim = @getimagesize($t->source);

        if ($dim[0] < 1 && $dim[1] < 1) {
            $row->error = 'bad_size';
            return false;
        }

        if ($dim[0] < $measure[0] && $dim[1] < $measure[1]) {
            $measure[0] = $dim[0];
            $measure[1] = $dim[1];
        }

        $row->width = $dim[0];
        $row->height = $dim[1];
        $row->mwidth = $measure[0];
        $row->mheight = $measure[1];

        $mode = ($do_scale === true) ? 'c' : 'v';
        $scale = $this->scale($mode, $row);

        $row->width = $scale->width;
        $row->height = $scale->height;

        switch ($dim[2]) {
            case IMG_JPG:
                $image_f = 'imagecreatefromjpeg';
                $image_g = 'imagejpeg';
                $image_t = 'jpg';
                break;
            case IMG_GIF:
                $image_f = 'imagecreatefromgif';
                $image_g = 'imagegif';
                $image_t = 'gif';
                break;
            case IMG_PNG:
                $image_f = 'imagecreatefrompng';
                $image_g = 'imagepng';
                $image_t = 'png';
                break;
        }

        if (!$generated = $image_f($t->source)) {
            return false;
        }

        imagealphablending($generated, true);
        $thumb = imagecreatetruecolor($row->width, $row->height);
        imagecopyresampled($thumb, $generated, 0, 0, 0, 0, $row->width, $row->height, $dim[0], $dim[1]);

        // Watermark
        if ($watermark) {
            if ($watermark_file === false) {
                $watermark_file = config('watermark');
            }

            if (!empty($watermark_file)) {
                $wm = imagecreatefrompng($watermark_file);
                $wm_w = imagesx($wm);
                $wm_h = imagesy($wm);

                if ($watermark_file == 'w') {
                    $dest_x = $row->width - $wm_w - 5;
                    $dest_y = $row->height - $wm_h - 5;

                    imagecopymerge($thumb, $wm, $dest_x, $dest_y, 0, 0, $wm_w, $wm_h, 100);
                    imagedestroy($wm);
                } else {
                    $dest_x = round(($row->width / 2) - ($wm_w / 2));
                    $dest_y = round(($row->height / 2) - ($wm_h / 2));

                    $thumb = $this->alpha_overlay($thumb, $wm, $wm_w, $wm_h, $dest_x, $dest_y, 100);
                }
            }
        }

        if ($dim[2] == IMG_JPG) {
            $created = @$image_g($thumb, $t->destination, 85);
        } else {
            $created = @$image_g($thumb, $t->destination);
        }

        if (!$created || !file_exists($t->destination)) {
            $row->error = 'not_created';
            return false;
        }

        _chmod($t->destination);

        imagedestroy($thumb);
        imagedestroy($generated);

        if ($remove && file_exists($t->source)) {
            _rm($t->source);
        }

        return $row;
    }

    public function scale($mode, $a) {
        switch ($mode) {
            case 'c':
                $width = $a->mwidth;
                $height = round(($a->height * $a->mwidth) / $a->width);
                break;
            case 'v':
                if ($a->width > $a->height) {
                    $width = round($a->width * ($a->mwidth / $a->width));
                    $height = round($a->height * ($a->mwidth / $a->width));
                } else {
                    $width = round($a->width * ($a->mwidth / $a->height));
                    $height = round($a->height * ($a->mwidth / $a->height));
                }
                break;
        }
        return (object) array('width' => $width, 'height' => $height);
    }

    public function alpha_overlay($destImg, $overlayImg, $imgW, $imgH, $onx, $ony, $alpha = 0) {
        for ($y = 0; $y < $imgH; $y++) {
            for ($x = 0; $x < $imgW; $x++) {
                $ovrARGB = imagecolorat($overlayImg, $x, $y);
                $ovrA = ($ovrARGB >> 24) << 1;
                $ovrR = $ovrARGB >> 16 & 0xFF;
                $ovrG = $ovrARGB >> 8 & 0xFF;
                $ovrB = $ovrARGB & 0xFF;

                $change = false;
                if ($ovrA == 0) {
                    $dstR = $ovrR;
                    $dstG = $ovrG;
                    $dstB = $ovrB;
                    $change = true;
                } elseif ($ovrA < 254) {
                    $dstARGB = imagecolorat($destImg, $x, $y);
                    $dstR = $dstARGB >> 16 & 0xFF;
                    $dstG = $dstARGB >> 8 & 0xFF;
                    $dstB = $dstARGB & 0xFF;
                    $dstR = (($ovrR * (0xFF - $ovrA)) >> 8) + (($dstR * $ovrA) >> 8);
                    $dstG = (($ovrG * (0xFF - $ovrA)) >> 8) + (($dstG * $ovrA) >> 8);
                    $dstB = (($ovrB * (0xFF - $ovrA)) >> 8) + (($dstB * $ovrA) >> 8);
                    $change = true;
                }

                if ($change) {
                    $dstRGB = imagecolorallocatealpha($destImg, $dstR, $dstG, $dstB, $alpha);
                    imagesetpixel($destImg, ($onx + $x), ($ony + $y), $dstRGB);
                }
            }
        }

        return $destImg;
    }

    public function resizeImage($image, $width, $height, $scale) {
        list($imagewidth, $imageheight, $imageType) = getimagesize($image);

        $imageType      = image_type_to_mime_type($imageType);
        $newImageWidth  = ceil($width * $scale);
        $newImageHeight = ceil($height * $scale);
        $newImage       = imagecreatetruecolor($newImageWidth, $newImageHeight);

        switch ($imageType) {
            case "image/gif":
                $source = imagecreatefromgif($image);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                $source = imagecreatefromjpeg($image);
                break;
            case "image/png":
            case "image/x-png":
                $source = imagecreatefrompng($image);
                break;
          }

        imagecopyresampled($newImage, $source, 0, 0, 0, 0, $newImageWidth, $newImageHeight, $width, $height);

        switch ($imageType) {
            case 'image/gif':
                imagegif($newImage, $image);
                break;
            case 'image/pjpeg':
            case 'image/jpeg':
            case 'image/jpg':
                imagejpeg($newImage, $image, 90);
                break;
            case 'image/png':
            case 'image/x-png':
                imagepng($newImage, $image);
                break;
        }

        _chmod($image);
        return $image;
    }

    public function resizeThumbnailImage(
        $thumb_image_name,
        $image,
        $width,
        $height,
        $start_width,
        $start_height,
        $scale
    ) {
        list($imagewidth, $imageheight, $imageType) = getimagesize($image);
        $imageType = image_type_to_mime_type($imageType);

        $newImageWidth  = ceil($width * $scale);
        $newImageHeight = ceil($height * $scale);
        $newImage       = imagecreatetruecolor($newImageWidth, $newImageHeight);

        switch ($imageType) {
            case 'image/gif':
                $source = imagecreatefromgif($image);
                break;
            case 'image/pjpeg':
            case 'image/jpeg':
            case 'image/jpg':
                $source = imagecreatefromjpeg($image);
                break;
            case 'image/png':
            case 'image/x-png':
                $source = imagecreatefrompng($image);
                break;
          }

        imagecopyresampled(
            $newImage,
            $source,
            0,
            0,
            $start_width,
            $start_height,
            $newImageWidth,
            $newImageHeight,
            $width,
            $height
        );

        switch ($imageType) {
            case "image/gif":
                imagegif($newImage, $thumb_image_name);
                break;
            case "image/pjpeg":
            case "image/jpeg":
            case "image/jpg":
                imagejpeg($newImage, $thumb_image_name, 90);
                break;
            case "image/png":
            case "image/x-png":
                imagepng($newImage, $thumb_image_name);
                break;
        }

        _chmod($thumb_image_name);
        return $thumb_image_name;
    }

    public function getWidth($image) {
        if (!@file_exists($image)) {
            return false;
        }

        $size = @getimagesize($image);
        $width = $size[0];
        return $width;
    }

    public function getHeight($image) {
        if (!@file_exists($image)) {
            return false;
        }

        $size = getimagesize($image);
        $height = $size[1];
        return $height;
    }
}
