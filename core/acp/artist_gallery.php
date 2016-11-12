<?php
namespace App;

class __artist_gallery extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('artist');
    }

    /*
    List all images associated to this artist.
    */
    public function home() {
        global $user, $cache;

        $this->isArtist();

        if ((_button() && $this->upload()) || (_button('remove') && $this->remove())) {
            return;
        }

        $sql = 'SELECT g.*
            FROM _artists a, _artists_images g
            WHERE a.ub = ?
                AND a.ub = g.ub
            ORDER BY image ASC';
        $result = sql_rowset(sql_filter($sql, $this->object['ub']));

        foreach ($result as $i => $row) {
            if (!$i) {
                _style('gallery');
            }

            $footer    = s_link(
                'acp',
                array(
                    'artist_gallery',
                    'a' => $this->object['subdomain'],
                    'footer' => $row['image']
                )
            );

            $image  = config('artists_url') . $this->object['ub'] . '/thumbnails/' . $row['image'] . '.jpg';

            $rimage = get_a_imagepath(
                config('artists_path'),
                config('artists_url'),
                $this->data['ub'],
                $row['image'] . '.jpg',
                w('gallery x1')
            );

            _style(
                'gallery.row',
                array(
                    'ITEM'     => $row['image'],
                    'URL'      => s_link('a', $this->object['subdomain'], 4, $row['image'], 'view'),
                    'U_FOOTER' => $footer,
                    'IMAGE'    => $image,
                    'RIMAGE'   => $rimage,
                    'WIDTH'    => $row['width'],
                    'HEIGHT'   => $row['height'],
                    'TFOOTER'  => $row['image_footer']
                )
            );
        }

        return;
    }

    /*
    Upload images to artist's gallery.
    */
    private function upload() {
        global $upload;

        $a_1 = artist_check($this->object['ub'] . ' x1');
        $a_2 = artist_check($this->object['ub'] . ' gallery');
        $a_3 = artist_check($this->object['ub'] . ' thumbnails');

        if (!$a_1 || !$a_2 || !$a_3) {
            return;
        }

        $filepath = config('artists_path') . $this->object['ub'] . '/';
        $filepath_1 = $filepath . 'x1/';
        $filepath_2 = $filepath . 'gallery/';
        $filepath_3 = $filepath . 'thumbnails/';

        $f = $upload->process($filepath_1, 'add_image', 'jpg');

        if (!sizeof($upload->error) && $f !== false) {
            $sql = 'SELECT MAX(image) AS total
                FROM _artists_images
                WHERE ub = ?';
            $img = sql_field(sql_filter($sql, $this->object['ub']), 'total', 0);

            $a = 0;
            foreach ($f as $row) {
                $img++;

                $xa = $upload->resize($row, $filepath_1, $filepath_1, $img, array(600, 400), false, false, true);
                if ($xa === false) {
                    continue;
                }

                $xb = $upload->resize($row, $filepath_1, $filepath_2, $img, array(300, 225), false, false);
                $xc = $upload->resize($row, $filepath_2, $filepath_3, $img, array(100, 75), false, false);

                $insert = array(
                    'ub'     => (int) $this->object['ub'],
                    'image'  => (int) $img,
                    'width'  => $xa->width,
                    'height' => $xa->height
                );
                sql_insert('artists_images', $insert);

                $a++;
            }

            if ($a) {
                $sql = 'UPDATE _artists SET images = images + ??
                    WHERE ub = ?';
                sql_query(sql_filter($sql, $a, $this->object['ub']));
            }

            redirect(s_link('acp', array('artist_gallery', 'a' => $this->object['subdomain'])));
        }

        _style(
            'error',
            array(
                'MESSAGE' => parse_error($upload->error)
            )
        );

        return;
    }

    /*
    Remove selected images from artist's gallery.
    */
    private function remove() {
        $s_images = request_var('ls_images', array(0));

        if (sizeof($s_images)) {
            $common_path = config('artists_path') . $this->object['ub'] . '/';
            $path = array(
                $common_path . 'x1/',
                $common_path . 'gallery/',
                $common_path . 'thumbnails/',
            );

            $sql = 'SELECT *
                FROM _artists_images
                WHERE ub = ?
                    AND image IN (??)
                ORDER BY image';
            $result = sql_rowset(sql_filter($sql, $this->object['ub'], implode(',', $s_images)));

            $affected = w();
            foreach ($result as $row) {
                foreach ($path as $path_row) {
                    $filepath = $path_row . $row['image'] . '.jpg';
                    _rm($filepath);
                }
                $affected[] = $row['image'];
            }

            if (count($affected)) {
                $sql = 'DELETE FROM _artists_images
                    WHERE ub = ?
                        AND image IN (??)';
                sql_query(sql_filter($sql, $this->object['ub'], implode(',', $affected)));

                $sql = 'UPDATE _artists SET images = images - ??
                    WHERE ub = ?';
                sql_query(sql_filter($sql, sql_affectedrows(), $this->object['ub']));
            }
        }

        return redirect(s_link('acp', array('artist_gallery', 'a' => $this->object['subdomain'])));
    }

    private function footer() {
        $v = _request_var(array('image' => '', 'value' => ''));

        $sql = 'SELECT *
            FROM _artists_images
            WHERE ub = ?
                AND image = ?';
        if (!$row = sql_fieldrow(sql_filter($sql, $this->object['ub'], $v->image))) {
            fatal_error();
        }

        $sql = 'UPDATE _artists_images SET image_footer = ?
            WHERE ub = ?
                AND image = ?';
        sql_query(sql_filter($sql, $v->value, $this->object['ub'], $v->image));

        $this->e($v->value);
    }
}
