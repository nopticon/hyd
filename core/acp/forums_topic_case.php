<?php namespace App;

class __forums_topic_case extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('mod');
    }

    public function home() {
        global $user, $cache;

        if (!_button()) {
            return false;
        }

        $topic_id = request_var('topic_id', 0);

        if (!$topic_id) {
            fatal_error();
        }

        $sql = 'SELECT *
            FROM _forum_topics
            WHERE topic_id = ?';
        if (!$data = sql_fieldrow(sql_filter($sql, $topic_id))) {
            fatal_error();
        }

        $title = ucfirst(strtolower($data['topic_title']));

        $sql = 'UPDATE _forum_topics SET topic_title = ?
            WHERE topic_id = ?';
        sql_query(sql_filter($sql, $title, $topic_id));

        return _pre($data['topic_title'] . ' > ' . $title, true);
    }
}
