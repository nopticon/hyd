<?php namespace App;

class __user_ban extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        if ($this->create()) {
            return;
        }

        return;
    }

    private function create() {
        $v = _request([
            'username' => ''
        ]);

        if (_empty($v)) {
            return;
        }

        $v->username = get_username_base($v->username);

        $sql = 'SELECT *
            FROM _members
            WHERE username_base = ?';
        if (!$result = sql_fieldrow(sql_filter($sql, $v->username))) {
            return;
        }

        if (create_ban_user($result['user_id'])) {
            echo 'El usuario ' . $result['username'] . ' fue bloqueado.';
        }

        return true;
    }
}
