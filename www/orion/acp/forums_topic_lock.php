<?php
namespace App;

class __forums_topic_lock extends mac {
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

        $sql = 'SELECT *
            FROM _forum_topics
            WHERE topic_id = ?';
        if (!$topicdata = sql_fieldrow(sql_filter($sql, $topic))) {
            fatal_error();
        }

        $sql = 'UPDATE _forum_topics SET topic_locked = ?
            WHERE topic_id = ?';
        sql_query(sql_filter($sql, !$topicdata['topic_locked'], $topic));

        $format = 'El tema <strong>%s</strong> ha sido %s';

        _pre(sprintf($format, $topicdata['topic_title'], ($topicdata['topic_locked'] ? 'abierto' : 'cerrado')), true);

        return;
    }
}
