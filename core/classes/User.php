<?php namespace App;

/**
* Base user class
*
* This is the overarching class which contains (through session extend)
* all methods utilised for user functionality during a session.
*/
class User extends Session {
    public $lang = array();
    public $help = array();
    public $theme = array();
    public $unr = array();
    public $date_format;
    public $timezone;
    public $dst;

    public $lang_name;
    public $lang_path;
    public $img_lang;

    public $keyoptions = array('viewimg' => 0, 'viewsigs' => 3, 'viewavatars' => 4);
    public $keyvalues = array();

    public function __construct() {
        return;
    }

    public function setup($lang_set = false, $style = false) {
        global $template, $auth, $cache;

        if ($this->data['user_id'] != GUEST) {
            $lang_path = ROOT . 'language/' . $this->data['user_lang'] . '/main.php';

            $this->lang_name = file_exists($lang_path) ? $this->data['user_lang'] : config('default_lang');
            $this->lang_path = ROOT.'language/' . $this->lang_name . '/';

            $this->date_format = $this->data['user_dateformat'];
            $this->timezone    = $this->data['user_timezone'] * 3600;
            $this->dst         = $this->data['user_dst'] * 3600;
        } else {
            $this->lang_name   = config('default_lang');
            $this->lang_path   = ROOT.'language/' . $this->lang_name . '/';
            $this->date_format = config('default_dateformat');
            $this->timezone    = config('board_timezone') * 3600;
            $this->dst         = config('board_dst') * 3600;

            if ($accept_lang = v_server('HTTP_ACCEPT_LANGUAGE')) {
                $accept_lang_ary = explode(',', $accept_lang);
                foreach ($accept_lang_ary as $accept_lang) {
                    // Set correct format ... guess full xx_YY form
                    $accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
                    if (file_exists(ROOT.'language/' . $accept_lang . "/main.php")) {
                        $this->lang_name = $accept_lang;
                        $this->lang_path = ROOT.'language/' . $accept_lang . '/';
                        break;
                    } else {
                        // No match on xx_YY so try xx
                        $accept_lang = substr($accept_lang, 0, 2);
                        if (file_exists(ROOT . 'language/' . $accept_lang . "/main.php")) {
                            $this->lang_name = $accept_lang;
                            $this->lang_path = ROOT.'language/' . $accept_lang . '/';
                            break;
                        }
                    }
                }
            }
        }

        // We include common language file here to not load it every time a custom language file is included
        $lang = &$this->lang;
        if ((require_once($this->lang_path . 'main.php')) === false) {
            _pre("Language file " . $this->lang_path . "main.php couldn't be opened.", true);
        }

        $this->add_lang($lang_set);
        unset($lang_set);

        $template->set_template(ROOT.'template');

        // Is board disabled and user not an admin or moderator?
        // TODO
        // New ACL enabling board access while offline?
        if (config('site_disable') && $this->is('founder')) {
            status("503 Service Temporarily Unavailable");
            header("Retry-After: 3600");

            sql_close();

            echo exception('disabled');
            exit;
        }

        return;
    }

    // Add Language Items
    public function add_lang($lang_set) {
        if (is_array($lang_set)) {
            foreach ($lang_set as $key => $lang_file) {
                // Please do not delete this line.
                // We have to force the type here, else [array] language inclusion will not work
                $key = (string) $key;

                if (!is_array($lang_file)) {
                    $this->set_lang($this->lang, $this->help, $lang_file);
                } else {
                    $this->add_lang($lang_file);
                }
            }
            unset($lang_set);
        } elseif ($lang_set) {
            $this->set_lang($this->lang, $this->help, $lang_set);
        }

        return;
    }

    public function set_lang(&$lang, &$help, $lang_file) {
        if ((@include $this->lang_path . "$lang_file.php") === false) {
            trigger_error("Language file " . $this->lang_path . "$lang_file.php" . " couldn't be opened.");
        }
    }

    public function format_date($gmepoch = false, $format = false, $forcedate = false) {
        static $lang_dates, $midnight;

        if (empty($lang_dates)) {
            $lang_dates = array();
            if ($this->lang) {
                foreach ($this->lang['datetime'] as $match => $replace) {
                    $lang_dates[$match] = $replace;
                }
            }
        }

        if ($gmepoch === false) {
            $gmepoch = time();
        }

        $format = (!$format) ? $this->date_format : $format;

        $current_year = YEAR;
        $this_year    = date('Y', $gmepoch);

        if ($current_year == $this_year) {
            $format = str_replace(' Y', ((strpos($format, 'H') !== false) ? '\, ' : ''), $format);
        }

        if (!$midnight) {
            list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $this->timezone + $this->dst));
            $midnight = gmmktime(0, 0, 0, $m, $d, $y) - $this->timezone - $this->dst;
        }

        $time_check = (!($gmepoch > $midnight && !$forcedate) && !($gmepoch > $midnight - 86400 && !$forcedate));

        if (strpos($format, '|') === false || $time_check) {
            return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $this->timezone + $this->dst), $lang_dates);
        }

        if ($gmepoch > $midnight && !$forcedate) {
            $format = substr($format, 0, strpos($format, '|')) . '||' . substr(strrchr($format, '|'), 1);
            $format = strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $lang_dates);

            return str_replace('||', $this->lang['datetime']['TODAY'], $format);
        }

        if ($gmepoch > $midnight - 86400 && !$forcedate) {
            $format = substr($format, 0, strpos($format, '|')) . '||' . substr(strrchr($format, '|'), 1);
            $format = strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $lang_dates);

            return str_replace('||', $this->lang['datetime']['YESTERDAY'], $format);
        }
    }

    public function is($name, $user_id = false, $artist = false) {
        if (isset($this->data['is_' . $name])) {
            return $this->data['is_' . $name];
        }

        if ($user_id === false) {
            $user_id = $this->d('user_id');
        }

        $response = false;
        if ($this->is('member')) {
            $all = $this->_team_auth_list($name);
            $response = (is_array($all) && count($all)) ? in_array($user_id, $all) : $all;

            if ($name == 'artist' && $response && $artist !== false) {
                $sql = 'SELECT ub
                    FROM _artists_auth
                    WHERE ub = ?
                        AND user_id = ?';
                if (!sql_field(sql_filter($sql, $artist, $user_id), 'ub', 0)) {
                    $response = false;
                }
            }
        }

        return $response;
    }

    public function init_ranks() {
        global $cache;

        if (!$ranks = $cache->get('ranks')) {
            $sql = 'SELECT *
                FROM _ranks
                ORDER BY rank_special DESC, rank_min';
            $ranks = sql_rowset($sql);
            $cache->save('ranks', $ranks);
        }

        return $ranks;
    }

    /*
     * Auth types
     *
     * founder
     * mod
     * colab
     * colab_admin
     * radio
     * user
     * all
     *
     */

    public function _team_auth_list($mode = '') {
        global $cache;

        $response = false;

        switch ($mode) {
            case 'founder':
                if (!$response = $cache->get('team_founder')) {
                    $sql = 'SELECT DISTINCT user_id
                        FROM _members
                        WHERE user_type = ?
                        ORDER BY user_id';
                    $response = sql_rowset(sql_filter($sql, USER_FOUNDER), false, 'user_id');
                    $cache->save('team_founder', $response);
                }
            break;
            case 'artist':
                $response = $this->d('user_auth_control');
            break;
            case 'user':
                $response = true;
            break;
            break;
            case 'mod':
                if (!$response = $cache->get('team_mod')) {
                    $sql = 'SELECT DISTINCT member_id
                        FROM _team_members
                        WHERE member_mod = 1
                        ORDER BY member_id';
                    $response = sql_rowset($sql, false, 'member_id');
                    $cache->save('team_mod', $response);
                }
            break;
            case 'colab':
                if (!$response = $cache->get('team_colab')) {
                    $sql = 'SELECT DISTINCT member_id
                        FROM _team_members
                        WHERE team_id = 2
                        ORDER BY member_id';
                    $response = sql_rowset($sql, false, 'member_id');
                    $cache->save('team_colab', $response);
                }
            break;
            case 'colab_admin':
                if (!$response = $cache->get('team_colab_admin')) {
                    $sql = 'SELECT DISTINCT member_id
                        FROM _team_members
                        WHERE team_id = 6
                        ORDER BY member_id';
                    $response = sql_rowset($sql, false, 'member_id');
                    $cache->save('team_colab_admin', $response);
                }
            break;
            case 'radio':
                if (!$response = $cache->get('team_radio')) {
                    $sql = 'SELECT DISTINCT member_id
                        FROM _team_members
                        WHERE team_id = 4';
                    $response = sql_rowset($sql, false, 'member_id');
                    $cache->save('team_radio', $response);
                }
            break;
            case 'all':
            default:
                if (!$response = $cache->get('team_all')) {
                    $sql = 'SELECT DISTINCT member_id
                        FROM _team_members
                        ORDER BY member_id';
                    $response = sql_rowset($sql, false, 'member_id');
                    $cache->save('team_all', $response);
                }
            break;
        }

        if ($mode != 'founder' && is_array($response)) {
            if ($response_founder = $this->_team_auth_list('founder')) {
                $response = array_merge($response, $response_founder);
            }
        }

        return $response;
    }

    /*
    User Unread Messages (Message Center) Functions

    Who will be receive recently addded items,
    in their unread message center? If for some
    reason the item is deleted in original
    location, then will be removed here too.

    Auth:

    U    USERS                ADM
    A    ARTISTS                -
    AF    ARTISTS FANS        ADM / USER_MOD
    E    EVENTS                -
    N    NEWS                -
    NP    NEWS MESSAGES        ADM / USER_MOD / POSTERS
    P    POSTS                -
    D    DOWNLOADS            -
    C    ARTISTS MESSAGES    ADM / USER_MOD / USER_FAN
    M    DOWNLOADS MESSAGES    ADM / USER_MOD / USER_FAN
    W    WALLPAPERS / ART    -
    F    ARTISTS NEWS        -
    I    ARTISTS IMAGES        -
    */

    public function today_type($type) {
        global $cache;

        if (!is_numeric($type)) {
            if (!$types = $cache->get('today_type')) {
                $sql = 'SELECT type_alias, type_id
                    FROM _today_type
                    ORDER BY type_order';
                $types = $cache->save('today_type', sql_rowset($sql));
            }

            $type = isset($types[$type]) ? $types[$type] : $type;
        }

        return $type;
    }

    public function today_get() {
    }

    public function today_create($type, $list, $filter = false, $to = false) {
        static $from_lastvisit;

        if ($to !== false) {
            if ($to != $this->d('user_id')) {
                $type = $this->today_type($type);

                $sql = 'SELECT object_id
                    FROM _today_objects
                    WHERE object_bio = ?
                        AND object_type = ?
                        AND object_relation = ?';
                if (!sql_field(sql_filter($sql, $to, $type, $list), 'object_id', 0)) {
                    $sql_insert = array(
                        'object_bio'      => $to,
                        'object_type'     => $type,
                        'object_relation' => $list,
                        'object_time'     => time()
                    );
                    sql_insert('today_objects', $sql_insert);
                }
            }
            return;
        }

        if (!$from_lastvisit) {
            list($d, $m, $y) = explode(' ', gmdate('j n Y', time() - 15552000)); // (30 * 6) * 86400
            $from_lastvisit = gmmktime(0, 0, 0, $m, $d, $y);
        }
    }

    public function today_update($type, $list) {
    }

    public function today_delete() {
    }

    public function save_unread(
        $element,
        $item,
        $where_id = 0,
        $reply_to = 0,
        $reply_to_return = true,
        $update_rows = false
    ) {
        static $from_lastvisit;

        if (!$element || !$item || in_array($element, array(UH_EP, UH_NP, UH_W))) {
            return;
        }

        if ($reply_to) {
            if ($reply_to == $this->data['user_id']) {
                return;
            }

            $sql = 'SELECT user_id
                FROM _members_unread
                WHERE element = ?
                    AND item = ?
                    AND user_id = ?
                ORDER BY user_id';
            if (!sql_field(sql_filter($sql, $element, $item, $reply_to), 'user_id', 0)) {
                $this->insert_unread($reply_to, $element, $item);
            }

            if ($reply_to_return) {
                return;
            }
        }

        if (!$from_lastvisit) {
            list($d, $m, $y) = explode(' ', gmdate('j n Y', time() - 15552000)); // (30 * 6) * 86400
            $from_lastvisit = gmmktime(0, 0, 0, $m, $d, $y);
        }

        $sql_in = w();
        switch ($element) {
            case UH_AF:
                $sql = 'SELECT m.user_id
                    FROM _artists_auth a, _members m
                    WHERE a.ub = ?
                        AND a.user_id = m.user_id
                        AND m.user_id <> ?
                    ORDER BY m.user_id';
                $sql = sql_filter($sql, $where_id, $this->data['user_id']);
                break;
            case UH_C:
            case UH_M:
                $sql_in = w();

                $sql = 'SELECT m.user_id
                    FROM _artists_auth a, _members m
                    WHERE a.ub = ?
                        AND a.user_id = m.user_id';
                $result = sql_rowset(sql_filter($sql, $where_id));

                foreach ($result as $row) {
                    $sql_in[] = $row['user_id'];
                }

                $sql = 'SELECT u.user_id
                    FROM _artists_fav f, _artists b, _members u
                    WHERE f.ub = ?
                        AND f.ub = b.ub
                        AND f.user_id = u.user_id
                    ORDER BY u.user_id';
                $result = sql_rowset(sql_filter($sql, $where_id));

                foreach ($result as $row) {
                    $sql_in[] = $row['user_id'];
                }

                $or = count($sql_in) ? ' OR user_id IN (' . implode(',', $sql_in) . ')' : '';

                $sql = 'SELECT user_id
                    FROM _members
                    WHERE (user_type IN (??, ??)' . $or . ')
                        AND user_type NOT IN (??)
                        AND user_id <> ?
                        AND user_lastvisit > ?
                    ORDER BY user_id';
                $sql = sql_filter(
                    $sql,
                    USER_FOUNDER,
                    USER_ADMIN,
                    USER_INACTIVE,
                    $this->data['user_id'],
                    $from_lastvisit
                );
                break;
            case UH_B:
                $sql = 'SELECT user_id
                    FROM _members
                    WHERE user_type NOT IN (??)
                        AND user_id = ?
                    ORDER BY user_id';
                $sql = sql_filter($sql, USER_INACTIVE, $where_id);
                break;
            case UH_U:
                $sql = 'SELECT user_id
                    FROM _members
                    WHERE user_type IN (??)
                        AND user_type NOT IN (??)
                        AND user_id <> ?
                        AND user_active = 1
                    ORDER BY user_id';
                $sql = sql_filter($sql, USER_FOUNDER, USER_INACTIVE, $item);
                break;
            default:
                $sql = 'SELECT user_id
                    FROM _members
                    WHERE user_type NOT IN (??)
                        AND user_id <> ?
                        AND user_lastvisit > 0
                        AND user_lastvisit > ?
                    ORDER BY user_id';
                $sql = sql_filter($sql, USER_INACTIVE, $this->data['user_id'], $from_lastvisit);
                break;
        }

        $sql_items = 'SELECT user_id
            FROM _members_unread
            WHERE element = ?
                AND item = ?
            ORDER BY user_id';
        $result = sql_rowset(sql_filter($sql_items, $element, $item));

        foreach ($result as $row) {
            $this->items[$row['user_id']] = true;
        }

        // Process members SQL
        $result = sql_rowset($sql);

        foreach ($result as $row) {
            if (!isset($this->items[$row['user_id']])) {
                $this->insert_unread($row['user_id'], $element, $item);
            }
        }

        if ($update_rows) {
            $this->update_unread($element, $item);
        }

        return;
    }

    public function insert_unread($uid, $cat, $el) {
        $row = array(
            'user_id'  => (int) $uid,
            'element'  => (int) $cat,
            'item'     => (int) $el,
            'datetime' => (int) $this->time
        );
        $sql = 'INSERT LOW_PRIORITY INTO _members_unread' . sql_build('INSERT', $row);
        sql_query($sql);
    }

    public function update_unread($cat, $el) {
        global $user;

        $sql = 'UPDATE _members_unread SET datetime = ?
            WHERE element = ?
                AND item = ?';
        sql_query(sql_filter($sql, $user->time, $cat, $el));
    }

    public function get_unread($element, $item) {
        if (!$this->data['is_member'] || !$element || !$item) {
            return false;
        }

        if (!sizeof($this->unr)) {
            $sql = 'SELECT element, item
                FROM _members_unread
                WHERE user_id = ?';
            $result = sql_rowset(sql_filter($sql, $this->data['user_id']));

            foreach ($result as $row) {
                $this->unr[$row['element']][$row['item']] = true;
            }
        }

        if (isset($this->unr[$element][$item]) && $this->unr[$element][$item]) {
            return true;
        }

        return false;
    }

    public function delete_unread($element, $item) {
        if (!$element || !$item) {
            return false;
        }

        $items = (is_array($item)) ? implode(',', array_map('intval', $item)) : $item;

        if (!empty($items)) {
            $sql = 'DELETE FROM _members_unread
                WHERE user_id = ?
                    AND element = ?
                    AND item IN (??)';
            sql_query(sql_filter($sql, $this->data['user_id'], $element, $items));

            return true;
        }

        return false;
    }

    public function delete_all_unread($element, $item) {
        if (!$element || !$item) {
            return false;
        }

        $items = (is_array($item)) ? implode(',', array_map('intval', $item)) : (int) $item;

        $sql = 'DELETE FROM _members_unread
            WHERE element = ?
                AND item IN (??)';
        sql_query(sql_filter($sql, $element, $items));

        return true;
    }

    //
    // POINTS SYSTEM
    //
    public function points_add($n, $uid = false) {
        global $user;

        if ($uid === false) {
            $uid = $this->data['user_id'];
            $block = $this->data['user_block_points'];
        } else {
            $sql = 'SELECT user_block_points
                FROM _members
                WHERE user_id = ?';
            if ($row = sql_fieldrow(sql_filter($sql, $uid))) {
                $block = $row['user_block_points'];
            }
        }

        if ($block) {
            return;
        }

        $sql = 'UPDATE _members
            SET user_points = user_points + ??
            WHERE user_id = ?';
        sql_query(sql_filter($sql, $n, $uid));

        return;
    }

    public function points_remove($n, $uid = false) {
        if ($uid === false) {
            $uid = $this->data['user_id'];
        }

        $sql = 'UPDATE _members
            SET user_points = user_points - ??
            WHERE user_id = ?';
        sql_query(sql_filter($sql, $n, $uid));

        return;
    }

    public function clean_value ($string) {
        return trim($string);
    }

    //
    // END - USER HISTORY FUNCTIONS
    //

    public function check_ref($block_ud = false, $auto_block = false) {
        $url = getenv('HTTP_REFERER') ? getenv('HTTP_REFERER') : v_server('HTTP_REFERER');
        $url = $this->clean_value($url);

        if ($url == '') {
            return;
        }

        $domain = explode('?', str_replace(['http://', 'https://'], '', $url));
        $domain = trim($domain[0]);
        $domain = explode('/', $domain);
        $excref = $domain[0] . '/' . $domain[1];
        $domain = trim($domain[0]);

        if (($domain == '') || preg_match('#^.*?' . config('server_name') . '.*?$#i', $domain)) {
            return;
        }

        if (is_array($this->config['exclude_refs'])) {
            $this->config['exclude_refs'] = $this->config['exclude_refs'][0];
        }

        if ($this->config['exclude_refs'] != '') {
            $this->config['exclude_refs'] = explode(nr(), $this->config['exclude_refs']);

            foreach ($this->config['exclude_refs'] as $e_domain) {
                if (strstr($e_domain, 'www.')) {
                    $this->config['exclude_refs'][] = str_replace('www.', '', $e_domain);
                }
            }
        }

        if (in_array($excref, $this->config['exclude_refs'])) {
            return;
        }

        $not_allowed_ref = true;
        if (in_array($excref, $this->config['exclude_refs'])) {
            $domain = $excref;
            $not_allowed_ref = false;
        }

        $request = $this->clean_value(v_server('REQUEST_URI'));
        $auto_block = (int) $auto_block;

        $insert   = true;
        $update   = false;
        $banned   = false;
        $group_id = '';
        $datetime = time();

        $sql = 'SELECT *
            FROM _ref
            WHERE domain = ?
                OR url = ?
            ORDER BY url';
        $result = sql_rowset(sql_filter($sql, $domain, $url));

        foreach ($result as $row) {
            if ($group_id == '') {
                $group_id = $row['group_id'];
            }

            if ($row['banned']) {
                $banned = true;
            }

            if (($row['url'] == $url) && !$update) {
                $sql_banned = '';
                $update = true;
                $insert = false;

                if (!$banned) {
                    $sql_banned = ', banned = ' . $auto_block;
                }

                $sql = 'UPDATE _ref
                    SET request = ?' . $sql_banned . ', views = views + 1, last_datetime = ?, last_ip = ?
                    WHERE domain = ?
                        AND url = ?';
                sql_query(sql_filter($sql, $request, $datetime, $user_ip, $domain, $url));
            }
        }

        if ($insert) {
            if ($group_id == '') {
                $group_id = md5(uniqid(time()));
            }

            $sql_insert = array(
                'group_id'      => $group_id,
                'domain'        => $domain,
                'url'           => $url,
                'request'       => $request,
                'banned'        => $auto_block,
                'views'         => 1,
                'datetime'      => $datetime,
                'last_datetime' => $datetime,
                'last_ip'       => $user_ip
            );
            sql_insert('ref', $sql_insert);
        }

        if ($not_allowed_ref) {
            if ($banned) {
                fatal_error();
            }

            if ($block_ud) {
                redirect();
            }
        }

        return;
    }
}
