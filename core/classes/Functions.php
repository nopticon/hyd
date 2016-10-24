<?php
namespace App;

function htmlencode($str, $multibyte = false) {
    $nr = nr();
    $result = str_replace(array(nr(1), nr(true), '\xFF'), array($nr, $nr, ' '), $str);
    $result = trim(htmlentities($result));
    $result = STRIP ? stripslashes($result) : $result;

    if ($multibyte) {
        $result = preg_replace('#&amp;(\#\d+;)#', '&\1', $result);
    }

    return $result;
}

function set_var(&$result, $var, $type, $multibyte = false) {
    settype($var, $type);
    $result = $var;

    if ($type == 'string') {
        $result = htmlencode($result);
    }
}

function _request($ary) {
    $response = new stdClass();

    foreach ($ary as $ary_k => $ary_v) {
        $response->$ary_k = request_var($ary_k, $ary_v);
    }

    return $response;
}

function _empty($ary) {
    $is_empty = true;

    if (!is_array($ary) && !is_object($ary)) {
        $ary = array($ary);
    }

    foreach ($ary as $ary_k => $ary_v) {
        if (!$ary_v) {
            $is_empty = true;
            break;
        }

        $is_empty = false;
    }

    return $is_empty;
}

//
// Get value of request var
//
function request_var($var_name, $default = false, $multibyte = false) {
    if (REQC) {
        if ((strpos($var_name, config('cookie_name')) !== false) && isset($_COOKIE[$var_name])) {
            $_REQUEST[$var_name] = $_COOKIE[$var_name];
        }
    }

    // Parse $_FILES format, (files:name)
    if (preg_match('#files:([a-z0-9_]+)#i', $var_name, $var_part)) {
        if (!isset($_FILES[$var_part[1]])) {
            return false;
        }

        $_REQUEST[$var_part[1]] = $_FILES[$var_part[1]];

        $var_name = $var_part[1];
        $default  = array('' => '');
    }

    $array_default = is_array($default);
    $array_var     = isset($_REQUEST[$var_name]) && is_array($_REQUEST[$var_name]);

    if (!isset($_REQUEST[$var_name]) || ($array_var && !$array_default) || ($array_default && !$array_var)) {
        return is_array($default) ? w() : $default;
    }

    $var = $_REQUEST[$var_name];
    if (!is_array($default)) {
        $type = gettype($default);
        _utf8($var);
    } else {
        list($key_type, $type) = each($default);
        $type = gettype($type);
        $key_type = gettype($key_type);
    }

    if (is_array($var)) {
        $_var = $var;
        $var = w();

        foreach ($_var as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $_k => $_v) {
                    set_var($k, $k, $key_type);
                    set_var($_k, $_k, $key_type);
                    set_var($var[$k][$_k], $_v, $type, $multibyte);
                }
            } else {
                set_var($k, $k, $key_type);
                set_var($var[$k], $v, $type, $multibyte);
            }
        }
    } else {
        set_var($var, $var, $type, $multibyte);
    }

    return $var;
}

function get_real_ip() {
    $_SERVER['HTTP_X_FORWARDED_FOR'] = v_server('HTTP_X_FORWARDED_FOR');
    $_SERVER['REMOTE_ADDR']          = v_server('REMOTE_ADDR');
    $_ENV['REMOTE_ADDR']             = v_server('REMOTE_ADDR');

    $server = $_SERVER['REMOTE_ADDR'];
    $env    = $_ENV['REMOTE_ADDR'];

    $client_ip = !empty($server) ? $server : (!empty($env) ? $env : '');

    if (v_server('HTTP_X_FORWARDED_FOR')) {
        // Los proxys van añadiendo al final de esta cabecera
        // las direcciones ip que van "ocultando". Para localizar la ip real
        // del usuario se comienza a mirar por el principio hasta encontrar
        // una dirección ip que no sea del rango privado. En caso de no
        // encontrarse ninguna se toma como valor el REMOTE_ADDR

        $entries = explode(',', str_replace(' ', '', $_SERVER['HTTP_X_FORWARDED_FOR']));

        $private_ip = array(
            '/^0\./',
            '/^127\.0\.0\.1/',
            '/^192\.168\..*/',
            '/^172\.((1[6-9])|(2[0-9])|(3[0-1]))\..*/',
            '/^10\..*/'
        );

        foreach ($entries as $entry) {
            if (preg_match("/^(\d+\.\d+\.\d+\.\d+)/", $entry, $ip_list)) {
                // http://www.faqs.org/rfcs/rfc1918.html
                $found_ip = preg_replace($private_ip, $client_ip, $ip_list[1]);

                if ($client_ip != $found_ip) {
                    $client_ip = $found_ip;
                    break;
                }
            }
        }
    }

    return $client_ip;
}

function _utf8(&$a) {
    if (is_array($a)) {
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $_k => $_v) {
                    $a[$k][$_k] = utf8_decode($_v);
                }
            } else {
                $a[$k] = utf8_decode($v);
            }
        }
    } else {
        $a = utf8_decode($a);
    }
}

function decode_ht($path) {
    $da_path = ROOT . '../' . $path;

    if (!@file_exists($da_path) || !$a = @file($da_path)) {
        echo 'htda';
        exit;
    }

    return explode(',', _decode($a[0]));
}

//
// Set or create config value
//
function set_config($name, $value) {
    global $config;

    $sql = 'UPDATE _application SET config_value = ?
        WHERE config_name = ?';
    sql_query(sql_filter($sql, $value, $name));

    if (!sql_affectedrows() && !isset($config[$name])) {
        $sql_insert = array(
            'config_name'  => $name,
            'config_value' => $value
        );
        sql_insert('application', $sql_insert);
    }

    $config[$name] = $value;
}

function isConfig($name) {
    global $config;

    return isset($config[$name]);
}

function config($name, $default = '') {
    global $config;

    return isConfig($name) ? $config[$name] : $default;
}

function do_login() {
    global $auth;

    return $auth->action();
}

function monetize() {
    global $cache, $user;

    if (!$monetize = $cache->get('monetize')) {
        $sql = 'SELECT *
            FROM _monetize
            ORDER BY monetize_order';
        if ($monetize = sql_rowset($sql, 'monetize_id')) {
            $cache->save('monetize', $monetize);
        }
    }

    if (!is_array($monetize) || !count($monetize)) {
        return;
    }

    $set_blocks = w();
    $i = 0;

    foreach ($monetize as $row) {
        if (!$i) {
            _style('monetize');
        }

        if (!isset($set_blocks[$row['monetize_position']])) {
            _style('monetize.' . $row['monetize_position']);
            $set_blocks[$row['monetize_position']] = true;
        }

        _style(
            'monetize.' . $row['monetize_position'] . '.row',
            array(
                'URL'   => $row['monetize_url'],
                'IMAGE' => config('assets_url') . 'base/' . $row['monetize_image'],
                'ALT'   => $row['monetize_alt']
            )
        );

        $i++;
    }

    return;
}

function leading_zero($number) {
    return (($number < 10) ? '0' : '') . $number;
}

function forum_for_team($forum_id) {
    $response = '';
    switch ($forum_id) {
        case config('forum_for_mod'):
            $response = 'mod';
            break;
        case config('forum_for_radio'):
            $response = 'radio';
            break;
        case config('forum_for_colab'):
            $response = 'colab';
            break;
        case config('forum_for_all'):
            $response = 'all';
            break;
    }

    return $response;
}

function forum_for_team_list($forum_id) {
    global $user;

    $a_list = w();
    switch ($forum_id) {
        case config('forum_for_mod'):
            $a_list = $user->_team_auth_list('mod');
            break;
        case config('forum_for_radio'):
            $a_list = $user->_team_auth_list('radio');
            break;
        case config('forum_for_colab'):
            $a_list = $user->_team_auth_list('colab');
            break;
        case config('forum_for_all'):
            $a_list = $user->_team_auth_list('all');
            break;
    }

    return $a_list;
}

function forum_for_team_not() {
    global $user;

    $sql = '';
    $list = w('all mod radio colab');
    foreach ($list as $k) {
        if (!$user->is($k)) {
            $sql .= ', ' . (int) config('forum_for_' . $k);
        }
    }

    return $sql;
}

function forum_for_team_array() {
    $ary  = array();
    $list = w('all mod radio colab');

    foreach ($list as $k) {
        $ary[] = config('forum_for_' . $k);
    }

    return $ary;
}

function extension($filename) {
    $filename = substr($filename, strrpos($filename, '.'));

    return strtolower(str_replace('.', '', $filename));
}

function _implode($glue, $pieces, $empty = false) {
    if (!is_array($pieces) || !count($pieces)) {
        return -1;
    }

    foreach ($pieces as $i => $v) {
        if (empty($v) && !$empty) {
            unset($pieces[$i]);
        }
    }

    if (!count($pieces)) {
        return -1;
    }

    return implode($glue, $pieces);
}

function _implode_and($glue, $last_glue, $pieces, $empty = false) {
    $response = _implode($glue, $pieces, $empty);

    $last = strrpos($response, $glue);
    if ($last !== false) {
        $response = substr_replace($response, $last_glue, $last, count($glue) + 1);
    }

    return $response;
}

function points_start_date() {
    return 1201370400;
}

function v_server($a) {
    return isset($_SERVER[$a]) ? $_SERVER[$a] : '';
}

function get_protocol($ssl = false) {
    return 'http' . (($ssl !== false || v_server('SERVER_PORT') == 443) ? 's' : '') . '://';
}

function get_host() {
    return v_server('HTTP_HOST');
}

function request_method() {
    return strtolower(v_server('REQUEST_METHOD'));
}

// Current page
function _page() {
    return get_protocol() . get_host() . v_server('REQUEST_URI');
}

function array_key($a, $k) {
    return isset($a[$k]) ? $a[$k] : false;
}

function array_dir($path) {
    $list = w();

    $fp = @opendir($path);
    while ($row = @readdir($fp)) {
        if (is_level($row)) {
            continue;
        }

        $list[] = $row;
    }
    @closedir($fp);

    return $list;
}

//
// Parse error lang
//
function parse_error($error) {
    global $user;

    $error = preg_replace('#^([A-Z_]+)$#is', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);

    return implode('<br />', $error);
}

function showError($list) {
    if (!$list) {
        return;
    }

    _style(
        'error',
        array(
            'MESSAGE' => parse_error($list)
        )
    );
}

//
// Return unique id
//
function unique_id() {
    list($sec, $usec) = explode(' ', microtime());
    mt_srand((float) $sec + ((float) $usec * 100000));

    return uniqid(mt_rand(), true);
}

function user_password($password) {
    return sha1(md5($password));
}

// Takes a password and returns the salted hash
// $password - the password to hash
// returns - the hash of the password (128 hex characters)
function HashPassword($password, $already = false) {
    $salt = bin2hex(mcrypt_create_iv(32, MCRYPT_DEV_URANDOM)); // get 256 random bits in hex

    if (!$already) {
        $password = user_password($password);
    }

    $hash = hash('sha256', $salt . $password); // prepend the salt, then hash
    // store the salt and hash in the same string, so only 1 DB column is needed

    return $salt . $hash;
}

//Validates a password
//returns true if hash is the correct hash for that password
//$hash - the hash created by HashPassword (stored in your DB)
//$password - the password to verify
//returns - true if the password is valid, false otherwise.
function ValidatePassword($password, $correctHash) {
    $salt = substr($correctHash, 0, 64); //get the salt from the front of the hash
    $validHash = substr($correctHash, 64, 64); //the SHA256

    $testHash = hash('sha256', $salt . user_password($password)); //hash the password being tested

    //if the hashes are exactly the same, the password is valid
    return $testHash === $validHash;
}

function get_username_base($username, $check_match = false) {
    if ($check_match && !preg_match('#^([A-Za-z0-9\-\_\ ]+)$#is', $username)) {
        return false;
    }

    return str_replace(' ', '', strtolower(trim($username)));
}

function get_subdomain($str) {
    $str = trim($str);
    $str = str_replace(' ', '', $str);

    $str = preg_replace('#&([a-zA-Z]+)acute;#is', '\\1', $str);
    $str = strtolower($str);
    return $str;
}

//
// Get Userdata, $user can be username or user_id. If force_str is true, the username will be forced.
//
function get_userdata($user, $force_str = false) {
    if (!is_numeric($user) || $force_str) {
        $user = get_username_base($user);
    } else {
        $user = intval($user);
    }

    $field = is_integer($user) ? 'user_id' : 'username_base';

    $sql = 'SELECT *
        FROM _members
        WHERE ?? = ?
            AND user_id <> ?';
    if ($row = sql_fieldrow(sql_filter($sq, $field, $user, GUEST))) {
        return $row;
    }
}

function _substr($a, $k, $r = '...') {
    if (strlen($a) > $k) {
        $a = (preg_match('/^(.*)\W.*$/', substr($a, 0, $k + 1), $matches) ? $matches[1] : substr($a, 0, $k)) . $r;
    }
    return $a;
}

function s_link() {
    $data = func_get_args();
    $module = array_shift($data);

    if (strpos($module, ' ') !== false) {
        $data = array_merge(w($module), $data);
        $module = array_shift($data);
    }

    $count_data = count($data);

    switch ($count_data) {
        case 0:
            $data = false;
            break;
        case 1:
            $data = $data[0];
            break;
    }

    // $url = 'http://';
    $url      = '';
    $is_a     = is_array($data);
    $is_local = v_server('REMOTE_ADDR') === '127.0.0.1';
    $is_dash  = preg_match('/^_(\d+)$/i', $data);

    // if (!$is_local && $module == 'a' && $data !== false && ((!$is_a && !$is_dash) || ($is_a && $count_data == 2))) {
    //     $subdomain = ($is_a) ? $data[0] : $data;
    //     $url .= str_replace('www', $subdomain, config('server_name')) . '/';
    //     // $url .= $subdomain . '.' . config('server_name') . '/';

    //     if ($is_a) array_shift($data);

    //     if (!$is_a || ($is_a && !count($data))) $data = false;
    // } else {
        // $url .= config('server_name') . '/' . (($module != '') ? $module . '/' : '');
        $url .= '/' . ($module ? $module . '/' : '');
    // }

    if ($data !== false) {
        if (is_array($data)) {
            switch ($module) {
            case 'acp':
                $args = 0;
                foreach ($data as $data_key => $value) {
                    if (is_numeric($data_key)) {
                        if (!empty($value)) {
                            $url .= ((substr($url, -1) !== '/') ? '/' : '') . $value . '/';
                        }
                    } else {
                        if (!empty($value)) {
                            $url .= (($args) ? '.' : '') . $data_key . ':' .$value;
                            $args++;
                        }
                    }
                }

                if (substr($url, -1) !== '/') {
                    $url .= '/';
                }
                break;
            default:
                foreach ($data as $value) {
                    if (!empty($value)) {
                        $url .= $value . '/';
                    }
                }
                break;
            }
        } else {
            $url .= $data . '/';
        }
    }

    return $url;
}

function s_hidden($input) {
    $hidden = '';
    $format = '<input type="hidden" name="%s" value="%s" />';

    if (is_array($input)) {
        foreach ($input as $name => $value) {
            $hidden .= sprintf($format, $name, $value);
        }
    }

    return $hidden;
}

function strnoupper($in) {
    return ucfirst(strtolower($in));
}

//
// Check if is number
//
function is_numb($v) {
    return preg_match('/^\d+$/', $v);
}

function is_level($path) {
    return ($path == '.' || $path == '..');
}

//
// Build items pagination
//
function build_pagination($url_format, $total_items, $per_page, $offset, $prefix = '', $lang_prefix = '') {
    global $user;

    $total_pages = ceil($total_items / $per_page);
    $on_page     = floor($offset / $per_page) + 1;

    $pages_prev = lang($lang_prefix . 'pages_prev');
    $pages_next = lang($lang_prefix . 'pages_next');

    $format = '<a href="%s">%s</a>';

    $prev = $next = '';
    if ($on_page > 1) {
        $prev = ' ' . sprintf(
            $format,
            sprintf($url_format, (($on_page - 2) * $per_page)),
            sprintf($pages_prev, $per_page)
        );
    }

    if ($on_page < $total_pages) {
        $next = sprintf($format, sprintf($url_format, ($on_page * $per_page)), sprintf($pages_next, $per_page));
    }

    v_style(
        array(
            $prefix . 'PAGES_PREV' => $prev,
            $prefix . 'PAGES_NEXT' => $next,
            $prefix . 'PAGES_ON'   => sprintf(lang('pages_on'), $on_page, max(ceil($total_items / $per_page), 1))
        )
    );

    return;
}

//
// Build items pagination with numbers
//
function build_num_pagination($url_format, $total_items, $per_page, $offset, $prefix = '', $lang_prefix = '') {
    global $user;

    $begin_end = 3;
    $from_middle = 1;

    $total_pages = ceil($total_items / $per_page);

    if ($total_pages < 2) {
        return;
    }

    $on_page    = floor($offset / $per_page) + 1;
    $pages_prev = lang($lang_prefix . 'pages_prev');
    $pages_next = lang($lang_prefix . 'pages_next');

    $format_b    = '<li><strong>%s</strong></li>';
    $format_a    = '<li><a href="%s">%s</a></li>';
    $format_dots = '<li><span>...</span></li>';
    $format_l    = '<a href="%s">%s</a>';

    $page_string = '<ul>';
    if ($total_pages > ((2 * ($begin_end + $from_middle)) + 2)) {
        $init_page_max = ($total_pages > $begin_end) ? $begin_end : $total_pages;

        for ($i = 1; $i < $init_page_max + 1; $i++) {
            $link = sprintf($url_format, (($i - 1) * $per_page));
            $number = ($i == $on_page) ? '<strong>' . $i . '</strong>' : sprintf($format_l, $link, $i);

            $page_string .= '<li>' . $number . '</li>';
        }

        if ($total_pages > $begin_end) {
            if ($on_page > 1  && $on_page < $total_pages) {
                $page_string .= ($on_page > ($begin_end + $from_middle + 1)) ? $format_dots : '';

                $begin = $begin_end + $from_middle;

                $init_page_min = ($on_page > $begin) ? $on_page : ($begin_end + $from_middle + 1);
                $init_page_max = ($on_page < $total_pages - $begin) ? $on_page : $total_pages - $begin;

                for ($i = $init_page_min - $from_middle; $i < $init_page_max + ($from_middle + 1); $i++) {
                    $link = sprintf($format_a, sprintf($url_format, (($i - 1) * $per_page)), $i);
                    $page_string .= ($i == $on_page) ? sprintf($format_b, $i) : $link;
                }

                $page_string .= ($on_page < $total_pages - ($begin_end + $from_middle)) ? $format_dots : '';
            } else {
                $page_string .= $format_dots;
            }

            for ($i = $total_pages - ($begin_end - 1); $i < $total_pages + 1; $i++) {
                $link = sprintf($format_a, sprintf($url_format, (($i - 1) * $per_page)), $i);
                $page_string .= ($i == $on_page) ? sprintf($format_b, $i) : $link;
            }
        }
    } else {
        for ($i = 1; $i < $total_pages + 1; $i++) {
            $link = sprintf($format_a, sprintf($url_format, (($i - 1) * $per_page)), $i);
            $page_string .= ($i == $on_page) ? sprintf($format_b, $i) : $link;
        }
    }

    $page_string .= '</ul>';

    $prev = $next = '';
    if ($on_page > 1) {
        $prev = sprintf($format_l, sprintf($url_format, (($on_page - 2) * $per_page)), sprintf($pages_prev, $per_page));
    }

    if ($on_page < $total_pages) {
        $next = sprintf($format_l, sprintf($url_format, ($on_page * $per_page)), sprintf($pages_next, $per_page));
    }

    if ($page_string == ' <strong>1</strong>') {
        $page_string = '';
    }

    v_style(
        array(
            $prefix . 'PAGES_NUMS' => $page_string,
            $prefix . 'PAGES_PREV' => $prev,
            $prefix . 'PAGES_NEXT' => $next,
            $prefix . 'PAGES_ON'   => sprintf(lang('pages_on'), $on_page, max($total_pages, 1))
        )
    );

    return $page_string;
}

//
// Obtain active bots
//
function obtain_bots(&$bots) {
    global $cache;

    if (!$bots = $cache->get('bots')) {
        $sql = 'SELECT user_id, bot_agent, bot_ip
            FROM _bots
            WHERE bot_active = 1';
        $bots = sql_rowset($sql);

        $cache->save('bots', $bots);
    }

    return;
}

function _button($name = 'submit') {
    return isset($_POST[$name]);
}

function _md($parent, $childs = false) {
    if (!@file_exists($parent)) {
        $oldumask = umask(0);

        if (!@mkdir($parent, octdec(config('mask')), true)) {
            return false;
        }
        _chmod($parent, config('mask'));

        umask($oldumask);
    }

    if ($childs !== false) {
        if (substr($parent, -1) !== '/') {
            $parent .= '/';
        }

        foreach ($childs as $child) {
            $parent .= $child . '/';
            _md($parent);
        }
    }

    return true;
}

function _chmod($filepath, $mask) {
    if (is_string($mask)) {
        $mask = octdec($mask);
    }

    $umask = umask(0);
    $a = @chmod($filepath, $mask);
    @umask($umask);

    return $a;
}

function _rm($path) {
    if (empty($path)) {
        return false;
    }

    if (!@file_exists($path)) {
        return false;
    }

    if (is_dir($path)) {
        $fp = @opendir($path);
        while ($file = @readdir($fp)) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            _rm($path . '/' . $file);
        }
        @closedir($fp);

        if (!@rmdir($path)) {
            return false;
        }
    } else {
        if (!@unlink($path)) {
            return false;
        }
    }

    return true;
}

function build_select($name, $list, $current = null, $callback = false, $use_value = false) {
    $select = '<select name="%s">%s</select>';
    $build  = build_options($list, $current, $callback, $use_value);

    return sprintf($select, $name, $build);
}

function build_options($list, $current = null, $callback = false, $use_value = false) {
    $option = '<option value="%s"%s>%s</option>';

    $str = '';
    foreach ($list as $i => $row) {
        $i    = $use_value ? $row : $i;
        $sel  = !is_null($current) ? selected($current, $i) : '';
        $row  = $callback ? $callback($row) : $row;
        $str .= sprintf($option, $i, $sel, $row);
    }

    return $str;
}

function selected($current, $row) {
    return ($current == $row) ? ' selected="selected"' : '';
}

function checked($current, $row) {
    return ($current == $row) ? ' checked="checked"' : '';
}

function get_artist($id, $force = false) {
    $artist_field = (is_numb($id) && !$force) ? 'ub' : 'subdomain';

    $sql = 'SELECT *
        FROM _artists
        WHERE ?? = ?';
    if (!$data = sql_fieldrow(sql_filter($sql, $artist_field, $id))) {
        return false;
    }

    return $data;
}

function get_file($f) {
    if (empty($f)) {
        return false;
    }

    if (!@file_exists($f)) {
        return w();
    }

    return array_map('trim', @file($f));
}

function exception($filename, $dynamics = false) {
    $a = implode(nr(), get_file(ROOT . 'template/exceptions/' . $filename . '.htm'));

    if ($dynamics !== false) {
        foreach ($dynamics as $k => $v) {
            $a = str_replace('<!--#echo var="' . $k . '" -->', $v, $a);
        }
    }

    return $a;
}

function hook($name, $args = array(), $arr = false) {
    switch ($name) {
        case 'isset':
            eval('$a = ' . $name . '($args' . ((is_array($args)) ? '[0]' . $args[1] : '') . ');');
            return $a;
            break;
        case 'in_array':
            if (is_array($args[1])) {
                if (hook('isset', array($args[1][0], $args[1][1]))) {
                    eval('$a = ' . $name . '($args[0], $args[1][0]' . $args[1][1] . ');');
                }
            } else {
                eval('$a = ' . $name . '($args[0], $args[1]);');
            }

            return isset($a) ? $a : false;
            break;
    }

    $f = 'call_user_func' . (!$arr ? '_array' : '');
    return $f($name, $args);
}

function _pre($a, $d = false) {
    echo '<pre>';
    print_r($a);
    echo '</pre>';

    if ($d === true) {
        sql_close();

        exit;
    }
}

function email_format($email) {
    if (preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is', $email)) {
        return true;
    }
    return false;
}

function entity_decode($s, $compat = true) {
    if ($compat) {
        return html_entity_decode($s, ENT_COMPAT, 'UTF-8');
    }
    return html_entity_decode($s);
}

function f($s) {
    return !empty($s);
}

function w($a = '', $d = false) {
    if (empty($a) || !is_string($a)) {
        return array();
    }

    $e = explode(' ', $a);
    if ($d !== false) {
        foreach ($e as $i => $v) {
            $e[$v] = $d;
            unset($e[$i]);
        }
    }

    return $e;
}

function sendmail($to, $from, $subject, $template = '', $vars = array()) {
    static $emailer;

    if (!$emailer) {
        $emailer = new emailer();
    }

    $emailer->from = trim($from);

    $template_parts = explode(':', $template);

    if (isset($template_parts[0])) {
        $emailer->use_template($template_parts[0]);
    }

    if (isset($template_parts[1])) {
        $emailer->format = $template_parts[1];
    }

    $emailer->assign_vars($vars);

    $response = $emailer->send();
    $emailer->reset();

    return $response;
}

function lang_key($key, $subkey = false, $default = '') {
    global $user;

    if ($subkey !== false) {
        return isset($user->lang[$key][$subkey]) ? $user->lang[$key][$subkey] : $default;
    }

    $key = strtoupper($key);

    return isset($user->lang[$key]) ? $user->lang[$key] : $default;
}

function lang($key, $default = '') {
    return lang_key($key, false, $default);
}

function fatal_error($mode = '404', $bp_message = '') {
    global $user;

    $current_page = _page();
    $error        = 'La p&aacute;gina <strong>' . $current_page . '</strong> ';

    $username    = (@method_exists($user, 'd')) ? $user->d('username') : '';
    $bp_message .= nr(false, 2) . $current_page . nr(false, 2) . $username;

    switch ($mode) {
        case 'mysql':
            if (config('default_lang') && isset($user->lang)) {
                // Send email notification
                $emailer = new emailer();

                $emailer->from('info');
                $emailer->set_subject('MySQL error');
                $emailer->use_template('mcp_delete', config('default_lang'));
                $emailer->email_address('info@rockrepublik.net');

                $emailer->assign_vars(
                    array(
                        'MESSAGE' => $bp_message,
                        'TIME'    => $user->format_date(time(), 'r')
                    )
                );
                //$emailer->send();
                $emailer->reset();
            }

            $title  = 'Error del sistema';
            $error .= 'tiene un error';
            break;
        case '600':
            $title  = 'Origen inv&aacute;lido';
            $error .= 'no puede ser accesada porque no se reconoce su IP de origen.';

            @error_log('[php client empty ip] File does not exist: ' . $current_page, 0);
            break;
        default:
            $title       = 'Archivo no encontrado';
            $error      .= 'no existe';
            $bp_message  = '';

            status("404 Not Found");

            $log = '[php client %s %s] File does not exist: %s';
            $user = $user->d('username') ? ' - ' . $user->d('username') : '';

            @error_log(sprintf($log, $user->ip, $user, $current_page), 0);
            break;
    }

    if ($mode != '600') {
        $error .= ', puedes regresar a<br /><a href="/">p&aacute;gina de inicio de Rock Republik</a>.';

        if (!empty($bp_message)) {
            $error .= '<br /><br />' . $bp_message;
        }
    }

    sql_close();

    $replaces = array(
        'PAGE_TITLE'   => $title,
        'PAGE_MESSAGE' => $error
    );

    echo exception('error', $replaces);
    exit;
}

function status($message) {
    header("HTTP/1.1 " . $message);
    header("Status: " . $message);
}

function msg_handler($errno, $msg_text, $errfile, $errline) {
    global $template, $user, $auth, $cache, $starttime;

    switch ($errno) {
        case E_NOTICE:
        case E_WARNING:
            $format = '<b>PHP Notice</b>: in file <b>%s</b> on line <b>%s</b>: <b>%s</b><br>';
            echo sprintf($format, $errfile, $errline, $msg_text);
            break;
        case E_USER_ERROR:
            sql_close();

            fatal_error('mysql', $msg_text);
            break;
        case E_USER_NOTICE:
            if (empty($user->data)) {
                $user->init();
            }
            if (empty($user->lang)) {
                $user->setup();
            }

            if (empty($template->root)) {
                $template->set_template(ROOT . 'template');
            }

            $custom_vars = array(
                'MESSAGE_TITLE' => lang('information'),
                'MESSAGE_TEXT'  => lang($msg_text, $msg_text)
            );

            page_layout('INFORMATION', 'message', $custom_vars);
            break;
        default:
            // $format = '<b>Another Error</b>: in file <b>%s</b> on line <b>%s</b>: <b>%s</b><br>';
            // echo sprintf($format, basename($$errfile), $errline, $msg_text);
            break;
    }
}

function redirect($url, $moved = false) {
    sql_close();

    // If relative path, prepend application url
    if (strpos($url, '//') === false) {
        $url = 'http://' . config('server_name') . trim($url);
    }

    if (strpos($url, 'http') === false) {
        $url = 'http:' . $url;
    }

    if ($moved !== false) {
        header("HTTP/1.1 301 Moved Permanently");
    }

    header('Location: ' . $url);
    exit;
}

function topic_feature($topic_id, $value) {
    $sql = 'UPDATE _forum_topics
        SET topic_featured = ?
        WHERE topic_id = ?';
    sql_query(sql_filter($sql, $value, $topic_id));

    return;
}

function topic_arkane($topic_id, $value) {
    $sql = 'UPDATE _forum_topics
        SET topic_points = ?
        WHERE topic_id = ?';
    sql_query(sql_filter($sql, $value, $topic_id));

    return;
}

function page_layout($page_title, $htmlpage, $custom_vars = false, $js_keepalive = true) {
    global $user, $cache, $starttime, $template;

    //
    // gzip_compression
    //
    if (strstr($user->browser, 'compatible') || strstr($user->browser, 'Gecko')) {
        ob_start('ob_gzhandler');
    }

    monetize();

    // Get today items count
    $sql = 'SELECT COUNT(element) AS total
        FROM _members_unread
        WHERE user_id = ?';
    $today_count = sql_field(sql_filter($sql, $user->d('user_id')), 'total', 0);

    //
    // Send headers
    //
    header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
    header('Expires: 0');
    header('Pragma: no-cache');

    //
    // Footer
    //
    $u_session = ($user->is('member')) ? 'out' : 'in';

    $acp = isset($template->vars['U_ACP']) ? $template->vars['U_ACP'] : 0;
    $acp = $acp ?: ($user->is('artist') || $user->is('mod') ? s_link('acp') : '');

    $today = ($today_count == 1) ? lang('unread_item_count') : lang('unread_items_count');
    $today = sprintf($today, $today_count);

    if (preg_match('#.*?my/confirm.*?#is', $user->d('session_page'))) {
        $user->d('session_page', '');
    }

    $common_vars = array(
        'PAGE_TITLE'    => lang($page_title, $page_title),
        '_SELF'         => _page(),

        'U_REGISTER'    => s_link('signup'),
        'U_SESSION'     => s_link('sign' . $u_session),
        'U_PROFILE'     => s_link('m', $user->d('username_base')),
        'U_EDITPROFILE' => s_link('my profile'),
        'U_PASSWORD'    => s_link('signr'),
        'U_DC'          => s_link('my dc'),

        'U_HOME'        => s_link(),
        'U_FAQ'         => s_link('faq'),
        'U_WHATS_NEW'   => s_link('today'),
        'U_ARTISTS'     => s_link('a'),
        'U_AWARDS'      => s_link('awards'),
        'U_RADIO'       => s_link('radio'),
        'U_BROADCAST'   => s_link('broadcast'),
        'U_NEWS'        => s_link('news'),
        'U_EVENTS'      => s_link('events'),
        'U_FORUM'       => s_link('board'),
        'U_COMMUNITY'   => s_link('community'),
        'U_ALLIES'      => s_link('allies'),
        'U_TOS'         => s_link('tos'),
        'U_HELP'        => s_link('help'),
        'U_RSS_NEWS'    => s_link('rss', 'news'),
        'U_RSS_ARTISTS' => s_link('rss', 'artists'),
        'U_COMMENTS'    => s_link('comments'),
        'U_EMOTICONS'   => s_link('emoticons'),
        'U_ACP'         => $acp,

        'S_YEAR'        => date('Y'),
        'S_UPLOAD'      => upload_maxsize(),
        'S_GIT'         => config('git_push_time'),
        'S_KEYWORDS'    => config('meta_keys'),
        'S_DESCRIPTION' => config('meta_desc'),
        'S_SERVER'      => '//' . config('server_name'),
        'S_ASSETS'      => config('assets_url'),
        'S_DIST'        => '/dist/',
        'S_SQL'         => $user->d('is_founder') ? sql_queries() . 'q | ' : '',
        'S_REDIRECT'    => $user->d('session_page'),
        'S_USERNAME'    => $user->d('username'),
        'S_MEMBER'      => $user->is('member'),
        'S_TODAY_COUNT' => $today
    );

    if ($custom_vars !== false) {
        $common_vars += $custom_vars;
    }

    $mtime = explode(' ', microtime());
    $common_vars['S_TIME'] = sprintf('%.2f', ($mtime[0] + $mtime[1] - $starttime));

    v_style($common_vars);

    $template->set_filenames(
        array(
            'body' => $htmlpage . '.htm'
        )
    );
    $template->pparse('body');

    sql_close();
    exit;
}

function sidebar() {
    $sfiles = func_get_args();
    if (!is_array($sfiles) || !sizeof($sfiles)) {
        return;
    }

    foreach ($sfiles as $each_file) {
        $include_file = ROOT . 'objects/sidebar/' . $each_file . '.php';
        if (file_exists($include_file)) {
            @require_once($include_file);
        }
    }

    return;
}

//
// Thanks to:
// SNEAK: Snarkles.Net Encryption Assortment Kit
// Copyright (c) 2000, 2001, 2002 Snarkles (webgeek@snarkles.net)
//
// Used Functions: hex2asc()
//
function hex2asc($str) {
    $newstring = '';
    for ($n = 0, $end = strlen($str); $n < $end; $n += 2) {
        $newstring .= pack('C', hexdec(substr($str, $n, 2)));
    }

    return $newstring;
}
//
// End @ Sneak
//

function _encode($msg) {
    for ($i = 0; $i < 1; $i++) {
        $msg = base64_encode($msg);
    }

    return bin2hex($msg);
}

function _decode($msg) {
    $msg = hex2asc($msg);
    for ($i = 0; $i < 1; $i++) {
        $msg = base64_decode($msg);
    }

    return $msg;
}
// End @ encode | decode
//

function get_yt_code($a) {
    $clear = '';

    if (strpos($a, '://') === false) {
        return $a;
    }

    $p = parse_url($a);
    if (!isset($p['query'])) {
        return $clear;
    }

    $s = explode('&', $p['query']);
    $v = '';
    for ($i = 0, $end = count($s); $i < $end; $i++) {
        if (strpos($s[$i], 'v=') !== false) {
            $v = $s[$i];
        }
    }

    if (empty($v)) {
        return $clear;
    }

    $s2 = explode('=', $v);
    return $s2[1];
}

function get_a_imagepath($abs_path, $domain_path, $directory, $filename, $folders) {
    foreach ($folders as $row) {
        $a = $abs_path . $directory . '/' . $row . '/' . $filename;
        return $domain_path . $directory . '/' . $row . '/' . $filename;
    }
    return false;
}

function check_www($url) {
    $domain = str_replace('http://', '', $url);
    if (strstr($domain, '?')) {
        $domain_e = explode('/', $domain);
        $domain = $domain_e[0];
        if ($domain == config('server_name')) {
            $domain .= '/' . $domain_e[1];
        }
    }

    if ($check = @fopen('http://' . $domain, 'r')) {
        @fclose($check);
        return true;
    }

    return false;
}

function curl_get($url, $method = 'get') {
    $socket = curl_init();
    curl_setopt($socket, CURLOPT_URL, $url);
    curl_setopt($socket, CURLOPT_VERBOSE, 0);
    curl_setopt($socket, CURLOPT_HEADER, 0);

    if ($method == 'post') {
        curl_setopt($socket, CURLOPT_POST, 1);
    }

    curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);

    $call = curl_exec($socket);
    if (!curl_errno($socket)) {
        $info = curl_getinfo($socket);
    } else {
        $info = curl_error($socket);
    }
    curl_close($socket);

    return $call;
}

function html_entity_decode_utf8($string) {
    static $trans_tbl;

    // Replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
    $string = preg_replace('~&#(\d+);~e', 'code2utf(\\1)', $string);

    // Replace literal entities
    if (!isset($trans_tbl)) {
        $trans_tbl = w();
        foreach (get_html_translation_table(HTML_ENTITIES) as $val => $key) {
            $trans_tbl[$key] = utf8_encode($val);
        }
    }

    return strtr($string, $trans_tbl);
}

function _rowset_style($sql, $style, $prefix = '') {
    $a = sql_rowset($sql);
    _rowset_foreach($a, $style, $prefix);

    return $a;
}

function _rowset_foreach($rows, $style, $prefix = '') {
    $i = 0;
    foreach ($rows as $row) {
        if (!$i) {
            _style($style);
        }

        _rowset_style_row($row, $style, $prefix);
        $i++;
    }

    return;
}

function _rowset_style_row($row, $style, $prefix = '') {
    if (!empty($prefix)) {
        $prefix .= '_';
    }

    $f = w();
    foreach ($row as $_f => $_v) {
        $g = array_key(array_slice(explode('_', $_f), -1), 0);
        $f[strtoupper($prefix . $g)] = $_v;
    }

    return _style($style . '.row', $f);
}

function _style_uv($a) {
    if (!is_array($a) && !is_object($a)) {
        $a = w();
    }

    $b = w();
    foreach ($a as $i => $v) {
        $b[strtoupper($i)] = $v;
    }

    return $b;
}

function _style($a, $b = array(), $i = false) {
    if ($i !== false && $i) {
        return;
    }

    global $template;

    $template->assign_block_vars($a, _style_uv($b));
    return true;
}

function _style_handler($f) {
    global $template;

    $template->set_filenames(array('tmp' => $f));
    $template->assign_var_from_handle('S_TMP', 'tmp');

    return _style_var('S_TMP');
}

function _style_vreplace($r = true) {
    global $template;

    return $template->set_vreplace($r);
}

function v_style($a) {
    global $template;

    $template->assign_vars(_style_uv($a));
    return true;
}

function _style_functions($arg) {
    if (!isset($arg[1]) || !isset($arg[2])) {
        return $arg[0];
    }

    $f = '_sf_' . strtolower($arg[1]);
    if (!@function_exists($f)) {
        return $arg[0];
    }

    $e = explode(':', $arg[2]);
    $f_arg = array();

    foreach ($e as $row) {
        if (preg_match('/\((.*?)\)/', $row, $reg)) {
            $_row = array_map('trim', explode(',', str_replace("'", '', $reg[1])));
            $row = array();

            foreach ($_row as $each) {
                $j = explode(' => ', $each);
                $row[$j[0]] = $j[1];
            }
        }
        $f_arg[] = $row;
    }

    return hook($f, $f_arg);
}

function artist_build($ary) {
    return implode('/', $ary);
}

function artist_root($alias, $check = false) {
    if (!is_array($alias)) {
        $alias = w($alias);
    }

    $response = config('artists_path') . artist_build($alias);

    if ($check) {
        artist_check($response);
    }

    return $response;
}

function artist_path($alias, $id, $build = true, $check = false) {
    $response = array($alias{0}, $alias{1}, $id);

    if ($check) {
        artist_check($response);
    }

    if ($build) {
        $response = config('artists_path') . artist_build($response) . '/';
    }

    return $response;
}

function artist_check($ary) {
    $fullpath = config('artists_path');

    if (!is_array($ary)) {
        $ary = w($ary);
    }

    foreach ($ary as $row) {
        $fullpath .= $row . '/';

        if (!@file_exists($fullpath)) {
            if (!_md($fullpath)) {
                return false;
            }
            _chmod($fullpath, config('mask'));
        }
    }

    return true;
}

function upload_maxsize() {
    return intval(ini_get('upload_max_filesize')) * 1048576;
}

function friendly($s) {
    $s = preg_replace("`\[.*\]`U", '', $s);
    $s = preg_replace('#&([a-zA-Z]+)acute;#is', '\\1', $s);
    $s = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '-', $s);
    $s = htmlentities($s, ENT_COMPAT, 'utf-8');
    $s = preg_replace("`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i", "\\1", $s);
    $s = preg_replace(array("`[^a-z0-9]`i", "`[-]+`"), '-', $s);

    return strtolower(trim($s, '-'));
}

function nr($r = false, $rep = 1) {
    return str_repeat((($r !== false) ? "\r" : '') . (($r !== true) ? "\n" : ''), $rep);
}

// Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
function code2utf($num) {
    if ($num < 128) {
        return chr($num);
    }

    if ($num < 2048) {
        return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
    }

    if ($num < 65536) {
        return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
    }

    if ($num < 2097152) {
        $return  = chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128);
        $return .= chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);

        return $return;
    }

    return '';
}

function language_select($default, $select_name = 'language', $dirname = 'language') {
    $lang = array();

    $dir = @opendir(ROOT . $dirname);
    while ($file = readdir($dir)) {
        $real_path = @realpath(ROOT.$dirname . '/' . $file);

        if (preg_match('#^lang_#i', $file) && !is_file($real_path) && !is_link($real_path)) {
            $filename = trim(str_replace('lang_', '', $file));
            $displayname = preg_replace("/^(.*?)_(.*)$/", "\\1 [ \\2 ]", $filename);
            $displayname = preg_replace("/\[(.*?)_(.*)\]/", "[ \\1 - \\2 ]", $displayname);

            $lang[$displayname] = $filename;
        }
    }
    closedir($dir);

    @asort($lang);

    $lang_select = '<select name="' . $select_name . '">';
    foreach ($lang as $displayname => $filename) {
        $selected = (strtolower($default) == strtolower($filename)) ? ' selected="selected"' : '';
        $lang_select .= '<option value="' . $filename . '"' . $selected . '>' . ucwords($displayname) . '</option>';
    }
    $lang_select .= '</select>';

    return $lang_select;
}

//
// Pick a timezone
//
function tz_select($default, $select_name = 'timezone') {
    global $lang;

    $tz_select = '<select name="' . $select_name . '">';

    foreach ($lang['tz'] as $offset => $zone) {
        $selected = ($offset == $default) ? ' selected="selected"' : '';
        $tz_select .= '<option value="' . $offset . '"' . $selected . '>' . $zone . '</option>';
    }
    $tz_select .= '</select>';

    return $tz_select;
}

function sumhour($a) {
    $h = substr($a, 0, 2);
    $m = substr($a, 2, 2);
    $mk = mktime($h - 6, $m);

    return date('Hi', $mk);
}

function oclock($a) {
    $h = substr($a, 0, 2);
    $m = substr($a, 2, 2);

    return ($m === '00');
}

//
// Check to see if the username has been taken, or if it is disallowed.
// Also checks if it includes the " character, which we don't allow in usernames.
// Used for registering, changing names, and posting anonymously with a username
//
function validate_username($username) {
    global $user;

    // Remove doubled up spaces
    $username = preg_replace('#\s+#', ' ', trim($username));
    $username = get_username_base($username);

    $sql = 'SELECT username
        FROM _members
        WHERE LOWER(username_base) = ?';
    if ($userdata = sql_fieldrow(sql_filter($sql, strtolower($username)))) {
        if (($user->is('member') && $username != $userdata['username']) || !$user->is('member')) {
            return array('error' => true, 'error_msg' => lang('username_taken'));
        }
    }

    $sql = 'SELECT group_name
        FROM _groups
        WHERE LOWER(group_name) = ?';
    if (sql_fieldrow(sql_filter($sql, strtolower($username)))) {
        return array('error' => true, 'error_msg' => lang('username_taken'));
    }

    $sql = 'SELECT disallow_username
        FROM _disallow';
    $result = sql_rowset($sql);

    foreach ($result as $row) {
        $preg_username = str_replace("\*", ".*?", preg_quote($row['disallow_username'], '#'));

        if (preg_match("#\b(" . $preg_username . ")\b#i", $username)) {
            return array('error' => true, 'error_msg' => lang('username_disallowed'));
        }
    }

    // Don't allow " and ALT-255 in username.
    if (strstr($username, '"') || strstr($username, '&quot;') || strstr($username, chr(160))) {
        return array('error' => true, 'error_msg' => lang('username_invalid'));
    }

    return array('error' => false, 'error_msg' => '');
}

//
// Check to see if email address is banned
// or already present in the DB
//
function validate_email($email) {
    global $user;

    if (!empty($email)) {
        if (preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is', $email)) {
            $sql = 'SELECT ban_email
                FROM _banlist';
            $result = sql_rowset($sql);

            foreach ($result as $row) {
                $match_email = str_replace('*', '.*?', $row['ban_email']);
                if (preg_match('/^' . $match_email . '$/is', $email)) {
                    return array('error' => true, 'error_msg' => lang('email_banned'));
                }
            }

            $sql = 'SELECT user_email
                FROM _members
                WHERE user_email = ?';
            if (sql_fieldrow(sql_filter($sql, $email))) {
                return array('error' => true, 'error_msg' => lang('emailL_taken'));
            }

            return array('error' => false, 'error_msg' => '');
        }
    }

    return array('error' => true, 'error_msg' => lang('email_invalid'));
}

function get_user_avatar($name, $user_id = GUEST, $format = '', $abs_path = false) {
    switch ($user_id) {
        case GUEST:
            $value = 'style/avatar.gif';
            break;
        default:
            if (empty($name)) {
                $name = 'avatar.gif';
            }

            $value = 'avatars/' . $name . $format;
            break;
    }

    $path = $abs_path ? config('assets_path') : config('assets_url');

    return $path . $value;
}

function etag($filename, $quote = true) {
    if (!file_exists($filename) || !($info = stat($filename))) {
        return false;
    }
    $q = ($quote) ? '"' : '';
    return sprintf("$q%x-%x-%x$q", $info['ino'], $info['size'], $info['mtime']);
}

//
// Does supplementary validation of optional profile fields. This expects common stuff like trim() and strip_tags()
// to have already been run. Params are passed by-ref, so we can set them to the empty string if they fail.
//
function validate_optional_fields(&$msnm, &$yim, &$website, &$location, &$occupation, &$interests, &$sig) {
    $check_var_length = w('aim msnm yim location occupation interests sig');

    foreach ($check_var_length as $row) {
        if (strlen($$row) < 2) {
            $$row = '';
        }
    }

    // website has to start with http://, followed by something with length at least 3 that
    // contains at least one dot.
    if (!empty($website)) {
        if (!preg_match('#^http[s]?:\/\/#i', $website)) {
            $website = 'http://' . $website;
        }

        if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $website)) {
            $website = '';
        }
    }

    return;
}

function show_exception($name) {
    $file_content = @file('./template/exceptions/' . $name . '.htm');

    $matches = array(
        '<!--#echo var="HTTP_HOST" -->' => v_server('HTTP_HOST'),
        '<!--#echo var="REQUEST_URI" -->' => v_server('REQUEST_URI')
    );

    $orig = $repl = array();

    foreach ($matches as $row_k => $row_v) {
        $orig[] = $row_k;
        $repl[] = $row_v;
    }

    echo str_replace($orig, $repl, implode('', $file_content));
    exit;
}

function setAppCookie($name, $value, $expires = 0) {
    setcookie(config('cookie_name') . '_' . $name, $value, $expires, config('cookie_path'), config('cookie_domain'));
}

function guestUsername($username) {
    return '*' . !empty($username) ? $username : lang('guest');
}

function decamelize($word) {
      return $word = preg_replace_callback(
        "/(^|[a-z])([A-Z])/",
        function ($m) {
            return strtolower(strlen($m[1]) ? "$m[1]_$m[2]" : "$m[2]");
        },
        $word
    );
}

function camelize($word) {
    return $word = preg_replace_callback(
        "/(^|_)([a-z])/",
        function ($m) {
            return strtoupper("$m[2]");
        },
        $word
    );
}

function a_thumbnails($selected_artists, $random_images, $lang_key, $block, $item_per_col = 2) {
    global $user;

    _style(
        'main.' . $block,
        array(
            'L_TITLE' => lang($lang_key)
        )
    );

    foreach ($selected_artists as $ub => $data) {
        $image = $ub . '/thumbnails/' . $random_images[$ub] . '.jpg';

        _style(
            'main.' . $block . '.row',
            array(
                'NAME'     => $data['name'],
                'IMAGE'    => config('artists_url') . $image,
                'URL'      => s_link('a', $data['subdomain']),
                'LOCATION' => $data['local'] ? 'Guatemala' : $data['location'],
                'GENRE'    => $data['genre']
            )
        );
    }

    return true;
}
