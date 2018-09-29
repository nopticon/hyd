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

        $event_protocol = get_protocol(false, false) . ':';
        $event_url      = s_link('events', $event['event_alias']);
        $facebook_url   = 'https://graph.facebook.com/' . config('facebook_app_id') . '/feed';
        $facebook_msg   = 'Rock Republik te invita al ' . ((strpos($event['title'], 'concierto') === false) ? 'evento ' : '');

        $facebook_data = [
            'full_picture' => $event_protocol . config('events_url') . 'future/' . $event['id']  . '.jpg',
            'link'         => $event_protocol . '//' . config('server_name') . $event_url,
            'message'      => $facebook_msg . $event['title'],
            'type'         => 'photo',
            'access_token' => config('facebook_access_token')
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $facebook_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $facebook_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        dd($response, true);

        return redirect($event_url);
    }
}
