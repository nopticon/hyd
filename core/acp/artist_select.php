<?php namespace App;

class __artist_select extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('artist');
    }

    public function home() {
        global $user, $cache;

        $artist   = request_var('a', '');
        $redirect = request_var('r', '');

        if (!empty($artist)) {
            redirect(s_link('acp', [$redirect, 'a' => $artist]));
        }

        $sql_artist = '';
        if (!$user->is('founder')) {
            $sql = 'SELECT ub
                FROM _artists_auth
                WHERE user_id = ?';
            $ub = sql_rowset(sql_filter($sql, $user->d('user_id')), false, 'ub');

            $sql_artist = ' WHERE ub IN (' . _implode(',', $ub) . ') ';
        }

        $sql = 'SELECT ub, subdomain, name
            FROM _artists
            ??
            ORDER BY name';
        $artists = sql_rowset(sql_filter($sql, $sql_artist));

        foreach ($artists as $i => $row) {
            if (!$i) {
                _style('artist_list');
            }

            _style('artist_list.row', [
                'URL'  => s_link('acp', [$redirect, 'a' => $row['subdomain']]),
                'NAME' => $row['name']
            ]);
        }

        return;
    }
}
