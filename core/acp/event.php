<?php namespace App;

class __event extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('colab');
    }

    public function home() {
        global $user, $cache, $upload;

        $error = w();

        if (_button()) {
            $filepath   = config('events_path');
            $filepath_1 = $filepath . 'future/';
            $filepath_2 = $filepath_1 . 'thumbnails/';

            $f = $upload->process($filepath_1, 'event_image', 'jpg');

            if (!sizeof($upload->error) && $f !== false) {
                $img = sql_total('_events');

                // Create vars
                $event_name          = request_var('event_name', '');
                $event_artists       = request_var('event_artists', '', true);
                $event_year          = request_var('event_year', 0);
                $event_month         = request_var('event_month', 0);
                $event_day           = request_var('event_day', 0);
                $event_hours         = request_var('event_hours', 0);
                $event_minutes       = request_var('event_minutes', 0);
                $event_current_topic = request_var('event_current_topic', 0);

                $time_c = $user->timezone - $user->dst;
                $v_date = gmmktime($event_hours, $event_minutes, 0, $event_month, $event_day, $event_year) - $time_c;

                foreach ($f as $row) {
                    $xa = $upload->resize($row, $filepath_1, $filepath_1, $img, [600, 400], false, false, true);
                    if ($xa === false) {
                        continue;
                    }
                    $xb = $upload->resize($row, $filepath_1, $filepath_2, $img, [100, 75], false, false);

                    $event_alias = $event_alias2 = friendly($event_name);

                    $event_count = 0;
                    while (true) {
                        $sql = 'SELECT id
                            FROM _events
                            WHERE event_alias = ?';
                        if (!sql_fieldrow(sql_filter($sql, $event_alias))) {
                            break;
                        }

                        $event_count++;
                        $event_alias = $event_alias2 . '-' . $event_count;
                    }

                    $insert = [
                        'event_alias'  => $event_alias,
                        'title'        => $event_name,
                        'archive'      => '',
                        'date'         => (int) $v_date,
                        'event_update' => time()
                    ];
                    $event_id = sql_insert('events', $insert);

                    //
                    $artists_ary = explode(nr(), $event_artists);
                    foreach ($artists_ary as $row) {
                        $subdomain = get_subdomain($row);

                        $sql = 'SELECT *
                            FROM _artists
                            WHERE subdomain = ?';
                        if ($a_row = sql_fieldrow(sql_filter($sql, $subdomain))) {
                            $sql = 'SELECT *
                                FROM _artists_events
                                WHERE a_artist = ?
                                    AND a_event = ?';
                            if (!sql_fieldrow(sql_filter($sql, $a_row['ub'], $event_id))) {
                                $sql_insert = [
                                    'a_artist' => $a_row['ub'],
                                    'a_event'  => $event_id
                                ];
                                sql_insert('artists_events', $sql_insert);
                            }
                        }
                    }

                    // Alice: Create topic
                    $post_message = 'Evento publicado';
                    $post_time    = time();
                    $forum_id     = 21;
                    $poster_id    = 1433;

                    $sql = 'SELECT *
                        FROM _forum_topics
                        WHERE topic_id = ?';
                    if (!$row_current_topic = sql_fieldrow(sql_filter($sql, $event_current_topic))) {
                        $insert = [
                            'topic_title'     => $event_name,
                            'topic_poster'    => $poster_id,
                            'topic_time'      => $post_time,
                            'forum_id'        => $forum_id,
                            'topic_locked'    => 0,
                            'topic_announce'  => 0,
                            'topic_important' => 0,
                            'topic_vote'      => 1,
                            'topic_featured'  => 1,
                            'topic_points'    => 1
                        ];
                        $topic_id = sql_insert('forum_topics', $insert);

                        $event_current_topic = 0;
                    } else {
                        $topic_id = $event_current_topic;

                        $post_message .= ' en la secci&oacute;n de eventos';

                        $sql = 'UPDATE _forum_topics SET topic_title = ?
                            WHERE topic_id = ?';
                        sql_query(sql_filter($sql, $event_name, $topic_id));
                    }

                    $post_message .= '.';
                    $poll_length   = 0;

                    $insert = [
                        'topic_id'  => (int) $topic_id,
                        'forum_id'  => $forum_id,
                        'poster_id' => $poster_id,
                        'post_time' => $post_time,
                        'poster_ip' => $user->ip,
                        'post_text' => $post_message,
                        'post_np'   => ''
                    ];
                    $post_id = sql_insert('forum_posts', $insert);

                    $sql = 'UPDATE _events SET event_topic = ?
                        WHERE id = ?';
                    sql_query(sql_filter($sql, $topic_id, $event_id));

                    $insert = [
                        'topic_id'    => (int) $topic_id,
                        'vote_text'   => '&iquest;Asistir&aacute;s a ' . $event_name . '?',
                        'vote_start'  => time(),
                        'vote_length' => (int) ($poll_length * 86400)
                    ];
                    $poll_id = sql_insert('poll_options', $insert);

                    $poll_options = [
                        1 => 'Si asistir&eacute;'
                    ];

                    foreach ($poll_options as $option_id => $option_text) {
                        $sql_insert = [
                            'vote_id'          => (int) $poll_id,
                            'vote_option_id'   => (int) $option_id,
                            'vote_option_text' => $option_text,
                            'vote_result'      => 0
                        ];
                        sql_insert('poll_results', $sql_insert);
                    }

                    $forum_plus = !$event_current_topic ? ', forum_topics = forum_topics + 1 ' : '';

                    $sql = 'UPDATE _forums
                        SET forum_posts = forum_posts + 1, forum_last_topic_id = ?' . $forum_plus . '
                        WHERE forum_id = ?';
                    sql_query(sql_filter($sql, $topic_id, $forum_id));

                    $sql = 'UPDATE _forum_topics SET topic_first_post_id = ?, topic_last_post_id = ?
                        WHERE topic_id = ?';
                    sql_query(sql_filter($sql, $post_id, $post_id, $topic_id));

                    $sql = 'UPDATE _members SET user_posts = user_posts + 1
                        WHERE user_id = ?';
                    sql_query(sql_filter($sql, $poster_id));

                    // Notify
                    $user->save_unread(UH_T, $topic_id);

                    // Post event to Facebook page
                    $event_protocol = get_protocol(false, false) . ':';
                    $event_url      = s_link('events', $event_alias);
                    $facebook_url   = 'https://graph.facebook.com/' . config('facebook_app_id') . '/feed';
                    $facebook_msg   = 'Rock Republik te invita al ' . ((strpos($event_name, 'concierto') === false) ? 'evento ' : '');

                    $facebook_data = [
                        'full_picture' => $event_protocol . config('events_url') . 'future/' . $img  . '.jpg',
                        'link'         => $event_protocol . '//' . config('server_name') . $event_url,
                        'message'      => $facebook_msg . $event_name,
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

                    redirect($event_url);
                }
            }

            _style('error', [
                'MESSAGE' => parse_error($upload->error)
            ]);
        }

        $year  = date('Y');
        $dates = [
            'day'   => range(1, 31),
            'month' => range(1, 12),
            'year'  => range($year, $year + 3),
        ];

        _style('dates');

        foreach ($dates as $name => $value) {
            _style('dates.block', [
                'NAME' => $name
            ]);

            foreach ($value as $name2 => $value2) {
                _style('dates.block.list', [
                    'NUMBER' => $value2
                ]);
            }
        }

        $hours = [
            'hours'   => range(0, 23),
            'minutes' => range(0, 59, 5),
        ];

        _style('hours');

        foreach ($hours as $name => $value) {
            _style('hours.block', [
                'NAME' => $name
            ]);

            foreach ($value as $name2 => $value2) {
                _style('hours.block.list', [
                    'NUMBER' => $value2
                ]);
            }
        }

        $sql = 'SELECT topic_id, topic_title
            FROM _forum_topics t
            LEFT OUTER JOIN _events e ON t.topic_id = e.event_topic
            WHERE e.event_topic IS NULL
                AND forum_id = 21
            ORDER BY topic_time DESC';
        $topics = sql_rowset($sql);

        foreach ($topics as $i => $row) {
            if (!$i) {
                _style('topics');
            }

            _style('topics.row', [
                'TOPIC_ID'    => $row['topic_id'],
                'TOPIC_TITLE' => $row['topic_title']
            ]);
        }

        return;
    }
}
