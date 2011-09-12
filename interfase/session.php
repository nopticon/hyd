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
class session {
	public $session_id = '';
	public $cookie_data = array();
	public $data = array();
	public $browser = '';
	public $ip = '';
	public $page = '';
	public $time = 0;
	
	public function init($update_page = true, $bypass_empty_ip = false) {
		global $config;
		
		$this->time = time();
		$this->browser = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$this->page = requested_page();
		$this->ip = (!empty($_SERVER['REMOTE_ADDR'])) ? htmlspecialchars($_SERVER['REMOTE_ADDR']) : '';
		$this->ip = (!empty($this->ip)) ? $this->ip : htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']);
		
		if (empty($this->ip) && !$bypass_empty_ip) {
			fatal_error('600');
		}
		
		if (!empty($this->ip) && $bypass_empty_ip) {
			//redirect(s_link());
		}
		
		$this->cookie_data = array();
		if (isset($_COOKIE[$config['cookie_name'] . '_sid']) || isset($_COOKIE[$config['cookie_name'] . '_u'])) {
			$this->cookie_data['u'] = request_var($config['cookie_name'] . '_u', 0);
			$this->session_id = request_var($config['cookie_name'] . '_sid', '');
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
				
				if ($u_ip == $s_ip && $this->data['session_browser'] == $this->browser) {
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
					$this->data['is_member'] = ($this->data['user_id'] != GUEST/* && ($this->data['user_type'] == USER_NORMAL || $this->data['user_type'] == USER_FOUNDER)*/) ? true : false;
					$this->data['is_bot'] = (!$this->data['is_member'] && $this->data['user_id'] != GUEST) ? true : false;
					$this->data['is_founder'] = ($this->data['user_id'] != GUEST && $this->data['user_type'] == USER_FOUNDER && !$this->data['is_bot']) ? true : false;
					
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
	function session_create($user_id = false, $set_admin = false, $update_page = true)
	{
		global $config;

		$this->data = array();
		
		// Garbage collection ... remove old sessions updating user information
		// if necessary. It means (potentially) 11 queries but only infrequently
		if ($this->time > $config['session_last_gc'] + $config['session_gc'])
		{
			$this->session_gc();
		}
		
		/**
		* Here we do a bot check. We loop through the list of bots defined by 
		* the admin and see if we have any useragent and/or IP matches. If we 
		* do, this is a bot, act accordingly
		*/		
		$bot = false;
		$active_bots = array();
		obtain_bots($active_bots);
		
		foreach ($active_bots as $row)
		{
			if ($row['bot_agent'] && strpos(strtolower($this->browser), strtolower($row['bot_agent'])) !== false)
			{
				$bot = $row['user_id'];
			}
			
			// If ip is supplied, we will make sure the ip is matching too...
			if ($row['bot_ip'] && ($bot || !$row['bot_agent']))
			{
				// Set bot to false, then we only have to set it to true if it is matching
				$bot = false;

				foreach (explode(',', $row['bot_ip']) as $bot_ip)
				{
					if (strpos($this->ip, $bot_ip) === 0)
					{
						$bot = (int) $row['user_id'];
						break;
					}
				}
			}

			if ($bot)
			{
				break;
			}
		}
		
		// If we've been passed a user_id we'll grab data based on that
		if ($user_id !== false)
		{
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
		if (!sizeof($this->data) || !is_array($this->data))
		{
			$this->cookie_data['u'] = ($bot) ? $bot : GUEST;

			$sql = 'SELECT *
				FROM _members
				WHERE user_id = ?';
			$this->data = sql_fieldrow(sql_filter($sql, $this->cookie_data['u']));
		}
		
		if ($this->data['user_id'] != GUEST)
		{
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

			$this->data['session_last_visit'] = (isset($this->data['session_time']) && $this->data['session_time']) ? $this->data['session_time'] : (($this->data['user_lastvisit']) ? $this->data['user_lastvisit'] : $this->time);
		} else {
			$this->data['session_last_visit'] = $this->time;
		}
		
		// At this stage we should have a filled data array, defined cookie u and k data.
		// data array should contain recent session info if we're a real user and a recent
		// session exists in which case session_id will also be set

		// Is user banned? Are they excluded? Won't return on ban, exists within method
		// @todo Change to !$this->data['user_type'] & USER_FOUNDER && !$this->data['user_type'] & USER_BOT in time
		if ($this->data['user_type'] != USER_FOUNDER) {
			$this->check_ban();
		}
		
		//
		// Do away with ultimately?
		$this->data['is_member'] = (!$bot && $this->data['user_id'] != GUEST) ? true : false;
		$this->data['is_bot'] = ($bot) ? true : false;
		$this->data['is_founder'] = ($this->data['user_id'] != GUEST && $this->data['user_type'] == USER_FOUNDER && !$this->data['is_bot']) ? true : false;
		//
		//
		
		// Create or update the session
		$sql_ary = array(
			'session_user_id' => (int) $this->data['user_id'],
			'session_start' => (int) $this->time,
			'session_last_visit' => (int) $this->data['session_last_visit'],
			'session_time' => (int) $this->time,
			'session_browser' => (string) $this->browser,
			'session_ip' => (string) $this->ip,
			'session_admin' => ($set_admin) ? 1 : 0
		);
		
		if ($update_page) {
			$sql_ary['session_page'] = (string) $this->page;
			$this->data['session_page'] = $sql_ary['session_page'];
		}
		
		$sql = 'UPDATE _sessions SET ??
			WHERE session_id = ?';
		sql_query(sql_filter($sql, sql_build('UPDATE', $sql_ary, $this->session_id)));

		if (!$this->session_id || !sql_affectedrows()) {
			$this->session_id = $this->data['session_id'] = md5(unique_id());

			$sql_ary['session_id'] = (string) $this->session_id;
			
			$sql = 'INSERT INTO _sessions' . sql_build('INSERT', $sql_ary);
			sql_query($sql);
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
	
	function register_ip()
	{
		$insert = array(
			'log_user_id' => (int) $this->data['user_id'],
			'log_session' => $this->session_id,
			'log_ip' => $this->ip,
			'log_agent' => $this->browser,
			'log_time' => (int) $this->time,
			'log_endtime' => 0
		);
		$sql = 'INSERT INTO _members_iplog' . sql_build('INSERT', $insert);
		sql_query($sql);
		
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
	function session_kill()
	{
		global $config;

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
			$this->data = array();			
			
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
	function session_gc()
	{
		global $config;
		
		// Get expired sessions, only most recent for each user
		$sql = 'SELECT session_id, session_user_id, session_page, MAX(session_time) AS recent_time
			FROM _sessions
			WHERE session_time < ?
			GROUP BY session_user_id, session_page
			LIMIT 5';
		$result = sql_rowset(sql_filter($sql, ($this->time - $config['session_length'])));
		
		$del_user_id = '';
		$del_sessions = 0;
		
		foreach ($result as $row) {
			if ($row['session_user_id'] != GUEST)
			{
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
			sql_query(sql_filter($sql, $del_user_id, ($this->time - $config['session_length'])));
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
	function set_cookie($name, $cookiedata, $cookietime) {
		global $config;
		
		if ($config['cookie_domain'] != 'localhost') {
			setcookie($config['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $config['cookie_path'], $config['cookie_domain']);
		} else {
			setcookie($config['cookie_name'] . '_' . $name, $cookiedata, $cookietime, $config['cookie_path']);
		}
	}

	/**
	* Check for banned user
	*
	* Checks whether the supplied user is banned by id, ip or email. If no parameters
	* are passed to the method pre-existing session data is used. This routine does 
	* not return on finding a banned user, it outputs a relevant message and stops 
	* execution.
	*/
	function check_ban($user_id = false, $user_ip = false, $user_email = false) {
		global $config;
		
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
			if ((!empty($row['ban_userid']) && intval($row['ban_userid']) == $user_id) ||
				(!empty($row['ban_ip']) && preg_match('#^' . str_replace('*', '.*?', $row['ban_ip']) . '$#i', $user_ip)) ||
				(!empty($row['ban_email']) && preg_match('#^' . str_replace('*', '.*?', $row['ban_email']) . '$#i', $user_email)))
			{
				if (!empty($row['ban_exclude'])) {
					$banned = false;
					break;
				} else {
					$banned = true;
				}
			}
		}

		if ($banned) {
			// Initiate environment ... since it won't be set at this stage
			$this->setup();

			fatal_error();
			/*
			// Determine which message to output
			$till_date = (!empty($row['ban_end'])) ? $this->format_date($row['ban_end']) : '';
			$message = (!empty($row['ban_end'])) ? 'BOARD_BAN_TIME' : 'YOU_ARE_BANNED';

			$message = sprintf($this->lang[$message], $till_date, '<a href="mailto:' . $config['board_contact'] . '">', '</a>');
			// More internal HTML ...
			$message .= (!empty($row['ban_show_reason'])) ? '<br /><br />' . sprintf($this->lang['BOARD_BAN_REASON'], $row['ban_show_reason']) : '';
			trigger_error($message);
			*/
		}
		
		return false;
	}
	
	function d($d = false, $v = false) {
		if ($d === false) {
			$r = $this->data;
			unset($r['user_password']);
			return $r;
		}
		
		if ($v !== false) {
			$this->data[$d] = $v;
		}
		
		return (isset($this->data[$d])) ? $this->data[$d] : false;
	}
}

/**
* Base user class
*
* This is the overarching class which contains (through session extend)
* all methods utilised for user functionality during a session.
*/
class user extends session
{
	var $lang = array();
	var $help = array();
	var $theme = array();
	var $unr = array();
	var $date_format;
	var $timezone;
	var $dst;

	var $lang_name;
	var $lang_path;
	var $img_lang;

	var $keyoptions = array('viewimg' => 0, 'viewsigs' => 3, 'viewavatars' => 4);
	var $keyvalues = array();

	function setup($lang_set = false, $style = false)
	{
		global $template, $config, $auth, $cache;

		if ($this->data['user_id'] != GUEST)
		{
			$this->lang_name = (file_exists(ROOT.'language/' . $this->data['user_lang'] . "/main.php")) ? $this->data['user_lang'] : $config['default_lang'];
			$this->lang_path = ROOT.'language/' . $this->lang_name . '/';

			$this->date_format = $this->data['user_dateformat'];
			$this->timezone = $this->data['user_timezone'] * 3600;
			$this->dst = $this->data['user_dst'] * 3600;
		}
		else
		{
			$this->lang_name = $config['default_lang'];
			$this->lang_path = ROOT.'language/' . $this->lang_name . '/';
			$this->date_format = $config['default_dateformat'];
			$this->timezone = $config['board_timezone'] * 3600;
			$this->dst = $config['board_dst'] * 3600;

			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']))
			{
				$accept_lang_ary = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach ($accept_lang_ary as $accept_lang)
				{
					// Set correct format ... guess full xx_YY form
					$accept_lang = substr($accept_lang, 0, 2) . '_' . strtoupper(substr($accept_lang, 3, 2));
					if (file_exists(ROOT.'language/' . $accept_lang . "/main.php"))
					{
						$this->lang_name = $config['default_lang'] = $accept_lang;
						$this->lang_path = ROOT.'language/' . $accept_lang . '/';
						break;
					}
					else
					{
						// No match on xx_YY so try xx
						$accept_lang = substr($accept_lang, 0, 2);
						if (file_exists(ROOT . 'language/' . $accept_lang . "/main.php"))
						{
							$this->lang_name = $config['default_lang'] = $accept_lang;
							$this->lang_path = ROOT.'language/' . $accept_lang . '/';
							break;
						}
					}
				}
			}
		}

		// We include common language file here to not load it every time a custom language file is included
		$lang = &$this->lang;
		if ((include($this->lang_path . "main.php")) === FALSE)
		{
			die("Language file " . $this->lang_path . "main.php" . " couldn't be opened.");
		}

		$this->add_lang($lang_set);
		unset($lang_set);

		$template->set_template(ROOT.'template');
		require(ROOT . 'template/config.cfg');

		// Is board disabled and user not an admin or moderator?
		// TODO
		// New ACL enabling board access while offline?
		if ($config['board_disable'] && $this->data['user_id'] != 2)
		{
			$page_html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<title>Rock Republik Networks</title>
<link rel="stylesheet" type="text/css" href="http://www.rockrepublik.net/net/styles/pe.css" />
</head>

<body>
<img src="http://www.rockrepublik.net/net/access/error.gif" width="350" height="146" alt="" border="0" />

<h1>Temporalmente fuera de servicio.</h1>
<div>Estamos realizando cambios en el sistema.<br /><a href="/">Intenta en un momento</a></div>
</body>
</html>';
			
			header("HTTP/1.1 503 Service Temporarily Unavailable");
			header("Status: 503 Service Temporarily Unavailable");
			header("Retry-After: 3600");

			sql_close();
			
			echo $page_html;
			exit;
		}

		return;
	}

	// Add Language Items
	function add_lang($lang_set)
	{
		if (is_array($lang_set))
		{
			foreach ($lang_set as $key => $lang_file)
			{
				// Please do not delete this line.
				// We have to force the type here, else [array] language inclusion will not work
				$key = (string) $key;

				if (!is_array($lang_file))
				{
					$this->set_lang($this->lang, $this->help, $lang_file);
				}
				else
				{
					$this->add_lang($lang_file);
				}
			}
			unset($lang_set);
		}
		else if ($lang_set)
		{
			$this->set_lang($this->lang, $this->help, $lang_set);
		}
	}

	function set_lang(&$lang, &$help, $lang_file)
	{
		if ( (@include $this->lang_path . "$lang_file.php") === FALSE )
		{
			trigger_error("Language file " . $this->lang_path . "$lang_file.php" . " couldn't be opened.");
		}
	}

	function format_date($gmepoch, $format = false, $forcedate = false)
	{
		static $lang_dates, $midnight;

		if (empty($lang_dates))
		{
			foreach ($this->lang['datetime'] as $match => $replace)
			{
				$lang_dates[$match] = $replace;
			}
		}

		$format = (!$format) ? $this->date_format : $format;

		if (!$midnight)
		{
			list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $this->timezone + $this->dst));
			$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $this->timezone - $this->dst;
		}

		if (strpos($format, '|') === false || (!($gmepoch > $midnight && !$forcedate) && !($gmepoch > $midnight - 86400 && !$forcedate)))
		{
			return strtr(@gmdate(str_replace('|', '', $format), $gmepoch + $this->timezone + $this->dst), $lang_dates);
		}

		if ($gmepoch > $midnight && !$forcedate)
		{
			$format = substr($format, 0, strpos($format, '|')) . '||' . substr(strrchr($format, '|'), 1);
			return str_replace('||', $this->lang['datetime']['TODAY'], strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $lang_dates));
		}
		else if ($gmepoch > $midnight - 86400 && !$forcedate)
		{
			$format = substr($format, 0, strpos($format, '|')) . '||' . substr(strrchr($format, '|'), 1);
			return str_replace('||', $this->lang['datetime']['YESTERDAY'], strtr(@gmdate($format, $gmepoch + $this->timezone + $this->dst), $lang_dates));
		}
	}

	function get_iso_lang_id() {
		global $config;

		if (isset($this->lang_id)) {
			return $this->lang_id;
		}

		if (!$this->lang_name) {
			$this->lang_name = $config['default_lang'];
		}

		$sql = 'SELECT lang_id
			FROM _lang
			WHERE lang_iso = ?';

		return (int) sql_field(sql_filter($sql, $this->lang_name), 'lang_id', 0);
	}
	
	function init_ranks()
	{
		global $cache;
		
		if (!$ranks = $cache->get('ranks')) {
			$sql = 'SELECT *
				FROM _ranks
				ORDER BY rank_special DESC, rank_min';
			$ranks - sql_rowset($sql);
			$cache->save('ranks', $ranks);
		}
		
		return $ranks;
	}

	function _team_auth_list($mode = '')
	{
		global $cache;
		
		switch ($mode)
		{
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
				if (!$response = $cache->get('team_radio'))
				{
					$sql = 'SELECT DISTINCT member_id
						FROM _team_members
						WHERE team_id = 4';
					$response = sql_rowset($sql, false, 'member_id');
					$cache->save('team_radio', $response);
				}
				break;
			case 'all':
			default:
				if (!$response = $cache->get('team_all'))
				{
					$sql = 'SELECT DISTINCT member_id
						FROM _team_members
						ORDER BY member_id';
					$response = sql_rowset($sql, false, 'member_id');
					$cache->save('team_all', $response);
				}
				break;
		}
		
		$response = array_merge($response, array(2, 3, 1433, 4830, 5777));
		return $response;
	}
	
	function _team_auth($mode = '', $user_id = false)
	{
		global $cache;
		
		if ($user_id === false)
		{
			$user_id = $this->data['user_id'];
		}
		
		$response = false;
		if ($this->data['is_member'])
		{
			switch ($mode)
			{
				case 'founder':
					$response = $this->data['is_founder'];
					break;
				case 'mod':
				case 'radio':
				case 'colab':
					$mods = $this->_team_auth_list($mode);
					if (sizeof($mods)) {
						$response = in_array($user_id, $mods);
					}
					break;
				case 'all':
				default:
					$all = $this->_team_auth_list($mode);
					if (sizeof($all))
					{
						$response = in_array($user_id, $all);
					}
					break;
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
	
	U			USERS									ADM
	A			ARTISTS								-
	AF		ARTISTS FANS					ADM / USER_MOD
	E			EVENTS								-
	N			NEWS									-
	NP		NEWS MESSAGES					ADM / USER_MOD / POSTERS
	P			POSTS									-
	D			DOWNLOADS							-
	C			ARTISTS MESSAGES			ADM / USER_MOD / USER_FAN
	M			DOWNLOADS MESSAGES		ADM / USER_MOD / USER_FAN
	W			WALLPAPERS / ART			-
	F			ARTISTS NEWS					-
	I			ARTISTS IMAGES				-
	
	*/
	function save_unread($element, $item, $where_id = 0, $reply_to = 0, $reply_to_return = true, $update_rows = false) {
		static $from_lastvisit;
		
		if (!$element || !$item || in_array($element, array(UH_EP, UH_NP, UH_W))) {
			return;
		}
		
		if ($reply_to) {
			if ($reply_to == $this->data['user_id']) {
				return;
			}
			
			$sql_items = 'SELECT user_id
				FROM _members_unread
				WHERE element = ?
					AND item = ?
					AND user_id = ?
				ORDER BY user_id';
			if (!$row = sql_field(sql_filter($sql, $element, $item, $reply_to))) {
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
		
		$sql_in = array();
		switch ($element)
		{
			case UH_AF:
				$sql = 'SELECT m.user_id
					FROM _artists_auth a, _members m
					WHERE a.ub = ' . (int) $where_id . '
						AND a.user_id = m.user_id
						AND m.user_id <> ' . (int) $this->data['user_id'] . '
					ORDER BY m.user_id';
				break;
			case UH_C:
			case UH_M:
				$sql = 'SELECT m.user_id 
					FROM _artists_auth a, _members m
					WHERE a.ub = ?
						AND a.user_id = m.user_id';
				$sql_in = sql_rowset(sql_filter($sql, $where_id));
				
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

				$sql = 'SELECT user_id
					FROM _members
					WHERE (user_type IN (??, ??)' . ((sizeof($sql_in)) ? ' OR user_id IN (' . implode(',', $sql_in) . ')' : '') . ')
						AND user_type NOT IN (??, ??)
						AND user_id <> ?
						AND user_lastvisit > ?
					ORDER BY user_id';
				$sql = sql_filter($sql, USER_FOUNDER, USER_ADMIN, USER_IGNORE, USER_INACTIVE, $this->data['user_id'], $from_lastvisit);
				break;
			case UH_B:
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_type NOT IN (??, ??)
						AND user_id = ?
					ORDER BY user_id';
				$sql = sql_filter($sql, USER_IGNORE, USER_INACTIVE, $where_id);
				break;
			case UH_U:
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_type IN (??)
						AND user_type NOT IN (??, ??)
						AND user_id <> ?
						AND user_active = 1 
					ORDER BY user_id';
				$sql = sql_filter($sql, USER_FOUNDER, USER_IGNORE, USER_INACTIVE, $item);
				break;
			default:
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_type NOT IN (??, ??)
						AND user_id <> ?
						AND user_lastvisit > 0 
						AND user_lastvisit > ?
					ORDER BY user_id';
				$sql = sql_filter($sql, USER_IGNORE, USER_INACTIVE, $this->data['user_id'], $from_lastvisit);
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
	
	function insert_unread($uid, $cat, $el)
	{
		$row = array(
			'user_id' => (int) $uid,
			'element' => (int) $cat,
			'item' => (int) $el,
			'datetime' => (int) $this->time
		);
		$sql = 'INSERT LOW_PRIORITY INTO _members_unread' . sql_build('INSERT', $row);
		sql_query($sql);
	}
	
	function update_unread($cat, $el)
	{
		global $user;
		
		$sql = 'UPDATE _members_unread SET datetime = ?
			WHERE element = ?
				AND item = ?';
		sql_query(sql_filter($sql, $user->time, $cat, $el));
	}
	
	function get_unread($element, $item)
	{
		if (!$this->data['is_member'] || !$element || !$item)
		{
			return false;
		}
		
		if (!sizeof($this->unr))
		{
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
	
	function delete_unread($element, $item)
	{
		if (!$element || !$item) {
			return false;
		}
		
		$items = (is_array($item)) ? implode(',', array_map('intval', $item)) : (int) $item;
		
		if (!empty($items)) {
			$sql = 'DELETE LOW_PRIORITY FROM _members_unread
				WHERE user_id = ?
					AND element = ?
					AND item IN (??)';
			sql_query(sql_filter($sql, $this->data['user_id'], $element, $items));
			
			return true;
		}
		
		return false;
	}
	
	function delete_all_unread($element, $item)
	{
		if (!$element || !$item) {
			return false;
		}
		
		$items = (is_array($item)) ? implode(',', array_map('intval', $item)) : (int) $item;
		
		$sql = 'DELETE LOW_PRIORITY FROM _members_unread
			WHERE element = ?
				AND item IN (??)';
		sql_query(sql_filter($sql, $element, $items));
		
		return true;
	}
	
	//
	// POINTS SYSTEM
	//
	function points_add($n, $uid = false)
	{
		global $user;
		
		if ($uid === false)
		{
			$uid = $this->data['user_id'];
			$block = $this->data['user_block_points'];
		}
		else
		{
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
	
	function points_remove($n, $uid = false)
	{
		if ($uid === false) {
			$uid = $this->data['user_id'];
		}
		
		$sql = 'UPDATE _members
			SET user_points = user_points - ??
			WHERE user_id = ?';
		sql_query(sql_filter($sql, $n, $uid));
		
		return;
	}
	
	//
	// END - USER HISTORY FUNCTIONS
	//
	
	function check_ref($block_ud = FALSE, $auto_block = FALSE)
	{
		global $config;
		
		$url = (getenv('HTTP_REFERER')) ? trim(getenv('HTTP_REFERER')) : trim($_SERVER['HTTP_REFERER']);
		$url = $this->clean_value($url);
		if ($url == '')
		{
			return;
		}
		
		$domain = explode('?', str_replace(array('http://', 'https://'), '', $url));
		$domain = trim($domain[0]);
		$domain = explode('/', $domain);
		$excref = $domain[0] . '/' . $domain[1];
		$domain = trim($domain[0]);
		
		if (($domain == '') || preg_match('#^.*?' . $config['server_name'] . '.*?$#i', $domain))
		{
			return;
		}
		
		if (is_array($this->config['exclude_refs']))
		{
			$this->config['exclude_refs'] = $this->config['exclude_refs'][0];
		}
		
		if ($this->config['exclude_refs'] != '')
		{
			$this->config['exclude_refs'] = explode("\n", $this->config['exclude_refs']);
			
			foreach ($this->config['exclude_refs'] as $e_domain)
			{
				if (strstr($e_domain, 'www.'))
				{
					$this->config['exclude_refs'][] = str_replace('www.', '', $e_domain);
				}
			}
		}
		
		if (in_array($excref, $this->config['exclude_refs']))
		{
			return;
		}
		
		$not_allowed_ref = TRUE;
		if (in_array($excref, $this->config['exclude_refs']))
		{
			$domain = $excref;
			$not_allowed_ref = FALSE;
		}
		
		$request = $this->clean_value($HTTP_SERVER_VARS['REQUEST_URI']);
		$auto_block = ($auto_block) ? 1 : 0;
		
		$insert = TRUE;
		$update = FALSE;
		$banned = FALSE;
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
					$sql_banned = ", banned = " . intval($auto_block);
				}
				
				$sql = 'UPDATE _ref SET request = ?' . $sql_banned . ', views = views + 1, last_datetime = ?, last_ip = ?
					WHERE domain = ?
						AND url = ?';
				sql_query(sql_filter($sql, $request, $datetime, $user_ip, $domain, $url));
			}
		}
		
		if ($insert)
		{
			if ($group_id == '')
			{
				$group_id = md5(uniqid(time()));
			}
			
			$sql_insert = array(
				'group_id' => $group_id,
				'domain' => $domain,
				'url' => $url,
				'request' => $request,
				'banned' => $auto_block,
				'views' => 1,
				'datetime' => $datetime,
				'last_datetime' => $datetime,
				'last_ip' => $user_ip
			);
			$sql = 'INSERT INTO _ref' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
		}
		
		if ($not_allowed_ref) {
			if ($banned) {
				redirect('net/access?2');
			}
			
			if ($block_ud) {
				redirect(s_link());
			}
		}
		
		return;
	}

}

class auth
{
	var $founder = false;
	var $data = array();
	
	//
	// @ $member_id
	//
	function query($module = false, $member_id = false)
	{
		if ($member_id === false)
		{
			global $user;
			$member_id = $user->data['user_id'];
			
			if ($user->data['is_founder'])
			{
				return true;
			}
		}
		
		if (!isset($this->data[$member_id]))
		{
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
		
		if ((isset($this->data[$member_id]) && is_array($this->data[$member_id]) && sizeof($this->data[$member_id])) || $user->data['is_founder']) {
			if ($module !== false && empty($this->data[$member_id]['a_' . $module])) {
				return false;
			}
			
			return true;
		}
		
		return false;
	}
	
	function option($ary, $member_id = false) {
		global $user;
		
		if ($member_id === false) {
			$member_id = $user->data['user_id'];
			
			if ($user->data['is_founder']) {
				return true;
			}
		}
		
		if (!isset($this->data[$member_id]) || !is_array($ary)) {
			return;
		}
		
		$a = '';
		foreach ($ary as $i => $k) {
			if (!$i) $k = 'a_' . $k;
			$a .= "['" . $k . "']";
		}
		
		eval('$b = (isset($this->data[$member_id]' . $a . ')) ? TRUE : FALSE;');
		return $b;
	}
	
	function forum($type, $forum_id, $f_access = false)
	{
		global $config, $user;
		
		switch ($type)
		{
			case AUTH_ALL:
				$a_sql = 'a.auth_view, a.auth_read, a.auth_post, a.auth_reply, a.auth_announce, a.auth_vote, a.auth_pollcreate';
				$auth_fields = array('auth_view', 'auth_read', 'auth_post', 'auth_reply', 'auth_announce', 'auth_vote', 'auth_pollcreate');
				break;
			case AUTH_VIEW:
				$a_sql = 'a.auth_view';
				$auth_fields = array('auth_view');
				break;
			case AUTH_READ:
				$a_sql = 'a.auth_read';
				$auth_fields = array('auth_read');
				break;
			case AUTH_POST:
				$a_sql = 'a.auth_post';
				$auth_fields = array('auth_post');
				break;
			case AUTH_REPLY:
				$a_sql = 'a.auth_reply';
				$auth_fields = array('auth_reply');
				break;
			case AUTH_ANNOUNCE:
				$a_sql = 'a.auth_announce';
				$auth_fields = array('auth_announce');
				break;
			case AUTH_POLLCREATE:
				$a_sql = 'a.auth_pollcreate';
				$auth_fields = array('auth_pollcreate');
				break;
			case AUTH_VOTE:
				$a_sql = 'a.auth_vote';
				$auth_fields = array('auth_vote');
				break;
			default:
				break;
		}
		
		//
		// If f_access has been passed, or auth is needed to return an array of forums
		// then we need to pull the auth information on the given forum (or all forums)
		//
		if ($f_access === false)
		{
			$forum_match_sql = ($forum_id != AUTH_LIST_ALL) ? sql_filter('WHERE a.forum_id = ?', $forum_id) : '';
			$sql_fetchrow = ($forum_id != AUTH_LIST_ALL) ? 'sql_fieldrow' : 'sql_rowset';
			
			$sql = 'SELECT a.forum_id, ' . $a_sql . '
				FROM _forums a
				' . $forum_match_sql;
			if (!$f_access = $sql_fetchrow($sql)) {
				return array();
			}
		}
	
		//
		// If the user isn't logged on then all we need do is check if the forum
		// has the type set to ALL, if yes they are good to go, if not then they
		// are denied access
		//
		$u_access = array();
		if ($user->data['is_member'])
		{
			$forum_match_sql = ($forum_id != AUTH_LIST_ALL) ? sql_filter('AND a.forum_id = ?', $forum_id) : '';
	
			$sql = 'SELECT a.forum_id, ' . $a_sql . ', a.auth_mod
				FROM _auth_access a, _members_group ug
				WHERE ug.user_id = ?
					AND ug.user_pending = 0
					AND a.group_id = ug.group_id
					' . $forum_match_sql;
			$result = sql_rowset(sql_filter($sql, $user->data['user_id']));
			
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
		
		$this->founder = ($user->data['is_founder']) ? TRUE : FALSE;
		
		$auth_user = array();
		foreach ($auth_fields as $a_key)
		{
			if ($forum_id != AUTH_LIST_ALL)
			{
				$value = $f_access[$a_key];
				
				$custom_mod = forum_for_team($forum_id);
	
				switch ($value)
				{
					case AUTH_ALL:
						$auth_user[$a_key] = TRUE;
						$auth_user[$a_key . '_type'] = $user->lang['AUTH_ANONYMOUS_USERS'];
						break;
					case AUTH_REG:
						$auth_user[$a_key] = ($user->data['is_member']) ? TRUE : FALSE;
						$auth_user[$a_key . '_type'] = $user->lang['AUTH_REGISTERED_USERS'];
						break;
					case AUTH_ACL:
						$auth_user[$a_key] = ($user->data['is_member']) ? $this->check_user(AUTH_ACL, $a_key, $u_access, $custom_mod) : FALSE;
						$auth_user[$a_key . '_type'] = $user->lang['AUTH_USERS_GRANTED_ACCESS'];
						break;
					case AUTH_MOD:
						//$auth_user[$a_key] = ($user->data['is_member']) ? $this->check_user(AUTH_MOD, 'auth_mod', $u_access, $custom_mod) : FALSE;
						$auth_user[$a_key] = ($user->data['is_member']) ? $user->_team_auth($custom_mod) : FALSE;
						$auth_user[$a_key . '_type'] = $user->lang['AUTH_MODERATORS'];
						break;
					case AUTH_ADMIN:
						$auth_user[$a_key] = $this->founder;
						$auth_user[$a_key . '_type'] = $user->lang['AUTH_ADMINISTRATORS'];
						break;
					default:
						$auth_user[$a_key] = FALSE;
						break;
				}
			}
			else
			{
				for ($k = 0, $end = sizeof($f_access); $k < $end; $k++)
				{
					$value = $f_access[$k][$a_key];
					$f_forum_id = $f_access[$k]['forum_id'];
					
					$custom_mod = forum_for_team($forum_id);
	
					switch ($value)
					{
						case AUTH_ALL:
							$auth_user[$f_forum_id][$a_key] = TRUE;
							$auth_user[$f_forum_id][$a_key . '_type'] = $user->lang['AUTH_ANONYMOUS_USERS'];
							break;
						case AUTH_REG:
							$auth_user[$f_forum_id][$a_key] = ($user->data['is_member']) ? TRUE : FALSE;
							$auth_user[$f_forum_id][$a_key . '_type'] = $user->lang['AUTH_REGISTERED_USERS'];
							break;
						case AUTH_ACL:
							$auth_user[$f_forum_id][$a_key] = ($user->data['is_member']) ? $this->check_user(AUTH_ACL, $a_key, $u_access[$f_forum_id], $custom_mod) : FALSE;
							$auth_user[$f_forum_id][$a_key . '_type'] = $user->lang['AUTH_USERS_GRANTED_ACCESS'];
							break;
						case AUTH_MOD:
							//$auth_user[$f_forum_id][$a_key] = ($user->data['is_member']) ? $this->check_user(AUTH_MOD, 'auth_mod', $u_access[$f_forum_id]) : FALSE;
							$auth_user[$f_forum_id][$a_key] = ($user->data['is_member']) ? $user->_team_auth($custom_mod) : FALSE;
							$auth_user[$f_forum_id][$a_key . '_type'] = $user->lang['AUTH_MODERATORS'];
							break;
						case AUTH_ADMIN:
							$auth_user[$f_forum_id][$a_key] = $this->founder;
							$auth_user[$f_forum_id][$a_key . '_type'] = $user->lang['AUTH_ADMINISTRATORS'];
							break;
						default:
							$auth_user[$f_forum_id][$a_key] = FALSE;
							break;
					}
				}
			}
		}
	
		//
		// Is user a moderator?
		//
		if ($forum_id != AUTH_LIST_ALL)
		{
			$custom_mod = forum_for_team($forum_id);
			
			//$auth_user['auth_mod'] = ($user->data['is_member']) ? $this->check_user(AUTH_MOD, 'auth_mod', $u_access) : FALSE;
			$auth_user['auth_mod'] = ($user->data['is_member']) ? $user->_team_auth($custom_mod) : FALSE;
		}
		else
		{
			for ($k = 0, $end = sizeof($f_access); $k < $end; $k++)
			{
				$f_forum_id = $f_access[$k]['forum_id'];
				$custom_mod = forum_for_team($forum_id);
	
				$auth_user[$f_forum_id]['auth_mod'] = ($user->data['is_member']) ? (isset($u_access[$f_forum_id]) ? $this->check_user(AUTH_MOD, 'auth_mod', $u_access[$f_forum_id], $custom_mod) : FALSE) : FALSE;
			}
		}
	
		return $auth_user;
	}
	
	function check_user($type, $key, $u_access, $custom_mod)
	{
		global $user;
		
		$auth_user = 0;
	
		if (sizeof($u_access))
		{
			for ($j = 0, $end = sizeof($u_access); $j < $end; $j++)
			{
				$result = 0;
				switch($type)
				{
					case AUTH_ACL:
						$result = $u_access[$j][$key];
	
					case AUTH_MOD:
						//$result = $result || $u_access[$j]['auth_mod'];
						$result = $result || $user->_team_auth($custom_mod);
	
					case AUTH_ADMIN:
						$result = $result || $this->founder;
						break;
				}
	
				$auth_user = $auth_user || $result;
			}
		}
		else
		{
			$auth_user = $this->founder;
		}
	
		return $auth_user;
	}
}

?>
