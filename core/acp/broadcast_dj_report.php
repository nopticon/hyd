<?php
namespace App;

class __broadcast_dj_report extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        $sql = 'SELECT d.*, m.username, m.username_base
            FROM _radio_dj_log d, _members m
            WHERE d.log_uid = m.user_id
            ORDER BY log_time DESC';
        $result = sql_rowset($sql);

        foreach ($result as $i => $row) {
            if (!$i) {
                _style('report');
            }

            _style(
                'report.row',
                array(
                    'LINK' => s_link('m', $row['username_base']),
                    'NAME' => $row['username'],
                    'TIME' => $user->format_date($row['log_time'])
                )
            );
        }

        return;
    }
}
