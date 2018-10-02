<?php namespace App;

class Auth {
    public $founder     = false;
    public $data        = [];
    private $error_list = [];
    private $fields;

    public function field($name, $set = false) {
        if (!$this->fields) {
            $this->fields = [
                'username'       => '',
                'email'          => '',
                'email_confirm'  => '',
                'key'            => '',
                'key_confirm'    => '',
                'gender'         => 0,
                'birthday_month' => 0,
                'birthday_day'   => 0,
                'birthday_year'  => 0,
                'tos'            => 0,
                'ref'            => 0
            ];
        }

        if ($set !== false) {
            $this->fields[$name] = $set;
        }

        return isset($this->fields[$name]) ? request_var($name, $this->fields[$name]) : '';
    }

    public function error($name, $value = false) {
        if ($value !== false) {
            $this->error_list[$name] = $value;
        }

        return isset($this->error_list[$name]);
    }

    public function errorList() {
        return $this->error_list;
    }

    public function hasError() {
        return count($this->error_list);
    }

    public function isInvited() {
        $code_invite = request_var('invite', '');

        if (empty($code_invite)) {
            return;
        }

        $sql = 'SELECT i.invite_email, m.user_email
            FROM _members_ref_invite i, _members m
            WHERE i.invite_code = ?
                AND i.invite_uid = m.user_id';
        if (!$invite_row = sql_fieldrow(sql_filter($sql, $code_invite))) {
            fatal_error();
        }

        $this->field('ref', $invite_row['user_email']);
        $this->field('email', $invite_row['invite_email']);

        return;
    }

    public function url($name, $execute = false) {
        $map = [
            'in'  => 'signin',
            'out' => 'signout',
            'up'  => 'signup',
            'r'   => 'recover'
        ];

        if (isset($map[$name])) {
            $method = $map[$name];

            return $this->$method();
        }

        return false;
    }

    public function action() {
        global $user;

        if (empty($user->data)) {
            $user->init(false);
        }
        if (empty($user->lang)) {
            $user->setup();
        }

        if ($user->is('bot')) {
            redirect();
        }

        $this->isInvited();

        $action = request_var('mode', '');
        $status = $this->url($action);

        if (!$status) {
            $this->form();
        }

        return;
    }

    public function signout() {
        global $user;

        if ($user->is('member')) {
            $user->session_kill();
        }

        redirect();
    }

    public function signup() {
        global $user;

        if ($user->is('member')) {
            redirect(s_link('my profile'));
        }

        $code = request_var('code', '');

        if (!empty($code)) {
            if (!preg_match('#([a-z0-9]+)#is', $code)) {
                fatal_error();
            }

            $sql = 'SELECT c.*, m.user_id, m.username, m.username_base, m.user_email
                FROM _crypt_confirm c, _members m
                WHERE c.crypt_code = ?
                    AND c.crypt_userid = m.user_id';
            if (!$crypt_data = sql_fieldrow(sql_filter($sql, $code))) {
                fatal_error();
            }

            $user_id = $crypt_data['user_id'];

            $sql = 'UPDATE _members SET user_type = ?
                WHERE user_id = ?';
            sql_query(sql_filter($sql, USER_NORMAL, $user_id));

            $sql = 'DELETE FROM _crypt_confirm
                WHERE crypt_code = ?
                    AND crypt_userid = ?';
            sql_query(sql_filter($sql, $code, $user_id));

            $emailer = new emailer();

            $emailer->from('info');
            $emailer->use_template('user_welcome_confirm');
            $emailer->email_address($crypt_data['user_email']);

            $emailer->assign_vars([
                'USERNAME' => $crypt_data['username']
            ]);
            $emailer->send();
            $emailer->reset();

            $user->session_create($user_id, 0);

            //
            if (empty($user->data)) {
                $user->init();
            }
            if (empty($user->lang)) {
                $user->setup();
            }

            $custom_vars = [
                'S_REDIRECT'    => '',
                'MESSAGE_TITLE' => lang('information'),
                'MESSAGE_TEXT'  => lang('membership_added_confirm')
            ];
            page_layout('INFORMATION', 'message', $custom_vars);
        }

        if (_button()) {
            if (!$this->field('username')) {
                $this->error('username', 'EMPTY_USERNAME');
            } else {
                $len_username = strlen($this->field('username'));
                $check_len = ($len_username < 2) || ($len_username > 20);

                if ($check_len || !get_username_base($this->field('username'), true)) {
                    $this->error('username', 'USERNAME_INVALID');
                }

                if (!$this->hasError()) {
                    $result = validate_username($this->field('username'));
                    if ($result['error']) {
                        $this->error('username', $result['error_msg']);
                    }
                }

                if (!$this->hasError()) {
                    $this->field('username_base', get_username_base($this->field('username')));

                    $sql = 'SELECT user_id
                        FROM _members
                        WHERE username_base = ?';
                    if (sql_field(sql_filter($sql, $this->field('username_base')), 'user_id', 0)) {
                        $this->error('username', 'USERNAME_TAKEN');
                    }
                }

                if (!$this->hasError()) {
                    $sql = 'SELECT ub
                        FROM _artists
                        WHERE subdomain = ?';
                    if (sql_field(sql_filter($sql, $this->field('username_base')), 'ub', 0)) {
                        $this->error('username', 'USERNAME_TAKEN');
                    }
                }
            }

            if (!$this->field('email') || !$this->field('email_confirm')) {
                if (!$this->field('email')) {
                    $this->error('email', 'EMPTY_EMAIL');
                }

                if (!$this->field('email_confirm')) {
                    $this->error('email_confirm', 'EMPTY_EMAIL_CONFIRM');
                }
            } else {
                if ($this->field('email') == $this->field('email_confirm')) {
                    $result = validate_email($this->field('email'));

                    if ($result['error']) {
                        $this->error('email', $result['error_msg']);
                    }
                } else {
                    $this->error('email', 'EMAIL_MISMATCH');
                    $this->error('email_confirm', 'EMAIL_MISMATCH');
                }
            }

            if ($this->field('key') && $this->field('key_confirm')) {
                if ($this->field('key') != $this->field('key_confirm')) {
                    $this->error('key', 'PASSWORD_MISMATCH');
                } elseif (strlen($this->field('key')) > 32) {
                    $this->error('key', 'PASSWORD_LONG');
                }
            } else {
                if (!$this->field('key')) {
                    $this->error('key', 'EMPTY_PASSWORD');
                } elseif (!$this->field('key_confirm')) {
                    $this->error('key_confirm', 'EMPTY_PASSWORD_CONFIRM');
                }
            }

            if (!$this->field('birthday_month') || !$this->field('birthday_day') || !$this->field('birthday_year')) {
                $this->error('birthday', 'EMPTY_BIRTH_MONTH');
            }

            if (!$this->field('tos')) {
                $this->error('tos', 'AGREETOS_ERROR');
            }

            if (!$this->hasError()) {
                $this->field('country', 90);

                $birthday = '';
                foreach (w('year month day') as $row) {
                    $birthday .= leading_zero($this->field('birthday_' . $row));
                }

                $this->field('birthday', $birthday);

                $member_data = [
                    'user_type'         => USER_INACTIVE,
                    'user_active'       => 1,
                    'username'          => $this->field('username'),
                    'username_base'     => $this->field('username_base'),
                    'user_password'     => HashPassword($this->field('key')),
                    'user_regip'        => $user->ip,
                    'user_session_time' => 0,
                    'user_lastpage'     => '',
                    'user_lastvisit'    => time(),
                    'user_regdate'      => time(),
                    'user_level'        => 0,
                    'user_posts'        => 0,
                    'userpage_posts'    => 0,
                    'user_points'       => 0,
                    'user_timezone'     => config('board_timezone'),
                    'user_dst'          => config('board_dst'),
                    'user_lang'         => config('default_lang'),
                    'user_dateformat'   => config('default_dateformat'),
                    'user_country'      => (int) $this->field('country'),
                    'user_rank'         => 0,
                    'user_avatar'       => '',
                    'user_avatar_type'  => 0,
                    'user_email'        => $this->field('email'),
                    'user_lastlogon'    => 0,
                    'user_totaltime'    => 0,
                    'user_totallogon'   => 0,
                    'user_totalpages'   => 0,
                    'user_gender'       => $this->field('gender'),
                    'user_birthday'     => (string) $this->field('birthday'),
                    'user_mark_items'   => 0,
                    'user_topic_order'  => 0,
                    'user_email_dc'     => 1,
                    'user_refop'        => 0,
                    'user_refby'        => $this->field('ref')
                ];
                $user_id = sql_insert('members', $member_data);

                set_config('max_users', (int) config('max_users') + 1);

                // Confirmation code
                $verification_code = md5(unique_id());

                $insert = [
                    'crypt_userid' => $user_id,
                    'crypt_code'   => $verification_code,
                    'crypt_time'   => $user->time
                ];
                sql_insert('crypt_confirm', $insert);

                // Emailer
                $emailer = new emailer();

                if ($this->field('ref')) {
                    $valid_ref = email_format($this->field('ref'));

                    if ($valid_ref) {
                        $sql = 'SELECT user_id
                            FROM _members
                            WHERE user_email = ?';
                        if ($ref_friend = sql_field(sql_filter($sql, $this->field('ref')), 'user_id', 0)) {
                            $sql_insert = [
                                'ref_uid'  => $user_id,
                                'ref_orig' => $ref_friend
                            ];
                            sql_insert('members_ref_assoc', $sql_insert);

                            $sql_insert = [
                                'user_id'     => $user_id,
                                'buddy_id'    => $ref_friend,
                                'friend_time' => time()
                            ];
                            sql_insert('members_friends', $sql_insert);
                        } else {
                            $invite_user = explode('@', $this->field('ref'));
                            $invite_code = substr(md5(unique_id()), 0, 6);

                            $sql_insert = [
                                'invite_code'  => $invite_code,
                                'invite_email' => $this->field('ref'),
                                'invite_uid'   => $user_id
                            ];
                            sql_insert('members_ref_invite', $sql_insert);

                            $emailer->from('info');
                            $emailer->use_template('user_invite');
                            $emailer->email_address($this->field('ref'));

                            $emailer->assign_vars([
                                'INVITED'    => $invite_user[0],
                                'USERNAME'   => $this->field('username'),
                                'U_REGISTER' => s_link('@my register a', $invite_code)
                            ]);
                            $emailer->send();
                            $emailer->reset();
                        }
                    }
                }

                // Send confirm email
                $emailer->from('info');
                $emailer->use_template('user_welcome');
                $emailer->email_address($this->field('email'));

                $emailer->assign_vars([
                    'USERNAME'   => $this->field('username'),
                    'U_ACTIVATE' => s_link('@signup', $verification_code)
                ]);
                $emailer->send();
                $emailer->reset();

                $custom_vars = [
                    'MESSAGE_TITLE' => lang('information'),
                    'MESSAGE_TEXT'  => lang('membership_added')
                ];
                page_layout('INFORMATION', 'message', $custom_vars);
            }
        }

        return;
    }

    public function signin() {
        global $user;

        if ($user->is('member') && !_button('admin')) {
            redirect();
        }

        if (_button('login') && (!$user->is('member') || _button('admin'))) {
            $username = request_var('username', '');
            $password = request_var('password', '');
            $ref      = request_var('ref', '');

            if (!empty($username) && !empty($password)) {
                $username_base = get_username_base($username);

                $sql = 'SELECT user_id, username, user_password, user_type, user_country,
                        user_avatar, user_location, user_gender, user_birthday
                    FROM _members
                    WHERE username_base = ?';
                if ($row = sql_fieldrow(sql_filter($sql, $username_base))) {
                    $exclude_type = [USER_INACTIVE];
                    $valid_password = ValidatePassword($password, $row['user_password']);

                    if ($valid_password && !in_array($row['user_type'], $exclude_type)) {
                        $user->session_create($row['user_id'], _button('admin'));

                        $ask_fill_profile = w('country location gender birthday avatar');

                        foreach ($ask_fill_profile as $row2) {
                            if (empty($row['user_' . $row2])) {
                                $ref = s_link('my', 'profile');
                                break;
                            }
                        }

                        if (empty($ref) && preg_match('#' . preg_quote(config('server_name')) . '/$#', $ref)) {
                            $ref = s_link('today');
                        }

                        redirect($ref);
                    }
                }
            }
        }

        return;
    }

    public function recover() {
        global $user;

        if ($user->is('member')) {
            redirect(s_link('my profile'));
        }

        $code = request_var('code', '');

        if (request_var('r', 0)) {
            redirect();
        }

        if (!empty($code)) {
            if (!preg_match('#([a-z0-9]+)#is', $code)) {
                fatal_error();
            }

            $sql = 'SELECT c.*, m.user_id, m.username, m.username_base, m.user_email
                FROM _crypt_confirm c, _members m
                WHERE c.crypt_code = ?
                    AND c.crypt_userid = m.user_id';
            if (!$crypt_data = sql_fieldrow(sql_filter($sql, $code))) {
                fatal_error();
            }

            if (_button()) {
                $password  = request_var('newkey', '');
                $password2 = request_var('newkey2', '');

                if (!empty($password)) {
                    if ($password === $password2) {
                        $crypt_password = HashPassword($password);

                        $sql = 'UPDATE _members SET user_password = ?
                            WHERE user_id = ?';
                        sql_query(sql_filter($sql, $crypt_password, $crypt_data['user_id']));

                        $sql = 'DELETE FROM _crypt_confirm
                            WHERE crypt_userid = ?';
                        sql_query(sql_filter($sql, $crypt_data['user_id']));

                        // Send email
                        $emailer = new emailer();

                        $emailer->from('info');
                        $emailer->use_template('user_confirm_passwd', config('default_lang'));
                        $emailer->email_address($crypt_data['user_email']);

                        $emailer->assign_vars([
                            'USERNAME'  => $crypt_data['username'],
                            'PASSWORD'  => $password,
                            'U_PROFILE' => s_link('@m', $crypt_data['username_base'])
                        ]);
                        $emailer->send();
                        $emailer->reset();

                        v_style([
                            'PAGE_MODE' => 'updated'
                        ]);
                    } else {
                        v_style([
                            'PAGE_MODE' => 'nomatch',
                            'S_CODE'    => $code
                        ]);
                    }
                } else {
                    v_style([
                        'PAGE_MODE' => 'nokey',
                        'S_CODE'    => $code
                    ]);
                }
            } else {
                v_style([
                    'PAGE_MODE' => 'verify',
                    'S_CODE'    => $code
                ]);
            }
        } elseif (_button()) {
            $email = request_var('address', '');
            if (empty($email) || !email_format($email)) {
                redirect();
            }

            $sql = 'SELECT *
                FROM _members
                WHERE user_email = ?
                    AND user_active = 1
                    AND user_type NOT IN (??, ??)
                    AND user_id NOT IN (
                        SELECT ban_userid
                        FROM _banlist
                    )';
            if (!$userdata = sql_fieldrow(sql_filter($sql, $email, USER_INACTIVE, USER_FOUNDER))) {
                fatal_error(404, 'El correo electr&oacute;nico no est&aacute; registrado.');
            }

            $emailer = new emailer();

            $verification_code = md5(unique_id());

            $sql = 'DELETE FROM _crypt_confirm
                WHERE crypt_userid = ?';
            sql_query(sql_filter($sql, $userdata['user_id']));

            $insert = [
                'crypt_userid' => $userdata['user_id'],
                'crypt_code'   => $verification_code,
                'crypt_time'   => $user->time
            ];
            sql_insert('crypt_confirm', $insert);

            // Send email
            $emailer->from('info');
            $emailer->use_template('user_activate_passwd', config('default_lang'));
            $emailer->email_address($userdata['user_email']);

            $emailer->assign_vars([
                'USERNAME'   => $userdata['username'],
                'U_ACTIVATE' => s_link('@signr', $verification_code)
            ]);
            $emailer->send();
            $emailer->reset();

            v_style([
                'PAGE_MODE' => 'submit'
            ]);

            // _style('reset_complete');
        }

        return;
    }

    public function form() {
        //
        // Signup data
        //
        showError($this->errorList());

        $months = [
            1  => 'January',
            2  => 'February',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        $format_option = '<option value="%s"%s>%s</option>';

        $select_gender = build_options([1 => 'MALE', 2 => 'FEMALE'], $this->field('gender'), function ($row) {
            return lang($row);
        });

        $select_birth_day = build_options(range(1, 31), $this->field('birthday_day'), false, true);

        $select_birth_month = build_options($months, $this->field('birthday_month'), function ($row) {
            return lang_key('datetime', $row);
        });

        $select_birth_year = build_options(range(YEAR - 5, YEAR - 100), $this->field('birthday_year'), false, true);

        $s_hidden = w();
        if (_button('admin')) {
            $s_hidden = ['admin' => 1];
        }

        $layout_vars = [
            'IS_NEED_AUTH'     => _button('admin'),
            'IS_LOGIN'         => _button('login'),
            'S_HIDDEN_FIELDS'  => s_hidden($s_hidden),
            'V_TOS'            => checked($this->field('tos'), true),

            'U_SIGNIN'         => s_link('signin'),
            'U_SIGNUP'         => s_link('signup'),
            'U_SIGNOUT'        => s_link('signout'),
            'U_PASSWORD'       => s_link('signr'),

            'V_USERNAME'       => $this->field('username'),
            'V_KEY'            => $this->field('key'),
            'V_KEY_CONFIRM'    => $this->field('key_confirm'),
            'V_EMAIL'          => $this->field('email'),
            'V_REFBY'          => $this->field('refby'),

            'V_GENDER'         => $select_gender,
            'V_BIRTHDAY_DAY'   => $select_birth_day,
            'V_BIRTHDAY_MONTH' => $select_birth_month,
            'V_BIRTHDAY_YEAR'  => $select_birth_year
        ];

        if (!isset_template_var('PAGE_MODE')) {
            $layout_vars['PAGE_MODE'] = '';
        }

        foreach ($this->fields as $name => $v) {
            $layout_vars['E_' . $name] = $this->error($name);
        }

        if (_button('login')) {
            $ref = request_var('ref', '');

            _style('error', [
                'LASTPAGE' => $ref ?: s_link()
            ]);
        }

        return page_layout('LOGIN2', 'login', $layout_vars);
    }

    //
    // @ $member_id
    //
    public function query($module = false, $member_id = false) {
        global $user;

        if ($member_id === false) {
            $member_id = $user->d('user_id');

            if ($user->is('founder')) {
                return true;
            }
        }

        if (!isset($this->data[$member_id])) {
            $sql = 'SELECT *
                FROM _auth_control
                WHERE member_id = ?';
            if ($row = sql_fieldrow(sql_filter($sql, $member_id))) {
                foreach ($row as $k => $v) {
                    if (preg_match('#^a_#', $k)) {
                        $this->data[$member_id][$k] = unserialize(_decode($v));
                    }
                }
            }
        }

        $member = isset($this->data[$member_id]);
        $member = $member && is_array($this->data[$member_id]) && count($this->data[$member_id]);

        if ($member || $user->is('founder')) {
            if ($module !== false && empty($this->data[$member_id]['a_' . $module])) {
                return false;
            }

            return true;
        }

        return false;
    }

    public function option($ary, $member_id = false) {
        global $user;

        if ($member_id === false) {
            $member_id = $user->d('user_id');

            if ($user->is('founder')) {
                return true;
            }
        }

        if (!isset($this->data[$member_id]) || !is_array($ary)) {
            return;
        }

        $a = '';
        foreach ($ary as $i => $k) {
            if (!$i) {
                $k = 'a_' . $k;
            }

            $a .= "['" . $k . "']";
        }

        eval('$b = isset($this->data[$member_id]' . $a . ')');
        return $b;
    }

    public function forum($type, $forum_id, $f_access = false) {
        global $user;

        switch ($type) {
            case AUTH_ALL:
                $a_sql = 'a.auth_view, a.auth_read, a.auth_post, a.auth_reply, ';
                $a_sql .= 'a.auth_announce, a.auth_vote, a.auth_pollcreate';

                $auth_fields = [
                    'auth_view',
                    'auth_read',
                    'auth_post',
                    'auth_reply',
                    'auth_announce',
                    'auth_vote',
                    'auth_pollcreate'
                ];
                break;
            case AUTH_VIEW:
                $a_sql = 'a.auth_view';
                $auth_fields = ['auth_view'];
                break;
            case AUTH_READ:
                $a_sql = 'a.auth_read';
                $auth_fields = ['auth_read'];
                break;
            case AUTH_POST:
                $a_sql = 'a.auth_post';
                $auth_fields = ['auth_post'];
                break;
            case AUTH_REPLY:
                $a_sql = 'a.auth_reply';
                $auth_fields = ['auth_reply'];
                break;
            case AUTH_ANNOUNCE:
                $a_sql = 'a.auth_announce';
                $auth_fields = ['auth_announce'];
                break;
            case AUTH_POLLCREATE:
                $a_sql = 'a.auth_pollcreate';
                $auth_fields = ['auth_pollcreate'];
                break;
            case AUTH_VOTE:
                $a_sql = 'a.auth_vote';
                $auth_fields = ['auth_vote'];
                break;
        }

        //
        // If f_access has been passed, or auth is needed to return an array of forums
        // then we need to pull the auth information on the given forum (or all forums)
        //
        if ($f_access === false) {
            $forum_match_sql = ($forum_id != AUTH_LIST_ALL) ? sql_filter('WHERE a.forum_id = ?', $forum_id) : '';
            $sql_fetchrow = ($forum_id != AUTH_LIST_ALL) ? 'sql_fieldrow' : 'sql_rowset';

            $sql = 'SELECT a.forum_id, ' . $a_sql . '
                FROM _forums a
                ' . $forum_match_sql;
            if (!$f_access = $sql_fetchrow($sql)) {
                return w();
            }
        }

        //
        // If the user isn't logged on then all we need do is check if the forum
        // has the type set to ALL, if yes they are good to go, if not then they
        // are denied access
        //
        $u_access = w();
        if ($user->is('member')) {
            $forum_match_sql = ($forum_id != AUTH_LIST_ALL) ? sql_filter('AND a.forum_id = ?', $forum_id) : '';

            $sql = 'SELECT a.forum_id, ' . $a_sql . ', a.auth_mod
                FROM _auth_access a, _members_group ug
                WHERE ug.user_id = ?
                    AND ug.user_pending = 0
                    AND a.group_id = ug.group_id
                    ' . $forum_match_sql;
            $result = sql_rowset(sql_filter($sql, $user->d('user_id')));

            foreach ($result as $row) {
                if ($forum_id != AUTH_LIST_ALL) {
                    $u_access[] = $row;
                } else {
                    $u_access[$row['forum_id']][] = $row;
                }
            }
        }

        //
        // If the user is logged on and the forum type is either ALL or REG then the user has access
        //
        // If the type if ACL, USER_MOD or USER_ADMIN then we need to see if the user has specific permissions
        // to do whatever it is they want to do ... to do this we pull relevant information for the
        // user (and any groups they belong to)
        //
        // Now we compare the users access level against the forums. We assume here that a moderator
        // and admin automatically have access to an ACL forum, similarly we assume admins meet an
        // auth requirement of USER_MOD
        //

        $this->founder = $user->is('founder');

        $auth_user = w();
        foreach ($auth_fields as $a_key) {
            if ($forum_id != AUTH_LIST_ALL) {
                $value = $f_access[$a_key];

                $custom_mod = forum_for_team($forum_id);

                switch ($value) {
                    case AUTH_ALL:
                        $auth_user[$a_key] = true;
                        $auth_user[$a_key . '_type'] = lang('auth_anonymous_users');
                        break;
                    case AUTH_REG:
                        $auth_user[$a_key] = $user->is('member');
                        $auth_user[$a_key . '_type'] = lang('auth_registered_users');
                        break;
                    case AUTH_ACL:
                        $mod = false;
                        if ($user->is('member')) {
                            $mod = $this->checkUser(AUTH_ACL, $a_key, $u_access, $custom_mod);
                        }

                        $auth_user[$a_key] = $mod;
                        $auth_user[$a_key . '_type'] = lang('auth_users_granted_access');
                        break;
                    case AUTH_MOD:
                        $auth_user[$a_key] = $user->is($custom_mod);
                        $auth_user[$a_key . '_type'] = lang('auth_moderators');
                        break;
                    case AUTH_ADMIN:
                        $auth_user[$a_key] = $this->founder;
                        $auth_user[$a_key . '_type'] = lang('auth_administrators');
                        break;
                    default:
                        $auth_user[$a_key] = false;
                        break;
                }
            } else {
                for ($k = 0, $end = sizeof($f_access); $k < $end; $k++) {
                    $value = $f_access[$k][$a_key];
                    $f_forum_id = $f_access[$k]['forum_id'];

                    $custom_mod = forum_for_team($forum_id);

                    switch ($value) {
                        case AUTH_ALL:
                            $auth_user[$f_forum_id][$a_key] = true;
                            $auth_user[$f_forum_id][$a_key . '_type'] = lang('auth_anonymous_users');
                            break;
                        case AUTH_REG:
                            $auth_user[$f_forum_id][$a_key] = $user->is('member');
                            $auth_user[$f_forum_id][$a_key . '_type'] = lang('auth_registered_users');
                            break;
                        case AUTH_ACL:
                            if (!isset($u_access[$f_forum_id])) {
                                $u_access[$f_forum_id] = [];
                            }

                            $mod = false;
                            if ($user->is('member')) {
                                $mod = $this->checkUser(AUTH_ACL, $a_key, $u_access[$f_forum_id], $custom_mod);
                            }

                            $auth_user[$f_forum_id][$a_key] = $mod;
                            $auth_user[$f_forum_id][$a_key . '_type'] = lang('auth_users_granted_access');
                            break;
                        case AUTH_MOD:
                            $auth_user[$f_forum_id][$a_key] = $user->is($custom_mod);
                            $auth_user[$f_forum_id][$a_key . '_type'] = lang('auth_moderators');
                            break;
                        case AUTH_ADMIN:
                            $auth_user[$f_forum_id][$a_key] = $this->founder;
                            $auth_user[$f_forum_id][$a_key . '_type'] = lang('auth_administrators');
                            break;
                        default:
                            $auth_user[$f_forum_id][$a_key] = false;
                            break;
                    }
                }
            }
        }

        //
        // Is user a moderator?
        //
        if ($forum_id != AUTH_LIST_ALL) {
            $custom_mod = forum_for_team($forum_id);

            $auth_user['auth_mod'] = $user->is('member') ? $user->is($custom_mod) : false;
        } else {
            for ($k = 0, $end = sizeof($f_access); $k < $end; $k++) {
                $f_forum_id = $f_access[$k]['forum_id'];
                $custom_mod = forum_for_team($forum_id);

                $mod = false;
                if ($user->is('member') && isset($u_access[$f_forum_id])) {
                    $mod = $this->checkUser(AUTH_MOD, 'auth_mod', $u_access[$f_forum_id], $custom_mod);
                }

                $auth_user[$f_forum_id]['auth_mod'] = $mod;
            }
        }

        return $auth_user;
    }

    public function checkUser($type, $key, $u_access, $custom_mod) {
        global $user;

        $auth_user = 0;

        if (sizeof($u_access)) {
            for ($j = 0, $end = sizeof($u_access); $j < $end; $j++) {
                $result = 0;
                switch ($type) {
                    case AUTH_ACL:
                        $result = $u_access[$j][$key];
                        // Continue
                    case AUTH_MOD:
                        $result = $result || $user->is($custom_mod);
                        // Continue
                    case AUTH_ADMIN:
                        $result = $result || $this->founder;
                        break;
                }

                $auth_user = $auth_user || $result;
            }
        } else {
            $auth_user = $this->founder;
        }

        return $auth_user;
    }
}
