<?php namespace App;

class __user_view extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        if (!_button()) {
            return false;
        }

        $userid   = request_var('uid', 0);
        $username = request_var('username', '');
        $email    = request_var('email', '');

        if (empty($username) && empty($email) && !$userid) {
            fatal_error();
        }

        if (!empty($email)) {
            $sql = 'SELECT *
                FROM _members
                WHERE user_email = ?';
            $sql = sql_filter($sql, $email);
        } elseif ($userid) {
            $sql = 'SELECT *
                FROM _members
                WHERE user_id = ?';
            $sql = sql_filter($sql, $userid);
        } else {
            $sql = 'SELECT *
                FROM _members
                WHERE username_base = ?';
            $sql = sql_filter($sql, get_username_base($username));
        }

        if (!$userdata = sql_fieldrow($sql)) {
            fatal_error();
        }

        foreach ($userdata as $k => $void) {
            if (preg_match('#\d+#is', $k)) {
                unset($userdata[$k]);
            }
        }

        return _pre($userdata, true);
    }
}
