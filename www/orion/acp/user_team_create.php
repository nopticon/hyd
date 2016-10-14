<?php
namespace App;

class __user_team_create extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function _home() {
        global $config, $user, $cache;

        if (!_button()) {
            $sql = 'SELECT *
                FROM _team
                ORDER BY team_name';
            $result = sql_rowset($sql);

            foreach ($result as $i => $row) {
                if (!$i) {
                    _style('team');
                }

                _style(
                    'team.row',
                    array(
                        'TEAM_ID' => $row['team_id'],
                        'TEAM_NAME' => $row['team_name']
                    )
                );
            }

            return false;
        }

        $team = request_var('team', 0);
        $username = request_var('username', '');
        $username = get_username_base($username);
        $realname = request_var('realname', '');
        $ismod = request_var('ismod', 0);

        $sql = 'SELECT *
            FROM _team
            WHERE team_id = ?';
        if (!$teamd = sql_fieldrow(sql_filter($sql, $team))) {
            fatal_error();
        }

        $sql = 'SELECT user_id, username
            FROM _members
            WHERE username_base = ?';
        if (!$userdata = sql_fieldrow(sql_filter($sql, $username))) {
            fatal_error();
        }

        $insert = true;

        $sql = 'SELECT *
            FROM _team_members
            WHERE team_id = ?
                AND member_id = ?';
        if ($row = sql_fieldrow(sql_filter($sql, $team, $userdata['user_id']))) {
            if ($ismod && !$row['member_mod']) {
                $sql = 'UPDATE _team_members SET member_mod = 1
                    WHERE team_id = ?
                        AND member_id = ?';
                sql_query(sql_filter($sql, $team, $userdata['user_id']));
            }

            $insert = false;
        }

        if ($insert) {
            $insert = array(
                'team_id' => $team,
                'member_id' => $userdata['user_id'],
                'real_name' => $realname,
                'member_mod' => $ismod
            );
            sql_insert('team_members', $insert);
        }

        $cache->delete('team team_all team_members team_mod team_radio team_colab');

        $format = 'El usuario <strong>%s</strong> fue agregado al grupo <strong>%s</strong>.';
        $message = sprintf($format, $userdata['username'], $teamd['team_name']);

        return _pre($message, true);
    }
}
