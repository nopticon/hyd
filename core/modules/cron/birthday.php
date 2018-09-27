<?php namespace App;

$max_email = 10;
@set_time_limit(120);

$emailer = new emailer();

$sql = "SELECT *
    FROM _members
    WHERE user_type NOT IN (??)
        AND user_id NOT IN (SELECT ban_userid FROM _banlist)
        AND user_birthday LIKE '%??'
        AND user_birthday_last < ?
    ORDER BY username
    LIMIT ??";
$result = sql_rowset(sql_filter($sql, USER_INACTIVE, date('md'), YEAR, $max_email));

$done      = [];
$users = [];

foreach ($result as $row) {
    $emailer->from('notify');
    $emailer->use_template('user_birthday');
    $emailer->email_address($row['user_email']);

    if (!empty($row['user_public_email']) && $row['user_email'] != $row['user_public_email']) {
        $emailer->cc($row['user_public_email']);
    }

    $emailer->assign_vars([
        'USERNAME' => $row['username']
    ]);

    $emailer->send();
    $emailer->reset();

    $done[]  = $row['user_id'];
    $users[] = $row['username'];
}

if (count($done)) {
    $sql = 'UPDATE _members SET user_birthday_last = ?
        WHERE user_id IN (??)';
    sql_query(sql_filter($sql, YEAR, implode(',', $done)));
}

_pre('Done. @ ' . implode(', ', $users), true);
