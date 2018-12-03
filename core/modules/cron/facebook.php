<?php namespace App;

@set_time_limit(120);

list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;

$created_at = $user->format_date(time(), 'Y-m-d');

$sql = 'SELECT e.*
    FROM _events e
    LEFT JOIN _events_share s
        ON s.event_id = e.id
        AND s.created_at = ?
    WHERE e.date >= ?
        AND s.event_id IS NULL
        AND e.facebook_share = 1
    ORDER BY e.date
    LIMIT 1';
$result = sql_rowset(sql_filter($sql, $created_at, $midnight));

foreach ($result as $row) {
    $post = facebook_event($row);

    $insert = [
        'event_id'   => $row['id'],
        'created_at' => $created_at
    ];
    $event_id = sql_insert('events_share', $insert);

    dd($post);
}

dd('.', true);
