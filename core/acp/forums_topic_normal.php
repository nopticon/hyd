<?php namespace App;

class __forums_topic_normal extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('mod');
    }

    public function home() {
        global $user, $cache;

        if (_button()) {
            $topic = request_var('topic', 0);

            $sql = 'SELECT *
                FROM _forum_topics
                WHERE topic_id = ?';
            if (!$topicdata = sql_fieldrow(sql_filter($sql, $topic))) {
                fatal_error();
            }

            $sql = 'UPDATE _forum_topics
                SET topic_color = ?, topic_announce = 0, topic_important = 0
                WHERE topic_id = ?';
            sql_query(sql_filter($sql, '', $topic));

            _style('updated', [
                'MESSAGE' => 'El tema <strong>' . $topicdata['topic_title'] . '</strong> ha sido normalizado.'
            ]);
        }

        $sql = 'SELECT t.topic_id, t.topic_title, f.forum_name
            FROM _forums f, _forum_topics t
            WHERE f.forum_id = t.forum_id
                AND (topic_announce = 1
                OR topic_important = 1)
            ORDER BY forum_order, topic_title';
        $topics = sql_rowset($sql);

        $forum_name = '';
        foreach ($topics as $i => $row) {
            if (!$i) {
                _style('topics');
            }

            if ($forum_name != $row['forum_name']) {
                _style('topics.forum', ['FORUM_NAME' => $row['forum_name']]);
            }

            $forum_name = $row['forum_name'];

            _style('topics.forum.row', [
                'TOPIC_ID'    => $row['topic_id'],
                'TOPIC_TITLE' => $row['topic_title']
            ]);
        }

        return;
    }
}
