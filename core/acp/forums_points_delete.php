<?php namespace App;

class __forims_points_delete extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        $sql = 'SELECT *
            FROM _forum_topics_nopoints
            ORDER BY exclude_topic';
        $result = sql_rowset($sql);

        foreach ($result as $i => $row) {
            $sql = 'UPDATE _forum_topics
                SET topic_points = 0
                WHERE topic_id = ?';
            sql_query(sql_filter($sql, $row['exclude_topic']));

            if (!$i) {
                _style('topics');
            }

            _style('topics.rows', [
                'NAME' => $row['exclude_topic']
            ]);
        }

        return;
    }
}
