<?php
namespace App;

class Session {
    public $session_id = '';
    public $cookie_data = array();
    public $data = array();
    public $browser = '';
    public $ip = '';
    public $page = '';
    public $time = 0;

    public function init($update_page = true, $bypass_empty_ip = false) {
        $this->time    = time();
        $this->browser = v_server('HTTP_USER_AGENT');
        $this->page    = _page();
        $this->ip      = htmlspecialchars(get_real_ip());
        $site_cookie   = config('cookie_name');

        if (empty($this->ip) && !$bypass_empty_ip) {
            fatal_error('600');
        }

        if (!empty($this->ip) && $bypass_empty_ip) {
            //redirect(s_link());
        }

        $this->cookie_data = w();
        if (isset($_COOKIE[$site_cookie . '_sid']) || isset($_COOKIE[$site_cookie . '_u'])) {
            $this->cookie_data['u'] = request_var($site_cookie . '_u', 0);
            $this->session_id       = request_var($site_cookie . '_sid', '');
        }

        // Is session_id is set
        if (!empty($this->session_id)) {
            $sql = 'SELECT m.*, s.*
                FROM _sessions s, _members m
                WHERE s.session_id = ?
                    AND m.user_id = s.session_user_id';
            $this->data = sql_fieldrow(sql_filter($sql, $this->session_id));

            // Did the session exist in the DB?
            if (isset($this->data['user_id'])) {
                $s_ip = implode('.', array_slice(explode('.', $this->data['session_ip']), 0, 4));
                $u_ip = implode('.', array_slice(explode('.', $this->ip), 0, 4));

                if ($this->data['session_browser'] == $this->browser) {
                    // Only update session DB a minute or so after last update or if page changes
                    if ($this->time - $this->data['session_time'] > 60 || $this->data['session_page'] != $this->page) {
                        $sql_update = array(
                            'session_time' => $this->time
                        );

                        if ($update_page) {
                            $sql_update['session_page'] = $this->page;
                        }

                        $sql = 'UPDATE _sessions SET ??
                            WHERE session_id = ?';
                        sql_query(sql_filter($sql, sql_build('UPDATE', $sql_update), $this->session_id));
                    }

                    if ($update_page) {
                        $this->data['session_page'] = $this->page;
                    }

                    // Ultimately to be removed
                    $uid = 'is_member';

                    $this->data[$uid]         = ($this->data['user_id'] != 1);
                    $this->data['is_bot']     = (!$this->data[$uid] && $this->data['user_id'] != 1);
                    $this->data['is_founder'] = false;

                    if ($this->data[$uid] && $this->data['user_type'] == USER_FOUNDER && !$this->data['is_bot']) {
                        $this->data['is_founder'] = true;
                    }

                    return true;
                }
            }
        }

        // If we reach here then no (valid) session exists. So we'll create a new one
        return $this->session_create(false, false, $update_page);
    }

    /**
    * Create a new session
    *
    * If upon trying to start a session we discover there is nothing existing we
    * jump here. Additionally this method is called directly during login to regenerate
    * the session for the specific user. In this method we carry out a number of tasks;
    * garbage collection, (search)bot checking, banned user comparison. Basically
    * though this method will result in a new session for a specific user.
    */
    public function session_create($user_id = false, $set_admin = false, $update_page = true, $is_inactive = false) {
        $this->data = w();

        if (strpos($this->page, 'signin')) {
            $this->page = '';
        }

        // Garbage collection ... remove old sessions updating user information
        // if necessary. It means (potentially) 11 queries but only infrequently
        if ($this->time > (config('session_last_gc') + config('session_gc'))) {
            $this->session_gc();
        }

        /**
        * Here we do a bot check. We loop through the list of bots defined by
        * the admin and see if we have any useragent and/or IP matches. If we
        * do, this is a bot, act accordingly
        */
        $bot = false;
        $active_bots = w();
        obtain_bots($active_bots);

        foreach ($active_bots as $row) {
            if ($row['bot_agent'] && strpos(strtolower($this->browser), strtolower($row['bot_agent'])) !== false) {
                $bot = $row['user_id'];
            }

            // If ip is supplied, we will make sure the ip is matching too...
            if ($row['bot_ip'] && ($bot || !$row['bot_agent'])) {
                // Set bot to false, then we only have to set it to true if it is matching
                $bot = false;

                foreach (explode(',', $row['bot_ip']) as $bot_ip) {
                    if (strpos($this->ip, $bot_ip) === 0) {
                        $bot = (int) $row['user_id'];
                        break;
                    }
                }
            }

            if ($bot) {
                break;
            }
        }

        // If we've been passed a user_id we'll grab data based on that
        if ($user_id !== false) {
            $this->cookie_data['u'] = $user_id;

            $sql = 'SELECT *
                FROM _members
                WHERE user_id = ?
                    AND user_type <> ?';
            $this->data = sql_fieldrow(sql_filter($sql, $this->cookie_data['u'], USER_INACTIVE));
        }

        // If no data was returned one or more of the following occured:
        // User does not exist
        // User is inactive
        // User is bot
        if (!sizeof($this->data) || !is_array($this->data)) {
            $this->cookie_data['u'] = ($bot) ? $bot : GUEST;

            $sql = 'SELECT *
                FROM _members
                WHERE user_id = ?';
            $this->data = sql_fieldrow(sql_filter($sql, $this->cookie_data['u']));
        }

        $s_lastvisit = 'session_last_visit';

        if ($this->data['user_id'] != 1) {
            $sql = 'SELECT session_time, session_id
                FROM _sessions
                WHERE session_user_id = ?
                ORDER BY session_time DESC
                LIMIT 1';
            if ($sdata = sql_fieldrow(sql_filter($sql, $this->data['user_id']))) {
                $this->data = array_merge($sdata, $this->data);
                unset($sdata);
                $this->session_id = $this->data['session_id'];
            }

            if (isset($this->data['session_time']) && $this->data['session_time']) {
                $this->data[$s_lastvisit] = $this->data['session_time'];
            } else {
                $this->data[$s_lastvisit] = $this->data['user_lastvisit'] ? $this->data['user_lastvisit'] : $this->time;
            }
        } else {
            $this->data[$s_lastvisit] = $this->time;
        }

        // At this stage we should have a filled data array, defined cookie u and k data.
        // data array should contain recent session info if we're a real user and a recent
        // session exists in which case session_id will also be set

        // Is user banned? Are they excluded? Won't return on ban, exists within method
        // @todo Change to !$this->data['user_type'] & USER_FOUNDER && !$this->data['user_type'] & USER_BOT in time
        // Fix 1 day problem
        //if ($this->data['user_type'] != USER_FOUNDER) {
            //$this->check_ban();
        //}

        //
        // Do away with ultimately?
        $uid = 'is_member';

        $this->data[$uid]         = (!$bot && $this->data['user_id'] != 1);
        $this->data['is_bot']     = $bot;
        $this->data['is_founder'] = false;

        if ($this->data[$uid] && $this->data['user_type'] == USER_FOUNDER && !$this->data['is_bot']) {
            $this->data['is_founder'] = true;
        }

        // Create or update the session
        $sql_ary = array(
            'session_user_id'    => (int) $this->data['user_id'],
            'session_start'      => (int) $this->time,
            'session_last_visit' => (int) $this->data['session_last_visit'],
            'session_time'       => (int) $this->time,
            'session_browser'    => (string) $this->browser,
            'session_ip'         => (string) $this->ip,
            'session_admin'      => ($set_admin) ? 1 : 0
        );

        if ($update_page) {
            $sql_ary['session_page'] = (string) $this->page;
            $this->data['session_page'] = $sql_ary['session_page'];
        }

        $sql = 'UPDATE _sessions SET ??
            WHERE session_id = ?';
        sql_query(sql_filter($sql, sql_build('UPDATE', $sql_ary), $this->session_id));

        if (!$this->session_id || !sql_affectedrows()) {
            $this->session_id = $this->data['session_id'] = md5(unique_id());

            $sql_ary['session_id'] = (string) $this->session_id;
            sql_insert('sessions', $sql_ary);
        }

        if (!$bot) {
            $cookie_expire = $this->time + 31536000;

            $this->set_cookie('u', $this->cookie_data['u'], $cookie_expire);
            $this->set_cookie('sid', $this->session_id, 0);

            if ($this->data['is_member']) {
                $this->register_ip();
            }

            unset($cookie_expire);
        }

        return true;
    }

    public function register_ip() {
        $insert = array(
            'log_user_id' => (int) $this->data['user_id'],
            'log_session' => $this->session_id,
            'log_ip'      => $this->ip,
            'log_agent'   => $this->browser,
            'log_time'    => (int) $this->time,
            'log_endtime' => 0
        );
        sql_insert('members_iplog', $insert);

        $sql = 'SELECT log_time
            FROM _members_iplog
            WHERE log_user_id = ?
            ORDER BY log_time DESC
            LIMIT 10, 1';
        if ($log_time = sql_field(sql_filter($sql, $this->data['user_id']), 'log_time', '')) {
            $sql = 'DELETE FROM _members_iplog
                WHERE log_time = ?';
            sql_query(sql_filter($sql, $log_time));
        }

        return;
    }

    /**
    * Kills a session
    *
    * This method does what it says on the tin. It will delete a pre-existing session.
    * It resets cookie information (destroying any autologin key within that cookie data)
    * and update the users information from the relevant session data. It will then
    * grab guest user information.
    */
    public function session_kill() {
        $sql = 'DELETE FROM _sessions
            WHERE session_id = ?
                AND session_user_id = ?';
        sql_query(sql_filter($sql, $this->session_id, $this->data['user_id']));

        if ($this->data['user_id'] != GUEST) {
            // Delete existing session, update last visit info first!
            $sql = 'UPDATE _members
                SET user_lastvisit = ?
                WHERE user_id = ?';
            sql_query(sql_filter($sql, $this->data['session_time'], $this->data['user_id']));

            $sql = 'UPDATE _members_iplog SET log_endtime = ?
                WHERE log_session = ?
                    AND log_user_id = ?';
            sql_query(sql_filter($sql, $this->time, $this->session_id, $this->data['user_id']));

            // Reset the data array
            $this->data = w();

            $sql = 'SELECT *
                FROM _members
                WHERE user_id = ?';
            $this->data = sql_fieldrow(sql_filter($sql, GUEST));
        }

        $cookie_expire = $this->time - 31536000;
        $this->set_cookie('u', '', $cookie_expire);
        $this->set_cookie('sid', '', $cookie_expire);
        unset($cookie_expire);

        $this->session_id = '';

        return true;
    }

    /**
    * Session garbage collection
    *
    * Effectively we are deleting any sessions older than an admin definable
    * limit. Due to the way in which we maintain session data we have to
    * ensure we update user data before those sessions are destroyed.
    * In addition this method removes autologin key information that is older
    * than an admin defined limit.
    */
    public function session_gc() {
        // Get expired sessions, only most recent for each user
        $sql = 'SELECT ANY_VALUE(session_id), session_user_id, session_page, MAX(session_time) AS recent_time
            FROM _sessions
            WHERE session_time < ?
            GROUP BY session_user_id, session_page
            LIMIT 5';
        $result = sql_rowset(sql_filter($sql, ($this->time - config('session_length'))));

        $del_user_id = '';
        $del_sessions = 0;

        foreach ($result as $row) {
            if ($row['session_user_id'] != GUEST) {
                $sql = 'UPDATE _members
                    SET user_lastvisit = ?, user_lastpage = ?
                    WHERE user_id = ?';
                sql_query(sql_filter($sql, $row['recent_time'], $row['session_page'], $row['session_user_id']));

                $sql = 'UPDATE _members_iplog SET log_endtime = ?
                    WHERE log_session = ?
                        AND log_user_id = ?';
                sql_query(sql_filter($sql, $row['recent_time'], $row['session_id'], $row['session_user_id']));
            }

            $del_user_id .= (($del_user_id != '') ? ', ' : '') . (int) $row['session_user_id'];
            $del_sessions++;
        }

        if ($del_user_id) {
            // Delete expired sessions
            $sql = 'DELETE FROM _sessions
                WHERE session_user_id IN (??)
                    AND session_time < ?';
            sql_query(sql_filter($sql, $del_user_id, ($this->time - config('session_length'))));
        }

        if ($del_sessions < 5) {
            // Less than 5 sessions, update gc timer ... else we want gc
            // called again to delete other sessions
            set_config('session_last_gc', $this->time);
        }

        return;
    }

    /**
    * Sets a cookie
    *
    * Sets a cookie of the given name with the specified data for the given length of time.
    */
    public function set_cookie($name, $value, $expires) {
        setAppCookie($name, $value, $expires);
    }

    /**
    * Check for banned user
    *
    * Checks whether the supplied user is banned by id, ip or email. If no parameters
    * are passed to the method pre-existing session data is used. This routine does
    * not return on finding a banned user, it outputs a relevant message and stops
    * execution.
    */
    public function check_ban($user_id = false, $user_ip = false, $user_email = false) {
        $user_id = ($user_id === false) ? $this->data['user_id'] : $user_id;
        $user_ip = ($user_ip === false) ? $this->ip : $user_ip;
        $user_email = ($user_email === false) ? $this->data['user_email'] : $user_email;

        $banned = false;

        $sql = 'SELECT ban_ip, ban_userid, ban_email, ban_exclude, ban_give_reason, ban_end
            FROM _banlist
            WHERE ban_end >= ?
                OR ban_end = 0';
        $result = sql_rowset(sql_filter($sql, time()));

        foreach ($result as $row) {
            $row['ban_ip']    = str_replace('*', '.*?', $row['ban_ip']);
            $row['ban_email'] = str_replace('*', '.*?', $row['ban_email']);

            $ban   = !empty($row['ban_userid']) && ((int) $row['ban_userid'] == $user_id);
            $ip    = !empty($row['ban_ip']) && preg_match('#^' . $row['ban_ip'] . '$#i', $user_ip);
            $email = !empty($row['ban_email']) && preg_match('#^' . $row['ban_email'] . '$#i', $user_email);


            if ($ban || $ip || $email) {
                $banned = empty($row['ban_exclude']);
                break;
            }
        }

        if ($banned) {
            //fatal_error();
            /*
            // Determine which message to output
            $till_date = (!empty($row['ban_end'])) ? $this->format_date($row['ban_end']) : '';
            $message = (!empty($row['ban_end'])) ? 'BOARD_BAN_TIME' : 'YOU_ARE_BANNED';

            $email = '<a href="mailto:' . config('board_contact') . '">';
            $reason = sprintf($this->lang['BOARD_BAN_REASON'], $row['ban_show_reason']);

            $message = sprintf($this->lang[$message], $till_date, $email, '</a>');
            // More internal HTML ...
            $message .= (!empty($row['ban_show_reason'])) ? '<br /><br />' . $reason : '';
            trigger_error($message);
            */
        }

        return;
    }

    public function d($d = false, $v = false) {
        if ($d === false) {
            if ($v !== false) {
                $v['is_member']  = ($v['user_id'] != 1);
                $v['is_bot']     = false;
                $v['is_founder'] = ($v['is_member'] && $v['user_type'] == USER_FOUNDER && !$v['is_bot']) ? true : false;

                $this->data = $v;
            }

            $r = $this->data;
            unset($r['user_password']);
            return $r;
        }

        if (!preg_match('/^user_/', $d) && !isset($this->data[$d])) {
            $d = 'user_' . $d;
        }

        if ($v !== false) {
            $this->data[$d] = $v;
        }

        return (isset($this->data[$d])) ? $this->data[$d] : false;
    }
}
