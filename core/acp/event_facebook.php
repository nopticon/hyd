<?php namespace App;

class __event_facebook extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('colab');
    }

    public function home() {
        global $user;

        if ($this->send()) {
            return;
        }

        $sql = 'SELECT *
            FROM _events
            WHERE date > ?
            ORDER BY date DESC';
        $result = sql_rowset(sql_filter($sql, time()));

        foreach ($result as $row) {
            _style('event_list', [
                'EVENT_ID'    => $row['id'],
                'EVENT_TITLE' => $row['title'],
                'EVENT_DATE'  => $user->format_date($row['date'])
            ]);
        }

        return;
    }

    public function send() {
        global $user, $cache, $upload;

        $v = _request([
            'event_id' => 0
        ]);

        $sql = 'SELECT *
            FROM _events
            WHERE id = ?';
        if (!$event = sql_fieldrow(sql_filter($sql, $v->event_id))) {
            return;
        }

        $response = facebook_event($event);

        return redirect($response['event_url']);
    }
}
