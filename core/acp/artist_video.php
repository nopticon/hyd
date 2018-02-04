<?php namespace App;

class __artist_video extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('artist');
    }

    /*
    Show all videos added to the artist.
    */
    public function home() {
        global $user, $comments;

        $this->isArtist();

        if ((_button() && $this->create()) || (_button('remove') && $this->remove())) {
            return;
        }

        $sql = 'SELECT *
            FROM _artists_video
            WHERE video_a = ?
            ORDER BY video_added DESC';
        $result = sql_rowset(sql_filter($sql, $this->object['ub']));

        foreach ($result as $i => $row) {
            if (!$i) {
                _style('video');
            }

            _style('video.row', [
                'ID'   => $row['video_id'],
                'CODE' => $row['video_code'],
                'NAME' => $row['video_name'],
                'TIME' => $user->format_date($row['video_added'])
            ]);
        }

        return;
    }

    /*
    Create video for this artist.
    */
    private function create() {
        $code  = request_var('code', '');
        $vname = request_var('vname', '');

        if (!empty($code)) {
            $sql = 'SELECT *
                FROM _artists_video
                WHERE video_a = ?
                    AND video_code = ?';
            if (sql_fieldrow(sql_filter($sql, $this->object['ub'], $code))) {
                $code = '';
            }
        }

        if (!empty($code)) {
            $code = get_yt_code($code);
        }

        if (!empty($code)) {
            $insert = [
                'video_a'     => $this->object['ub'],
                'video_name'  => $vname,
                'video_code'  => $code,
                'video_added' => time()
            ];
            sql_insert('artists_video', $insert);

            $sql = 'UPDATE _artists SET a_video = a_video + 1
                WHERE ub = ?';
            sql_query(sql_filter($sql, $this->object['ub']));
        }

        return redirect(_page());
    }

    /*
    Remove selected videos from the artist.
    */
    private function remove() {
        $v = _request(['group' => [0]]);

        if (!$v->group) {
            return;
        }

        $sql = 'SELECT video_id
            FROM _artists_video
            WHERE video_id IN (??)
                AND video_a = ?';
        $result = sql_rowset(sql_filter($sql, implode(',', $v->group), $this->object['ub']), false, 'video_id');

        if (!$result) {
            return;
        }

        $sql = 'DELETE FROM _artists_video
            WHERE video_id IN (??)
                AND video_a = ?';
        sql_query(sql_filter($sql, implode(',', $result), $this->object['ub']));

        return redirect(s_link('acp', ['artist_video', 'a' => $this->object['subdomain']]));
    }
}
