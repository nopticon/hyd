<?php
/*
<Orion, a web development framework for RK.>
Copyright (C) <2011>  <Orion>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
function htmlencode($str) {
	$result = trim(htmlentities(str_replace(array("\r\n", "\r", '\xFF'), array("\n", "\n", ' '), $str)));
	$result = (STRIP) ? stripslashes($result) : $result;
	
	if ($multibyte) {
		$result = preg_replace('#&amp;(\#[0-9]+;)#', '&\1', $result);
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

//
// Get value of request var
//
function request_var($var_name, $default, $multibyte = false) {
	if (REQC) {
		global $config;
		
		if (strstr($var_name, $config['cookie_name']) && isset($_COOKIE[$var_name])) {
			$_REQUEST[$var_name] = $_COOKIE[$var_name];
		}
	}
	
	if (!isset($_REQUEST[$var_name]) || (is_array($_REQUEST[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($_REQUEST[$var_name]))) {
		return (is_array($default)) ? array() : $default;
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
		$var = array();

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
	
	if (!@file_exists($da_path) || !$a = @file($da_path)) exit;
	
	return explode(',', _decode($a[0]));
}

//
// Set or create config value
//
function set_config($config_name, $config_value) {
	global $config;

	$sql = 'UPDATE _application SET config_value = ?
		WHERE config_name = ?';
	sql_query(sql_filter($sql, $config_value, $config_name));
	
	if (!sql_affectedrows() && !isset($config[$config_name])) {
		$sql_insert = array(
			'config_name' => $config_name,
			'config_value' => $config_value
		);
		$sql = 'INSERT INTO _application' . sql_build('INSERT', $sql_insert);
		sql_query($sql);
	}

	$config[$config_name] = $config_value;
}

function leading_zero($number) {
	return (($number < 10) ? '0' : '') . $number;
}

function forum_for_team($forum_id) {
	global $config;
	
	$response = '';
	switch ($forum_id) {
		case $config['forum_for_mod']:
			$response = 'mod';
			break;
		case $config['forum_for_radio']:
			$response = 'radio';
			break;
		case $config['forum_for_colab']:
			$response = 'colab';
			break;
		case $config['forum_for_all']:
			$response = 'all';
			break;
	}
	
	return $response;
}

function forum_for_team_list($forum_id) {
	global $config, $user;
	
	$a_list = array();
	switch ($forum_id) {
		case $config['forum_for_mod']:
			$a_list = $user->_team_auth_list('mod');
			break;
		case $config['forum_for_radio']:
			$a_list = $user->_team_auth_list('radio');
			break;
		case $config['forum_for_colab']:
			$a_list = $user->_team_auth_list('colab');
			break;
		case $config['forum_for_all']:
			$a_list = $user->_team_auth_list('all');
			break;
	}
	
	return $a_list;
}

function forum_for_team_not() {
	global $config, $user;
	
	$sql = '';
	$list = array('all', 'mod', 'radio', 'colab');
	foreach ($list as $k) {
		if (!$user->_team_auth($k)) {
			$sql .= ', ' . (int) $config['forum_for_' . $k];
		}
	}
	return $sql;
}

function forum_for_team_array() {
	global $config;
	
	$ary = array();
	$list = array('all', 'mod', 'radio', 'colab');
	foreach ($list as $k) {
		$ary[] = $config['forum_for_' . $k];
	}
	return $ary;
}

function points_start_date() {
	return 1201370400;
}

//
// Requested Page
//
function requested_page() {
	$protocol = ((int) $_SERVER['SERVER_PORT'] === 443) ? 'https://' : 'http://';
	$current_page = $protocol . $_SERVER['HTTP_HOST'] . ((!empty($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '');
	
	return $current_page;
}

function array_key($a, $k) {
	return (isset($a[$k])) ? $a[$k] : false;
}

//
// Parse error lang
//
function parse_error($error) {
	global $user;
	
	return implode('<br />', preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error));
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

//
// Format the username
//
function phpbb_clean_username($username) {
	/*
	$username = substr(htmlspecialchars(str_replace("\'", "'", trim($username))), 0, 20);
	$username = rtrim($username, "\\");
	$username = str_replace("'", "\'", $username);
	*/
	$username = substr(trim($username), 0, 20);
	/*
	$username = rtrim($username, "\\");
	$username = str_replace("'", "\'", $username);
	*/

	return $username;
}

function get_username_base($username, $check_match = false) {
	if ($check_match && !preg_match('#^([A-Za-z0-9\-\_\ ]+)$#is', $username)) {
		return false;
	}
	
	return str_replace(' ', '', strtolower($username));
}

function get_subdomain($str) {
	$str = trim($str);
	$str = strtolower($str);
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
		$user = phpbb_clean_username($user);
	} else {
		$user = intval($user);
	}
	
	$field = (is_integer($user)) ? 'user_id' : 'username'; 
	
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

function s_link($module = '', $data = false) {
	global $config;
	
	$url = '//';
	$is_a = is_array($data);
	if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1' && $module == 'a' && $data !== false && ((!$is_a && !preg_match('/^_([0-9]+)$/i', $data)) || ($is_a && count($data) == 2))) {
		$subdomain = ($is_a) ? $data[0] : $data;
		$url .= str_replace('www', $subdomain, $config['server_name']) . '/';
		
		if ($is_a) array_shift($data);
		
		if (!$is_a || ($is_a && !count($data))) $data = false;
	} else {
		$url .= $config['server_name'] . '/' . (($module != '') ? $module . '/' : '');
	}
	
	if ($data !== false) {
		if (is_array($data)) {
			foreach ($data as $value) {
				if ($value != '') $url .= $value . '/';
			}
		} else {
			$url .= $data . '/';
		}
	}
	
	return $url;
}

function s_link_control($module, $data = false) {
	global $config;
	
	$url = 'http://' . $config['server_name'] . '/control/' . $module . '/';
	if ($data !== false) {
		$i = 0;
		foreach ($data as $key => $value) {
			$url .= (($i) ? '.' : '') . $key . '-' . $value;
			$i++;
		}
		
		$url .= '/';
	}
	
	return $url;
}

function s_hidden($input) {
	$s_hidden_fields = '';
	
	if (is_array($input)) {
		foreach ($input as $name => $value) {
			$s_hidden_fields .= '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
		}
	}
	
	return $s_hidden_fields;
}

function strnoupper($in) {
	$in = strtolower($in);
	return ucfirst($in);
	//return preg_replace('/(^(\w*?)|(\w{4,}?))/e', "ucfirst('$1')", $in);
}

//
// Check if is number
//
function is_numb($v) {
	return @preg_match('/^\d+$/', $v);
}

function is_number($number = '') {
	if (preg_match('/^([0-9]+)$/', $number)) {
		return true;
	}
	
	return false;
}

//
// Build items pagination
//
function build_pagination($url_format, $total_items, $per_page, $offset, $prefix = '', $lang_prefix = '') {
	global $template, $user;
	
	$total_pages = ceil($total_items / $per_page);
	$on_page = floor($offset / $per_page) + 1;
	
	$prev = $next = '';
	if ($on_page > 1) {
		$prev = ' <a href="' . sprintf($url_format, (($on_page - 2) * $per_page)) . '">' . sprintf($user->lang[(($lang_prefix != '') ? $lang_prefix : '') . 'PAGES_PREV'], $per_page) . '</a>';
	}
	
	if ($on_page < $total_pages) {
		$next = '<a href="' . sprintf($url_format, ($on_page * $per_page)) . '">' . sprintf($user->lang[(($lang_prefix != '') ? $lang_prefix : '') . 'PAGES_NEXT'], $per_page) . '</a>';
	}
	
	$template->assign_vars(array(
		$prefix . 'PAGES_PREV' => $prev,
		$prefix . 'PAGES_NEXT' => $next,
		$prefix . 'PAGES_ON' => sprintf($user->lang['PAGES_ON'], $on_page, max(ceil($total_items / $per_page), 1)))
	);
	
	return;
}

//
// Build items pagination with numbers
//
//function generate_pagination($base_url, $num_items, $per_page, $start_item, $add_prevnext_text = true, $start_field = 'start', $folders_format = 0)
function build_num_pagination ($url_format, $total_items, $per_page, $offset, $prefix = '', $lang_prefix = '') {
	global $user, $template;
	
	$begin_end = 3;
	$from_middle = 1;

	$total_pages = ceil($total_items/$per_page);
	
	if ($total_pages < 2) {
		return;
	}
	
	$on_page = floor($offset / $per_page) + 1;
	
	$page_string = '<ul>';
	if ($total_pages > ((2 * ($begin_end + $from_middle)) + 2)) {
		$init_page_max = ($total_pages > $begin_end) ? $begin_end : $total_pages;

		for ($i = 1; $i < $init_page_max + 1; $i++) {
			$page_string .= ($i == $on_page) ? '<li><strong>' . $i . '</strong></li>' : '<li><a href="' . sprintf($url_format, (($i - 1) * $per_page)) . '">' . $i . '</a></li>';
		}

		if ($total_pages > $begin_end) {
			if ($on_page > 1  && $on_page < $total_pages) {
				$page_string .= ($on_page > ($begin_end + $from_middle + 1)) ? '<li><span>...</span></li>' : '';

				$init_page_min = ($on_page > ($begin_end + $from_middle)) ? $on_page : ($begin_end + $from_middle + 1);
				$init_page_max = ($on_page < $total_pages - ($begin_end + $from_middle)) ? $on_page : $total_pages - ($begin_end + $from_middle);

				for ($i = $init_page_min - $from_middle; $i < $init_page_max + ($from_middle + 1); $i++) {
					$page_string .= ($i == $on_page) ? '<li><strong>' . $i . '</strong></li>' : '<li><a href="' . sprintf($url_format, (($i - 1) * $per_page)) . '">' . $i . '</a></li>';
				}

				$page_string .= ($on_page < $total_pages - ($begin_end + $from_middle)) ? '<li><span>...</span></li>' : '';
			} else {
				$page_string .= '<li><span>...</span></li>';
			}

			for ($i = $total_pages - ($begin_end - 1); $i < $total_pages + 1; $i++) {
				$page_string .= ($i == $on_page) ? '<li><strong>' . $i . '</strong></li>'  : '<li><a href="' . sprintf($url_format, (($i - 1) * $per_page)) . '">' . $i . '</a></li>';
			}
		}
	} else {
		for ($i = 1; $i < $total_pages + 1; $i++) {
			$page_string .= ($i == $on_page) ? '<li><strong>' . $i . '</strong></li>' : '<li><a href="' . sprintf($url_format, (($i - 1) * $per_page)) . '">' . $i . '</a></li>';
		}
	}
	
	$page_string .= '</ul>';
	
	$prev = $next = '';
	if ($on_page > 1) {
		$prev = '<a href="' . sprintf($url_format, (($on_page - 2) * $per_page)) . '">' . sprintf($user->lang[(($lang_prefix != '') ? $lang_prefix : '') . 'PAGES_PREV'], $per_page) . '</a>';
	}
	
	if ($on_page < $total_pages) {
		$next = '<a href="' . sprintf($url_format, ($on_page * $per_page)) . '">' . sprintf($user->lang[(($lang_prefix != '') ? $lang_prefix : '') . 'PAGES_NEXT'], $per_page) . '</a>';
	}
	
	if ($page_string == ' <strong>1</strong>') {
		$page_string = '';
	}
	
	$template->assign_vars(array(
		$prefix . 'PAGES_NUMS' => $page_string,
		$prefix . 'PAGES_PREV' => $prev,
		$prefix . 'PAGES_NEXT' => $next,
		$prefix . 'PAGES_ON' => sprintf($user->lang['PAGES_ON'], $on_page, max($total_pages, 1)))
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

function do_login($box_text = '', $need_auth = false, $extra_vars = false) {
	global $user, $template;
	
	if (empty($user->data)) {
		$user->init();
	}
	if (empty($user->lang)) {
		$user->setup();
	}
	
	if (isset($_POST['login'])) {
		$ref = request_var('ref', '');
		
		$template->assign_block_vars('error', array(
			'LASTPAGE' => ($ref != '') ? $ref : s_link())
		);
	}
	
	$s_hidden = array();
	if ($need_auth) {
		$s_hidden = array('admin' => 1);
	}
	
	$box_text = ($box_text != '') ? ((isset($user->lang[$box_text])) ? $user->lang[$box_text] : $box_text) : '';
	
	$template_vars = array(
		'IS_NEED_AUTH' => $need_auth,
		'IS_LOGIN' => isset($_POST['login']),
		'CUSTOM_MESSAGE' => $box_text,
		'S_HIDDEN_FIELDS' => s_hidden($s_hidden)
	);
	
	if ($extra_vars !== false) {
		$template_vars = array_merge($template_vars, $extra_vars);
	}
	
	page_layout('LOGIN2', 'login', $template_vars);
}

function get_file($f) {
	if (!f($f)) return false;
	
	if (!@file_exists($f)) {
		return w();
	}
	
	return array_map('trim', @file($f));
}

function exception($filename, $dynamics = false) {
	$a = implode("\n", get_file(ROOT . 'template/exceptions/' . $filename . '.htm'));
	
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
			
			return (isset($a)) ? $a : false;
			break;
	}
	
	$f = 'call_user_func' . ((!$arr) ? '_array' : '');
	return $f($name, $args);
}

function _pre($a, $d = false) {
	echo '<pre>';
	print_r($a);
	echo '</pre>';
	
	if ($d === true) {
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
	if (!f($a) || !is_string($a)) return array();
	
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
	static $included;
	
	if (!$included) {
		require_once(ROOT . 'interfase/emailer.php');
		$emailer = new emailer();
		
		$included = true;
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

function kernel_function($mode, $name, $param = false, $return_on_error = false) {
	switch ($mode) {
		case 'a':
			$fe = 'file';
			break;
		case 'f':
			$fe = 'function';
			break;
		case 'm':
			$fe = 'method';
			break;
		case 'c':
			$fe = 'class';
			break;
	}
	
	$fe .= '_exists';
	
	if ($mode == 'm') {
		$cfe = $fe($name, $param);
		$name = get_class($name);
	} else {
		$cfe = $fe($name);
	}
	
	if (!$cfe) {
		if ($return_on_error) {
			return false;
		}
		
		if ($mode == 'a') {
			$name = base64_encode(base64_encode($name));
		}
		
		if ($param !== false) {
			$name .= ', ' . (is_array($param) ? implode(', ', $param) : $param);
		}
		
		echo('<u>ERROR</u><br /><br />@ ~' . $fe . '( ' . $name . ' )<br /><br /><strong>info&#64;rockrepublik.net</strong>');
		exit;
	}
	
	return true;
}

function fatal_error_tables($msg) {
	return preg_replace('#([a-z_]+)\._([a-z]+)#is', '~\\2~', $msg);
}

function fatal_error($mode = '404', $bp_message = '') {
	global $user, $config;
	
	$current_page = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
	$error = 'La p&aacute;gina <strong>' . $current_page . '</strong> ';
	
	$username = (@method_exists($user, 'd')) ? $user->d('username') : '';
	$bp_message .= "\n\n" . $current_page . "\n\n" . $username;
	
	switch ($mode) {
		case 'mysql':
			if (isset($config['default_lang']) && isset($user->lang)) {
				// Send email notification
				require_once('./interfase/emailer.php');
				$emailer = new emailer();
				
				$emailer->from('info');
				$emailer->set_subject('MySQL error');
				$emailer->use_template('mcp_delete', $config['default_lang']);
				$emailer->email_address('info@rockrepublik.net');
				
				$emailer->assign_vars(array(
					'MESSAGE' => $bp_message,
					'TIME' => $user->format_date(time(), 'r'))
				);
				//$emailer->send();
				$emailer->reset();
			} else {
				$email_message = $bp_message . "\n\n" . date('r');
				$email_headers = "From: info@rockrepublik.net\nReturn-Path: " . $config['board_email'] . "\nMessage-ID: <" . md5(uniqid(time())) . "@" . $config['server_name'] . ">\nMIME-Version: 1.0\nContent-type: text/plain; charset=iso-8859-1\nContent-transfer-encoding: 8bit\nDate: " . date('r', time()) . "\nX-Priority: 3\nX-MSMail-Priority: Normal\n"; 
				//$result = @mail('info@rockrepublik.net', 'MySQL error', preg_replace("#(?<!\r)\n#s", "\n", $email_message), $email_headers, "-f{$config['board_email']}");
			}
			
			$title = 'Error del sistema';
			$error .= 'tiene un error';
			break;
		case '600':
			$title = 'Origen inv&aacute;lido';
			$error .= 'no puede ser accesada porque no se reconoce su IP de origen.';
			
			@error_log('[php client empty ip] File does not exist: ' . $current_page, 0);
			break;
		default:
			$title = 'Archivo no encontrado';
			$error .= 'no existe';
			
			status("404 Not Found");
			
			@error_log('[php client ' . $user->ip . ((isset($user->data['username'])) ? ' - ' . $user->data['username'] : '') . '] File does not exist: ' . $current_page, 0);
			break;
	}
	
	if ($mode != '600') {
		$error .= ', puedes regresar a<br /><a href="http://www.rockrepublik.net/">p&aacute;gina de inicio de Rock Republik</a> para encontrar informaci&oacute;n.';
		
		$error .= '<br /><br />' . $bp_message;
	}
	
	sql_close();
	
	$replaces = array(
		'PAGE_TITLE' => $title,
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
	global $template, $config, $user, $auth, $cache, $starttime;

	switch ($errno) {
		case E_NOTICE:
		case E_WARNING:
			//echo '<b>PHP Notice</b>: in file <b>' . $errfile . '</b> on line <b>' . $errline . '</b>: <b>' . $msg_text . '</b><br>';
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
				$template->set_template(ROOT.'template');
			}
			
			$custom_vars = array(
				'MESSAGE_TITLE' => $user->lang['INFORMATION'],
				'MESSAGE_TEXT' => (isset($user->lang[$msg_text])) ? $user->lang[$msg_text] : $msg_text
			);
			
			page_layout('INFORMATION', 'message', $custom_vars);
			
			break;
		default:
			// echo "<b>Another Error</b>: in file <b>" . basename($$errfile) . "</b> on line <b>$errline</b>: <b>$msg_text</b><br>";
			break;
	}
}

function redirect($url, $moved = false) {
	global $config;
	
	sql_close();
	
	// If relative path, prepend board url
	if (strpos($url, '//') === false) {
		$url = 'http://' . $config['server_name'] . trim($url);
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

// Meta refresh assignment
function meta_refresh($time, $url) {
	global $template;

	$template->assign_vars(array(
		'META' => '<meta http-equiv="refresh" content="' . $time . ';url=' . $url . '">')
	);
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
	global $config, $user, $cache, $starttime, $template;
	
	define('HEADER_INC', true);
	
	//
	// gzip_compression
	//
	$useragent = (isset($HTTP_SERVER_VARS['HTTP_USER_AGENT'])) ? $HTTP_SERVER_VARS['HTTP_USER_AGENT'] : getenv('HTTP_USER_AGENT');

	if (strstr($useragent,'compatible') || strstr($useragent,'Gecko')) {
		ob_start('ob_gzhandler');
	}
	
	// Artists meta
	$ub_meta = array();
	if (!defined('NO_A_META')) {
		if (!$ub_meta = $cache->get('ub_list')) {
			$sql = 'SELECT name
				FROM _artists
				ORDER BY name';
			$ub_meta = sql_rowset($sql, false, 'name');
			$cache->save('ub_list', $ub_meta);
		}
	}
	
	// Get unread items count
	$sql = 'SELECT COUNT(element) AS total
		FROM _members_unread
		WHERE user_id = ?';
	$unread_items = sql_field(sql_filter($sql, $user->d('user_id')), 'total', 0);
	
	// Context Menu Blocking
	$s_context_menu = '';
	if (!$user->d('is_founder')) {
		$s_context_menu = ' oncontextmenu="return false"';
	}
	
	//
	// Send headers
	//	
	header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
	header('Expires: 0');
	header('Pragma: no-cache');
	
	//
	// Footer
	//
	$u_login_logout = ($user->d('is_member')) ? 'signout' : 'signin';
	
	if (preg_match('#.*?my/confirm.*?#is', $user->d('session_page'))) {
		$user->data['session_page'] = '';
	}
	
	$common_vars = array(
		'PAGE_TITLE' => (isset($user->lang[$page_title])) ? $user->lang[$page_title] : $page_title,
		
		'U_SESSION' => s_link($u_login_logout),
		'U_PROFILE' => s_link('m', $user->d('username_base')),
		'U_REGISTER' => s_link('signup'),
		'U_EDITPROFILE' => s_link('my', 'profile'),
		'U_SPASSWORD' => s_link('my', 'password'),
		'U_FAQ' => s_link('faq'),
		'U_WHATS_NEW' => s_link('new'),
		'U_COVER' => s_link(),
		'U_ARTISTS'	=> s_link('a'),
		'U_RADIO' => s_link('radio'),
		'U_CHAT' => s_link('chat'),
		'U_NEWS' => s_link('news'),
		'U_EVENTS' => s_link('events'),
		'U_FORUM' => s_link('board'),
		'U_ART' => s_link('art'),
		'U_COMMUNITY'	=> s_link('community'),
		'U_ALLIES'	=> s_link('allies'),		
		'U_WWW' => s_link('bounce'),
		'U_TOS' => s_link('tos'),
		'U_HELP' => s_link('help'),
		'U_RSS_NEWS' => s_link('rss', 'news'),
		'U_RSS_ARTISTS' => s_link('rss', 'artists'),
	
		'S_KEYWORDS' => $config['meta_keys'],
		'S_DESCRIPTION' => $config['meta_desc'],
		'S_REDIRECT' => $user->d('session_page'),
		'S_CONTEXT_MENU' => $s_context_menu,
		'S_USERNAME' => $user->d('username'),
		'S_CONTROLPANEL' => (!isset($template->vars['S_CONTROLPANEL'])) ? (($user->d('is_member') && $user->d('user_auth_control')) ? s_link('control') : '') : $template->vars['S_CONTROLPANEL'],
		'S_UNREAD_ITEMS' => (($unread_items == 1) ? sprintf($user->lang['UNREAD_ITEM_COUNT'], $unread_items) : sprintf($user->lang['UNREAD_ITEMS_COUNT'], $unread_items)),
		'S_AP_POINTS' => (($user->d('user_points') == 1) ? sprintf($user->lang['AP_POINT'], $user->d('user_points')) : sprintf($user->lang['AP_POINTS'], $user->d('user_points'))),
		
		'F_SQL' => ($user->d('is_founder')) ? sql_queries() . 'q | ' : '',
		'JS_KEEPALIVE' => $js_keepalive
	);
	
	if ($custom_vars !== false) {
		$common_vars += $custom_vars;
	}
	
	$mtime = explode(' ', microtime());
	$common_vars['F_TIME'] = sprintf('%.2f', ($mtime[0] + $mtime[1] - $starttime));
	
	
	//
	// End template output
	//
	$template->assign_vars($common_vars);
	
	$template->set_filenames(array(
		'body' => $htmlpage . '.htm')
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
		$include_file = './interfase/sidebar/' . $each_file . '.php';
		if (!file_exists($include_file)) {
			continue;
		}
		
		@require_once($include_file);
	}
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
	for ($n = 0, $end = strlen($str); $n < $end; $n+=2) {
		$newstring .=  pack('C', hexdec(substr($str, $n, 2)));
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

function get_a_imagepath($path, $filename, $folders) {
	foreach ($folders as $row) {
		$a = $path . '/' . $row . '/' . $filename;
		if (@file_exists('..' . $a)) {
			return $a;
		}
	}
	return false;
}

function check_www($url) {
	global $config;
	
	$domain = str_replace('http://', '', $url);
	if (strstr($domain, '?')) {
		$domain_e = explode('/', $domain);
		$domain = $domain_e[0];
		if ($domain == $config['server_name']) {
			$domain .= '/' . $domain_e[1];
		}
	}
	
	if ($check = @fopen('http://' . $domain, 'r')) {
		@fclose($check);
		return true;
	}
	
	return false;
}

function _die($str) {
	sql_close();
	
	echo $str;
	exit;
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
	if(!curl_errno($socket)) {
		$info = curl_getinfo($socket);
	} else {
		$info = curl_error($socket);
	}
	curl_close($socket);
	
	return $call;
}

function _shoutcast() {
	global $config;
	
	$response = false;
	
	if (!$connection = @fsockopen($config['shoutcast_host'], $config['shoutcast_port'], $errno, $errstr, 5)) {
		return $response;
	}
	
	$s_response = '';
	
	fputs($connection, 'GET /admin.cgi?pass=' . $config['shoutcast_code'] . "&mode=viewxml HTTP/1.0\r\nUser-Agent: SHOUTcast Song Status (Mozilla Compatible)\r\n\r\n");
	while (!feof($connection)) {
		$s_response .= fgets($connection, 1000);
	}
	@fclose($connection);
	unset($connection);
	
	require_once(ROOT . 'interfase/xml.php');
	$shoutcast = XML_unserialize(strstr($s_response, '<?xml'));
	$shoutcast = $shoutcast['SHOUTCASTSERVER'];
	
	return $shoutcast;
}

function html_entity_decode_utf8($string) {
	static $trans_tbl;
	
	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'code2utf(hexdec("\\1"))', $string);
	$string = preg_replace('~&#([0-9]+);~e', 'code2utf(\\1)', $string);
	
	// replace literal entities
	if (!isset($trans_tbl)) {
		$trans_tbl = array();
		foreach (get_html_translation_table(HTML_ENTITIES) as $val => $key) {
			$trans_tbl[$key] = utf8_encode($val);
		}
	}
	
	return strtr($string, $trans_tbl);
}

function artist_build($ary) {
	return implode('/', $ary);
}

function artist_path($alias, $id, $build = true, $check = false) {
	global $config;
	
	$response = array($alias{0}, $alias{1}, $id);
	
	if ($check) {
		artist_check($response);
	}
	
	if ($build) {
		$response = $config['artists_path'] . artist_build($response) . '/';
	}
	
	return $response;
}

function artist_check($ary) {
	global $config;
	
	$fullpath = $config['artists_path'];
	
	foreach ($ary as $row) {
		$fullpath .= $row . '/';
		
		if (!@file_exists($fullpath)) {
			if (!@mkdir($fullpath, $config['mask'])) {
				return false;
			}
			@chmod($fullpath, $config['mask']);
		}
	}
	
	return true;
}

function upload_maxsize() {
	return intval(ini_get('upload_max_filesize')) * 1048576;
}

function friendly($s) {
	$s = preg_replace("`\[.*\]`U" ,"", $s);
	$s = preg_replace('#&([a-zA-Z]+)acute;#is', '\\1', $s);
	$s = preg_replace('`&(amp;)?#?[a-z0-9]+;`i', '-', $s);
	$s = htmlentities($s, ENT_COMPAT, 'utf-8');
	$s = preg_replace("`&([a-z])(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig|quot|rsquo);`i", "\\1", $s);
	$s = preg_replace(array("`[^a-z0-9]`i", "`[-]+`") , "-", $s);
	
	return strtolower(trim($s, '-'));
}

// Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
function code2utf($num) {
	if ($num < 128) return chr($num);
	if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
	if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	return '';
}

if (!function_exists('bcdiv')) {
	function bcdiv($first, $second, $scale = 0) {
		$res = $first / $second;
		return round( $res, $scale );
	}
}

?>
