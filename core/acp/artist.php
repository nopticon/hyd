<?php namespace App;

class __artist extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        if (!_button()) {
            return false;
        }

        $request = _request([
            'name'     => '',
            'local'    => 0,
            'location' => '',
            'genre'    => '',
            'email'    => '',
            'www'      => '',
            'mods'     => ''
        ]);
        $request->subdomain = get_subdomain($request->name);

        if (!$request->name) {
            _pre('Ingresa el nombre del artista.', true);
        }

        $sql_insert = [
            'a_active'  => 1,
            'subdomain' => $request->subdomain,
            'name'      => $request->name,
            'local'     => (int) $request->local,
            'datetime'  => time(),
            'location'  => $request->location,
            'genre'     => $request->genre,
            'email'     => $request->email,
            'www'       => $request->www,
            'bio'       => ''
        ];
        $artist_id = sql_insert('artists', $sql_insert);

        // Cache
        $cache->delete('ub_list a_records ai_records a_recent');

        set_config('max_artists', config('max_artists') + 1);

        // Create directories
        artist_check($artist_id);

        artist_check($artist_id . ' gallery');
        artist_check($artist_id . ' media');
        artist_check($artist_id . ' thumbnails');
        artist_check($artist_id . ' x1');

        // Mods
        if (!empty($request->mods)) {
            $usernames = w();

            $a_mods = explode(nr(), $request->mods);
            foreach ($a_mods as $each) {
                $username_base = get_username_base($each);

                $sql = 'SELECT *
                    FROM _members
                    WHERE username_base = ?
                        AND user_type <> ?
                        AND user_id <> ?';
                if (!$info = sql_fieldrow(sql_filter($sql, $username_base, USER_INACTIVE, 1))) {
                    continue;
                }

                $sql_insert = [
                    'ub'      => $artist_id,
                    'user_id' => $info['user_id']
                ];
                sql_insert('artists_auth', $sql_insert);

                //
                $update = [
                    'user_type'         => USER_ARTIST,
                    'user_auth_control' => 1
                ];

                if (!$info['user_rank']) {
                    $update['user_rank'] = (int) config('default_a_rank');
                }

                $sql = 'UPDATE _members SET ??
                    WHERE user_id = ?
                        AND user_type NOT IN (??, ??)';
                $sql = sql_filter($sql, sql_build('UPDATE', $update), $info['user_id'], USER_INACTIVE, USER_FOUNDER);
                sql_query($sql);
            }

            redirect(s_link('a', $subdomain));
        }
    }
}
