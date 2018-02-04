<?php namespace App;

class __forums_topic_title extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('mod');
    }

    public function home() {
        global $user, $cache;

        if (!_button()) {
            return false;
        }

        $topic = request_var('topic', 0);
        $title = request_var('title', '');

        $sql = 'SELECT *
            FROM _forum_topics
            WHERE topic_id = ?';
        if (!$topicdata = sql_fieldrow(sql_filter($sql, $topic))) {
            fatal_error();
        }

        $sql = 'UPDATE _forum_topics SET topic_title = ?
            WHERE topic_id = ?';
        sql_query(sql_filter($sql, $title, $topic));

        $format  = 'El titulo del tema <strong>%s</strong> ha sido cambiado por <strong>%s</strong>.';
        $message = sprintf($format, $topicdata['topic_title'], $title);

        return _pre($message, true);
    }
}
