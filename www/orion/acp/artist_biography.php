<?php
namespace App;

class __artist_biography extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('artist');
    }

    public function home() {
        global $user, $comments;

        $this->isArtist();

        if (_button()) {
            $message = request_var('message', '');
            $message = $comments->prepare($message);

            $sql = 'UPDATE _artists SET bio = ?
                WHERE ub = ?';
            sql_query(sql_filter($sql, $message, $this->object['ub']));

            _style('updated');
        }

        $sql = 'SELECT bio
            FROM _artists
            WHERE ub = ?';
        $bio = sql_field(sql_filter($sql, $this->object['ub']), 'bio');

        v_style(
            array(
                'MESSAGE' => $bio
            )
        );

        return;
    }
}
