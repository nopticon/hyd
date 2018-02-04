<?php namespace App;

class __artist_lyric_create extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('artist');
    }

    public function home() {
        global $user, $cache;

        $this->isArtist();

        if ($this->create()) {
            return;
        }

        return;
    }

    private function create() {
        $v = _request([
            'title'  => '',
            'author' => '',
            'text'   => ''
        ]);

        if (_empty($v)) {
            return;
        }

        $sql = 'SELECT *
            FROM _artists
            WHERE ub = ?';
        if (!$ad = sql_fieldrow(sql_filter($sql, $this->object['ub']))) {
            return;
        }

        $v->ub = $this->object['ub'];
        sql_insert('artists_lyrics', $v);

        $sql = 'UPDATE _artists SET lirics = lirics + 1
            WHERE ub = ?';
        sql_query(sql_filter($sql, $this->object['ub']));

        return redirect(s_link('a', $ad['subdomain']));
    }
}
