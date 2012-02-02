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
if (!defined('IN_NUCLEO')) exit;

function a_thumbnails($selected_artists, $random_images, $lang_key, $block, $item_per_col = 2) {
	global $config, $user;
	
	_style('main.' . $block, array(
		'L_TITLE' => $user->lang[$lang_key])
	);
	
	$col = 0;
	foreach ($selected_artists as $ub => $data) {
		if (!$col) {
			_style('main.' . $block . '.row');
		}
		
		$image = ($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif';
		
		_style('main.' . $block . '.row.col', array(
			'NAME' => $data['name'],
			'IMAGE' => $config['artists_url'] . $image,
			'URL' => s_link('a', $data['subdomain']),
			'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
			'GENRE' => $data['genre'])
		);
		
		$col = ($col == ($item_per_col - 1)) ? 0 : $col + 1;
	}
	
	return true;
}

class userpage {
	private $_title;
	private $_template;
	private $data;
	private $comments;
	
	public function __construct() {
		$this->comments = new _comments();
		
		return;
	}
	
	public function get_title($default = '') {
		return (!empty($this->_title)) ? $this->_title : $default;
	}
	
	public function get_template($default = '') {
		return (!empty($this->_template)) ? $this->_template : $default;
	}
	
	public function run() {
		global $user;
		
		if (!$user->is('member')) {
			do_login();
		}
		
		$userpage = request_var('member', '');
		
		if (empty($userpage)) {
			return $this->profile();
		}
		
		$sql = 'SELECT *
			FROM _members
			WHERE username_base = ? 
				AND user_type NOT IN (??, ??)
				AND user_id NOT IN (
					SELECT user_id
					FROM _members_ban
					WHERE banned_user = ?
				)
				AND user_id NOT IN (
					SELECT ban_userid
					FROM _banlist
				)';
		if (!$this->data = sql_fieldrow(sql_filter($sql, phpbb_clean_username($userpage), USER_INACTIVE, USER_IGNORE, $user->d('user_id')))) {
			fatal_error();
		}
		
		return $this->userpage();
	}
	
	private function profile() {
		global $user;
		
		require_once(ROOT . 'interfase/functions_avatar.php');
		
		$fields = 'public_email timezone dateformat location sig msnm yim aim icq lastfm website occ interests os fav_genres fav_artists rank color';
		
		$user_fields = w();
		foreach (w($fields) as $row) {
			$user_fields[$row] = $user->d('user_' . $row);
		}
		
		$user_fields['avatar'] = '';
		$user_fields['gender'] = $user->data['user_gender'];
		$user_fields['birthday_day'] = (int) substr($user->data['user_birthday'], 6, 2);
		$user_fields['birthday_month'] = (int) substr($user->data['user_birthday'], 4, 2);
		$user_fields['birthday_year'] = (int) substr($user->data['user_birthday'], 0, 4);
		
		extract($user_fields);
		
		$hideuser = $user->d('user_hideuser');
		$email_dc = $user->d('user_email_dc');
		
		$error = array();
		$dateset = array('d M Y H:i', 'M d, Y H:i');
		
		if (_button()) {
			
			foreach ($user_fields as $name => $value) {
				$$name = request_var($name, $value);
			}
			
			$password1 = request_var('password1', '');
			$password2 = request_var('password2', '');
			$hideuser = (isset($_POST['hideuser'])) ? true : false;
			$email_dc = (isset($_POST['email_dc'])) ? true : false;
			
			if (!empty($password1)) {
				if (empty($password2)) {
					$error[] = 'EMPTY_PASSWORD2';
				}
				
				if (!sizeof($error)) {
					if ($password1 != $password2) {
						$error[] = 'PASSWORD_MISMATCH';
					} else if (strlen($password1) > 30) {
						$error[] = 'PASSWORD_LONG';
					}
				}
			}
			
			$check_length_ary = w('location sig msnm yim aim icq website occ interests os fav_genres fav_artists');
			foreach ($check_length_ary as $name) {
				if (strlen($$name) < 3) {
					$$name = '';
				}
			}
			
			if (!empty($website)) {
				if (!preg_match('#^http[s]?:\/\/#i', $website)) {
					$website = 'http://' . $website;
				}
				
				if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $website)) {
					$website = '';
				}
			}
			
			if (!empty($rank)) {
				$rank_word = explode(' ', $rank);
				if (sizeof($rank_word) > 3) {
					$error[] = 'RANK_TOO_LONG';
				}
				
				if (!sizeof($error)) {
					$rank_limit = 15;
					foreach ($rank_word as $each) {
						if (preg_match_all('#\&.*?\;#is', $each, $each_preg)) {
							foreach ($each_preg[0] as $each_preg_each) {
								$rank_limit += (strlen($each_preg_each) - 1);
							}
						}
						
						if (strlen($each) > $rank_limit) {
							$error[] = 'RANK_TOO_LONG';
							break;
						}
					}
				}
			}
			
			// Rank
			if (!empty($rank) && !sizeof($error)) {
				$sql = 'SELECT rank_id
					FROM _ranks
					WHERE rank_title = ?';
				if (!$rank_id = sql_field(sql_filter($sql, $rank), 'rank_id', 0)) {
					$insert = array(
						'rank_title' => $rank,
						'rank_min' => -1,
						'rank_max' => -1,
						'rank_special' => 1
					);
					$sql = 'INSERT INTO _ranks' . sql_build('INSERT', $insert);
					$rank_id = sql_query_nextid($sql);
				}
				
				$old_rank = $userdata['user_rank'];
				if ($old_rank) {
					$sql = 'SELECT user_id
						FROM _members
						WHERE user_rank = ?';
					$by = sql_rowset(sql_filter($sql, $old_rank), false, 'user_id');
					
					if (sizeof($by) == 1) {
						$sql = 'DELETE FROM _ranks
							WHERE rank_id = ?';
						sql_query(sql_filter($sql, $old_rank));
					}
				}
				
				$rank = $rank_id;
				$cache->delete('ranks');
			}
			
			if (!$birthday_month || !$birthday_day || !$birthday_year) {
				$error[] = 'EMPTY_BIRTH_MONTH';
			}
			
			if (!sizeof($error)) {
				if ($xavatar->process()) {
					$avatar = $xavatar->file();
				}
			}
			
			if (!sizeof($error)) {
				if (!empty($sig)) {
					$sig = $this->comments->prepare($sig);
				}
				
				unset($user_fields['birthday_day'], $user_fields['birthday_month'], $user_fields['birthday_year']);
				
				$dateformat = $dateset[$dateformat];
				$user_fields['hideuser'] = $user->d('user_hideuser');
				$user_fields['email_dc'] = $user->d('user_email_dc');
				
				$member_data = w();
				foreach ($user_fields as $name => $value) {
					if ($value != $$name) {
						$member_data['user_' . $name] = $$name;
					}
				}
				
				$member_data['user_gender'] = $gender;
				$member_data['user_birthday'] = (string) (leading_zero($birthday_year) . leading_zero($birthday_month) . leading_zero($birthday_day));
				
				if (sizeof($member_data)) {
					$sql = 'UPDATE _members SET ' . sql_build('UPDATE', $member_data) . sql_filter(' 
						WHERE user_id = ?', $user->data['user_id']);
					sql_query($sql);
				}
				
				redirect(s_link('m', $user->data['username_base']));
			}
		}
		
		if (sizeof($error)) {
			$error = preg_replace('#^([0-9A-Z_]+)$#e', "(isset(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);
			
			_style('error', array(
				'MESSAGE' => (sizeof($error)) ? implode('<br />', $error) : '')
			);
		}
		
		if ($user->d('user_avatar')) {
			_style('current_avatar', array(
				'IMAGE' => $config['assets_url'] . 'avatars/' . $user->d('user_avatar'))
			);
		}
		
		$s_genders_select = '';
		foreach (array(1 => 'MALE', 2 => 'FEMALE') as $id => $value) {
			$s_genders_select .= '<option value="' . $id . '"' . (($gender == $id) ? ' selected="true"' : '') . '>' . $user->lang[$value] . '</option>';
		}
		
		_style('gender', array(
			'GENDER_SELECT' => $s_genders_select)
		);
		
		$s_day_select = '<option value="">&nbsp;</option>';
		for ($i = 1; $i < 32; $i++) {
			$s_day_select .= '<option value="' . $i . '"' . (($birthday_day == $i) ? ' selected="true"' : '') . '>' . $i . '</option>';
		}
		
		$s_month_select = '<option value="">&nbsp;</option>';
		$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		foreach ($months as $id => $value) {
			$s_month_select .= '<option value="' . ($id + 1) . '"' . (($birthday_month == ($id + 1)) ? ' selected="true"' : '') . '>' . $user->lang['datetime'][$value] . '</option>';
		}
		
		$s_year_select = '<option value="">&nbsp;</option>';
		for ($i = 2005; $i > 1899; $i--) {
			$s_year_select .= '<option value="' . $i . '"' . (($birthday_year == $i) ? ' selected="true"' : '') . '>' . $i . '</option>';
		}
		
		_style('birthday', array(
			'DAY' => $s_day_select,
			'MONTH' => $s_month_select,
			'YEAR' => $s_year_select)
		);
		
		$dateformat_select = '';
		foreach ($dateset as $id => $value) {
			$dateformat_select .= '<option value="' . $id . '"' . (($value == $dateformat) ? ' selected="selected"' : '') . '>' . $user->format_date(time(), $value) . '</option>';
		}
		
		$timezone_select = '';
		foreach ($user->lang['zones'] as $id => $value) {
			$timezone_select .= '<option value="' . $id . '"' . (($id == $timezone) ? ' selected="selected"' : '') . '>' . $value . '</option>';
		}
		
		unset($user_fields['timezone'], $user_fields['dateformat']);
		
		$output_vars = array(
			'AVATAR_MAXSIZE' => $config['avatar_filesize'],
			'DATEFORMAT' => $dateformat_select,
			'TIMEZONE' => $timezone_select,
			'HIDEUSER_SELECTED' => ($hideuser) ? ' checked="checked"' : '',
			'EMAIL_DC_SELECTED' => ($email_dc) ? ' checked="checked"' : ''
		);
		
		foreach ($user_fields as $name => $value) {
			$output_vars[strtoupper($name)] = $$name;
		}
		v_style($output_vars);
		
		$this->_title = 'MEMBER_OPTIONS';
		$this->_template = 'profile';
		
		return;
	}
	
	private function userpage() {
		global $user;
		
		$mode = request_var('mode', 'main');
		
		if ($user->data['user_id'] != $this->data['user_id'] && !in_array($mode, w('friend ban'))) {
			$is_blocked = false;
			
			if (!$user->is('all', $this->data['user_id'])) {
				$sql = 'SELECT ban_id
					FROM _members_ban
					WHERE user_id = ?
						AND banned_user = ?';
				if ($banned_row = sql_fieldrow(sql_filter($sql, $user->d('user_id'), $this->data['user_id']))) {
					$is_blocked = true;
				}
				
				$banned_lang = ($is_blocked) ? 'REMOVE' : 'ADD';
				
				_style('block_member', array(
					'URL' => s_link('m', array($this->data['username_base'], 'ban')),
					'LANG' => $user->lang['BLOCKED_MEMBER_' . $banned_lang])
				);
			}
		}
		
		$profile_fields = $this->comments->user_profile($this->data);
		
		switch ($mode) {
			case 'friend':
				$this->friend_add();
			break;
			case 'ban':
				$this->user_ban();
			break;
			case 'favs':
			break;
			case 'friends':
				$this->friend_list();
			break;
			case 'stats':
				$this->user_stats();
			break;
			case 'main':
			default:
				$this->user_main();
			break;
		}
		
		$panel_selection = array(
			'main' => array('L' => 'MAIN', 'U' => false)
		);
		
		if ($user->d('user_id') != $this->data['user_id']) {
			$panel_selection['start'] = array('L' => 'DCONV_START', 'U' => s_link('my', array('dc', 'start', $this->data['username_base'])));
		} else {
			$panel_selection['dc'] = array('L' => 'DC', 'U' => s_link('my', 'dc'));
		}
		
		$panel_selection += array(
			'friends' => array('L' => 'FRIENDS', 'U' => false)
		);
		
		foreach ($panel_selection as $link => $data) {
			_style('selected_panel', array(
				'LANG' => $user->lang['USERPAGE_' . $data['L']])
			);
			
			if ($mode == $link) {
				_style('selected_panel.strong');
				continue;
			}
			
			_style('selected_panel.a', array(
				'URL' => ($data['U'] !== false) ? $data['U'] : s_link('m', array($this->data['username_base'], (($link != 'main') ? $link : ''))))
			);
		}
		
		//
		// Check if friends
		//
		if ($user->d('user_id') != $this->data['user_id']) {
			$friend_add_lang = true;
			
			if ($user->is('member')) {
				$friend_add_lang = $this->is_friend($user->data['user_id'], $this->data['user_id']);
			}
			
			$friend_add_lang = ($friend_add_lang) ? 'FRIENDS_ADD' : 'FRIENDS_DEL';
			
			_style('friend', array(
				'U_FRIEND' => s_link('m', array($this->data['username_base'], 'friend')),
				'L_FRIENDS_ADD' => $user->lang[$friend_add_lang])
			);
		}
		
		//
		// Generate page
		//
		v_style(array(
			'USERNAME' => $this->data['username'],
			'USERNAME_COLOR' => $this->data['user_color'],
			'POSTER_RANK' => $profile_fields['user_rank'],
			'AVATAR_IMG' => $profile_fields['user_avatar'],
			'USER_ONLINE' => $online,
			
			'PM' => s_link('my', array('dc', 'start', $this->data['username_base'])),
			'WEBSITE' => $this->data['user_website'],
			'MSN' => $this->data['user_msnm']
		));
		
		$layout_file = 'userpage';
		
		$use_m_template = 'custom/profile_' . $this->data['username_base'];
		if (@file_exists(ROOT . 'template/' . $use_m_template . '.htm')) {
			$layout_file = $use_m_template;
		}
		
		$this->_title = $this->data['username'];
		$this->_template = $layout_file;
		
		return;
	}
	
	public function friend_add() {
		global $user;
		
		if (!$user->is('member')) {
			do_login();
		}
		
		if ($user->data['user_id'] == $this->data['user_id']) {
			redirect(s_link('m', $this->data['username_base']));
		}
		
		$sql = 'SELECT *
			FROM _members_friends
			WHERE user_id = ?
				AND buddy_id = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $user->data['user_id'], $this->data['user_id']))) {
			$sql = 'DELETE FROM _members_friends
				WHERE user_id = ?
					AND buddy_id = ?';
			sql_query(sql_filter($sql, $user->data['user_id'], $this->data['user_id']));
			
			if ($row['friend_time']) {
				//$user->points_remove(1);
			}
			
			$user->delete_unread($this->data['user_id'], $user->data['user_id']);
			
			redirect(s_link('m', $this->data['username_base']));
		}
		
		$sql_insert = array(
			'user_id' => $user->data['user_id'],
			'buddy_id' => $this->data['user_id'],
			'friend_time' => time()
		);
		$sql = 'INSERT INTO _members_friends' . sql_build('INSERT', $sql_insert);
		sql_query($sql);
		
		$user->save_unread(UH_FRIEND, $user->data['user_id'], 0, $this->data['user_id']);
		
		redirect(s_link('m', array($user->data['username_base'], 'friends')));
	}
	
	public function friend_list() {
		global $user;
		
		$sql = 'SELECT DISTINCT u.user_id AS user_id, u.username, u.username_base, u.user_color, u.user_avatar, u.user_rank, u.user_gender, u.user_posts
			FROM _members_friends b, _members u
			WHERE (b.user_id = ?
				AND b.buddy_id = u.user_id) OR
				(b.buddy_id = ?
					AND b.user_id = u.user_id)
			ORDER BY u.username';
		if ($result = sql_rowset(sql_filter($sql, $this->data['user_id'], $this->data['user_id']))) {
			_style('friends');
			
			$tcol = 0;
			foreach ($result as $row) {
				$friend_profile = $this->comments->user_profile($row);
				
				if (!$tcol) _style('friends.row');
				
				_style('friends.row.col', array(
					'PROFILE' => $friend_profile['profile'],
					'USERNAME' => $friend_profile['username'],
					'COLOR' => $friend_profile['user_color'],
					'AVATAR' => $friend_profile['user_avatar'],
					'RANK' => $friend_profile['user_rank'])
				);
				
				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
		}
		
		return true;
	}
	
	public function is_friend($user_one, $user_two) {
		$sql = 'SELECT *
			FROM _members_friends
			WHERE (user_id = ?
				AND buddy_id = ?)
				OR (buddy_id = ?
				AND user_id = ?)';
		if (sql_fieldrow(sql_filter($sql, $user_one, $user_two, $user_two, $user_one))) {
			return true;
		}
		
		return false;
	}
	
	public function user_ban() {
		global $user;
		
		if (!$user->is('member')) {
			do_login();
		}
		
		if ($user->data['user_id'] == $this->data['user_id']) {
			redirect(s_link('m', $this->data['username_base']));
		}
		
		if ($epbi) {
			fatal_error();
		}
		
		$sql = 'SELECT ban_id
			FROM _members_ban
			WHERE user_id = ?
				AND banned_user = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $user->data['user_id'], $this->data['user_id']))) {
			$sql = 'DELETE FROM _members_ban
				WHERE ban_id = ?';
			sql_query(sql_filter($sql, $row['ban_id']));
			
			redirect(s_link('m', $this->data['username_base']));
		}
		
		$sql_insert = array(
			'user_id' => $user->data['user_id'],
			'banned_user' => $this->data['user_id'],
			'ban_time' => $user->time
		);
		$sql = 'INSERT INTO _members_ban' . sql_build('INSERT', $sql_insert);
		sql_query($sql);
		
		$sql = 'DELETE FROM _members_friends
			WHERE user_id = ?
				AND buddy_id = ?';
		sql_query(sql_filter($sql, $user->data['user_id'], $this->data['user_id']));
		
		$sql = 'DELETE FROM _members_friends
			WHERE user_id = ?
				AND buddy_id = ?';
		sql_query(sql_filter($sql, $this->data['user_id'], $user->data['user_id']));
		
		$sql = 'DELETE FROM _members_viewers
			WHERE user_id = ?
				AND viewer_id = ?';
		sql_query(sql_filter($sql, $this->data['user_id'], $user->data['user_id']));
		
		redirect(s_link('m', $this->data['username_base']));
	}
	
	public function user_stats() {
		$user_stats = array(
			'VISITS_COUNT' => $this->data['user_totallogon'],
			'PAGEVIEWS_COUNT' => $this->data['user_totalpages'],
			'FORUM_POSTS' => $this->data['user_posts']
		);
		
		$m = false;
		foreach ($user_stats as $key => $value) {
			if ($value == '') {
				continue;
			}
			
			if (!$m) {
				_style('main.stats');
				$m = true;
			}
			
			_style('main.stats.item', array(
				'KEY' => $user->lang[$key],
				'VALUE' => $value)
			);
		}
		
		return true;
	}
	
	public function user_main() {
		global $user;
		
		_style('main');
		
		//
		// Get artists where this member is an authorized member
		//
		$sql = 'SELECT au.user_id, a.ub, a.name, a.subdomain, a.images, a.local, a.location, a.genre
			FROM _artists_auth au, _artists a
			WHERE au.user_id = ?
				AND au.ub = a.ub
			ORDER BY a.name';
		if ($selected_artists = sql_rowset(sql_filter($sql, $this->data['user_id']), 'ub')) {
			$sql = 'SELECT ub, image
				FROM _artists_images
				WHERE ub IN (??)
				ORDER BY RAND()';
			$result = sql_rowset(sql_filter($sql, implode(',', array_keys($selected_artists))));
			
			$random_images = array();
			foreach ($result as $row) {
				if (!isset($random_images[$row['ub']])) {
					$random_images[$row['ub']] = $row['image'];
				}
			}
			
			a_thumbnails($selected_artists, $random_images, 'USERPAGE_MOD', 'thumbnails');
		}
		
		//
		// GET MEMBER FAV ARTISTS
		//
		$sql = 'SELECT f.user_id, a.ub, a.name, a.subdomain, a.images, a.local, a.location, a.genre
			FROM _artists_fav f, _artists a
			WHERE f.user_id = ?
				AND f.ub = a.ub
			ORDER BY RAND()';
		if ($result2 = sql_rowset(sql_filter($sql, $this->data['user_id']), 'ub')) {
			
			$sql = 'SELECT ub, image
				FROM _artists_images
				WHERE ub IN (??)
				ORDER BY RAND()';
			$result_images = sql_rowset(sql_filter($sql, implode(',', array_keys($result2))));
			
			$random_images2 = array();
			foreach ($result_images as $row) {
				if (!isset($random_images2[$row['ub']])) {
					$random_images2[$row['ub']] = $row['image'];
				}
			}
			
			$total_a = 0;
			$selected_artists2 = array();
			
			foreach ($result2 as $row) {
				if ($total_a < 6) {
					$selected_artists2[$row['ub']] = $row;
				}
				$total_a++;
			}
			
			a_thumbnails($result2, $random_images2, 'USERPAGE_AFAVS', 'thumbnails');
			
			if ($total_a > 6) {
				_style('main.thumbnails.all');
			}
		}
		
		// Latest board posts
		$sql = "SELECT DISTINCT(t.topic_title), p.post_id, p.post_time, t.topic_color
			FROM _forum_topics t, _forum_posts p
			WHERE p.poster_id = ?
				AND p.forum_id NOT IN (14,15,16,17,20,22,38)
				AND t.topic_id = p.topic_id
			GROUP BY p.topic_id
			ORDER BY p.post_time DESC
			LIMIT 10";
		$result = sql_rowset(sql_filter($sql, $this->data['user_id']));
		
		foreach ($result as $i => $row) {
			if (!$i) _style('main.lastboard');
			
			_style('main.lastboard.row', array(
				'URL' => s_link('post', $row['post_id']) . '#' . $row['post_id'],
				'TITLE' => $row['topic_title'],
				'TOPIC_COLOR' => $row['topic_color'],
				'TIME' => $user->format_date($row['post_time'], 'H:i'),
				'DATE' => $user->format_date($row['post_time'], $user->lang['DATE_FORMAT']))
			);
		}
		
		//
		// GET USERPAGE MESSAGES
		//
		$comments_ref = s_link('m', array($this->data['username_base']));
		
		if ($user->is('member')) {
			_style('main.post_comment_box', array(
				'REF' => $comments_ref)
			);
		}
		
		//
		// User age & birthday
		//
		$birthday = '';
		$age = 0;
		if ($this->data['user_birthday']) {
			$bd_month = gmmktime(0, 0, 0, substr($this->data['user_birthday'], 4, 2) + 1, 0, 0);
			$birthday = (int) substr($this->data['user_birthday'], 6, 2) . ' ' . $user->format_date($bd_month, 'F') . ' ' . substr($this->data['user_birthday'], 0, 4);
			
			$age = date('Y', time()) - intval(substr($this->data['user_birthday'], 0, 4));
			if (intval(substr($this->data['user_birthday'], 4, 4)) > date('md', time())) {
				$age--;
			}
			$age .= ' ' . $user->lang['YEARS'];
		}
		
		switch ($this->data['user_gender']) {
			case 0:
				$gender = 'NO_GENDER';
				break;
			case 1:
				$gender = 'MALE';
				break;
			case 2:
				$gender = 'FEMALE';
				break;
		}
		
		$gender = $user->lang[$gender];
		
		$user_fields = array(
			//'JOINED' => ($this->data['user_regdate'] && (!$this->data['user_hideuser'] || $epbi2)) ? $user->format_date($this->data['user_regdate']) . sprintf($user->lang['JOINED_SINCE'], $memberdays) : '',
			'LAST_LOGON' => ($this->data['user_lastvisit'] && (!$this->data['user_hideuser'] || $epbi2)) ? $user->format_date($this->data['user_lastvisit']) : '',
			'GENDER' => $gender,
			'AGE' => $age,
			'BIRTHDAY' => $birthday,
			'FAV_GENRES' => $this->data['user_fav_genres'],
			'FAV_BANDS' => $this->data['user_fav_artists'],
			'LOCATION' => $this->data['user_location'],
			'OCCUPATION' => $this->data['user_occ'],
			'INTERESTS' => $this->data['user_interests'],
			'MEMBER_OS' => $this->data['user_os']
		);
		
		$m = 0;
		foreach ($user_fields as $key => $value) {
			if ($value == '') {
				continue;
			}
			
			if (!$m) {
				_style('main.general');
				$m = 1;
			}
			
			_style('main.general.item', array(
				'KEY' => $user->lang[$key],
				'VALUE' => $value)
			);
		}
		
		//
		// GET LAST.FM FEED
		//
		// http://ws.audioscrobbler.com/1.0/user//recenttracks.xml
		if (!empty($this->data['user_lastfm'])) {
			include_once('./interfase/scrobbler.php');
			
			$scrobbler = new EasyScrobbler($this->data['user_lastfm']);
			$list = $scrobbler->getRecentTracs();
			
			if (sizeof($list))
			{
				_style('main.lastfm', array(
					'NAME' => $this->data['user_lastfm'],
					'URL' => 'http://www.last.fm/user/' . $this->data['user_lastfm'] . '/')
				);
				
				foreach ($list as $row)
				{
					_style('main.lastfm.row', array(
						'ARTIST' => $row['ARTIST'],
						'NAME' => $row['NAME'],
						'ALBUM' => $row['ALBUM'],
						'URL' => $row['URL'],
						'TIME' => $user->format_date($row['DATE_UTS'], 'H:i'))
					);
				}
			}
		}
		
		//
		// GET LAST USERPAGE VIEWERS
		//
		/*
		$sql = 'SELECT v.datetime, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar
			FROM _members_viewers v, _members u
			WHERE v.user_id = ?
				AND v.viewer_id = u.user_id
			ORDER BY datetime DESC';
		if ($result = sql_rowset(sql_filter($sql, $this->data['user_id']))) {
			_style('main.viewers');
			
			$col = 0;
			foreach ($result as $row) {
				$profile = $this->comments->user_profile($row);
				
				if (!$col) _style('main.viewers.row');
				
				_style('main.viewers.row.col', array(
					'PROFILE' => $profile['profile'],
					'USERNAME' => $profile['username'],
					'COLOR' => $profile['user_color'],
					'AVATAR' => $profile['user_avatar'],
					'DATETIME' => $user->format_date($row['datetime']))
				);
				
				$col = ($col == 2) ? 0 : $col + 1;
			}
		}
		*/
		
		//
		// GET USERPAGE MESSAGES
		//
		$comments_ref = s_link('m', $this->data['username_base']);
		if ($this->data['userpage_posts'])
		{
			$this->comments->reset();
			$this->comments->ref = $comments_ref;
			
			$sql = 'SELECT p.*, u2.user_id, u2.username, u2.username_base, u2.user_color, u2.user_avatar
				FROM _members_posts p, _members u, _members u2
				WHERE p.userpage_id = ?
					AND p.userpage_id = u.user_id 
					AND p.post_active = 1 
					AND p.poster_id = u2.user_id 
				ORDER BY p.post_time DESC 
				LIMIT 50';
			
			$this->comments->data = array(
				'A_LINKS_CLASS' => 'bold red',
				'USER_ID_FIELD' => 'userpage_id',
				'S_DELETE_URL' => s_link('mcp', array('ucm', '%d')),
				'SQL' => sql_filter($sql, $this->data['user_id'])
			);
			
			$this->comments->view(0, '', $this->data['userpage_posts'], $this->data['userpage_posts'], 'main.posts');
		}
		
		if ($user->is('member')) {
			_style('main.box', array(
				'REF' => $comments_ref)
			);
		}
		
		return true;
	}
}

?>