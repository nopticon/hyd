<?php namespace App;

class __artist_media extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('artist');
    }

    public function home() {
        global $user, $comments;

        $this->isArtist();

        if (_button()) {
            $this->upload();
        }

        if (_button('remove')) {
            $this->remove();
        }

        $sql = 'SELECT *
            FROM _dl
            WHERE ub = ?
            ORDER BY title';
        if ($result = sql_rowset(sql_filter($sql, $this->object['ub']))) {
            foreach ($result as $i => $row) {
                if (!$i) {
                    _style('media');
                }

                _style('media.row', [
                    'ITEM'           => $row['id'],
                    'URL'            => s_link('a', $this->object['subdomain'], 9, $row['id']),
                    'POSTS_URL'      => s_link('a', $this->object['subdomain'], 9, $row['id']) . '#dpf',
                    'IMAGE_TYPE'     => $downloads_type[$row['ud']],
                    'DOWNLOAD_TITLE' => $row['title'],
                    'VIEWS'          => $row['views'],
                    'DOWNLOADS'      => $row['downloads']
                ]);
            }
        }

        return;
    }

    private function upload() {
        global $user, $cache, $upload;

        $limit = set_time_limit(0);

        $filepath = config('artists_path') . $this->object['ub'] . '/';
        $filepath_1 = $filepath . 'media/';

        $check = artist_check($this->object['ub'] . ' media');
        $f = ($check !== false) ? $upload->process($filepath_1, 'create', 'mp3') : false;

        if ($f === false) {
            return;
        } elseif (!sizeof($upload->error)) {
            $a = sql_total('_dl');

            foreach ($f as $i => $row) {
                if (!$i) {
                    $getID3 = new \getID3;
                }

                $filename = $upload->rename($row, $a);
                $tags = $getID3->analyze($filename);
                $a++;

                $mt = new \stdClass();
                foreach (w('title genre album year') as $w) {
                    $mt->$w = (isset($tags['tags']['id3v1'][$w][0])) ? htmlencode($tags['tags']['id3v1'][$w][0]) : '';
                }

                $tags['playtime_string'] = isset($tags['playtime_string']) ? $tags['playtime_string'] : '0';

                $sql_insert = [
                    'ud'        => 1,
                    'ub'        => $this->object['ub'],
                    'alias'     => friendly($mt->title),
                    'title'     => $mt->title,
                    'views'     => 0,
                    'downloads' => 0,
                    'votes'     => 0,
                    'posts'     => 0,
                    'date'      => time(),
                    'filesize'  => @filesize($filename),
                    'duration'  => $tags['playtime_string'],
                    'genre'     => $mt->genre,
                    'album'     => $mt->album,
                    'year'      => $mt->year
                ];
                $media_id = sql_insert('dl', $sql_insert);
            }

            $sql = 'UPDATE _artists SET um = um + ??
                WHERE ub = ?';
            sql_query(sql_filter($sql, count($f), $a_id));

            $cache->delete('downloads_list');

            redirect(s_link('acp', ['artist_media', 'a' => $this->object['subdomain'], 'id' => $media_id]));
        } else {
            _style('error', [
                'MESSAGE' => parse_error($upload->error)
            ]);
        }

        return;
    }

    private function remove() {
        global $cache;

        $remove = request_var('s_downloads', [0]);

        if (!count($remove)) {
            return;
        }

        $sql = 'SELECT *
            FROM _dl
            WHERE id IN (??)
                AND ub = ?';
        if ($result = sql_rowset(sql_filter($sql, _implode(',', $remove), $this->object['ub']))) {
            foreach ($result as $row) {
                $path = artist_root($this->object['ub'] . ' media ' . $row['id'] . '.mp3');

                _rm($path);

                $sql = 'DELETE FROM _dl
                    WHERE id = ?';
                sql_query(sql_filter($sql, $row['id']));

                $cache->delete('downloads_list');
            }

            redirect(s_link('acp', ['artist_media', 'a' => $this->object['subdomain']]));
        }

        return;
    }
}
