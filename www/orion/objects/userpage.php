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
if (!defined('IN_APP')) exit;

function a_thumbnails($selected_artists, $random_images, $lang_key, $block, $item_per_col = 2) {
	global $config, $user;

	_style('main.' . $block, array(
		'L_TITLE' => lang($lang_key))
	);

	foreach ($selected_artists as $ub => $data) {
		$image = $ub . '/thumbnails/' . $random_images[$ub] . '.jpg';

		_style('main.' . $block . '.row', array(
			'NAME' => $data['name'],
			'IMAGE' => $config['artists_url'] . $image,
			'URL' => s_link('a', $data['subdomain']),
			'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
			'GENRE' => $data['genre'])
		);
	}

	return true;
}

class userpage {
	private $_title;
	private $_template;
	private $data;

	public function __construct() {
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
		$page = request_var('page', '');

		switch ($page) {
			case 'dc':
				return $this->conversations();
		}

		if (empty($userpage)) {
			return $this->profile();
		}

		$sql = 'SELECT *
			FROM _members
			WHERE username_base = ?
				AND user_type NOT IN (??)
				AND user_id NOT IN (
					SELECT user_id
					FROM _members_ban
					WHERE banned_user = ?
				)
				AND user_id NOT IN (
					SELECT ban_userid
					FROM _banlist
				)';
		if (!$this->data = sql_fieldrow(sql_filter($sql, get_username_base($userpage), USER_INACTIVE, $user->d('user_id')))) {
			fatal_error();
		}

		return $this->userpage();
	}

	private function conversations() {
		if (_button('cancel')) {
			redirect(s_link('my dc'));
		}

		global $config, $user, $cache, $comments;

		// TODO: New conversation system
		// /my/dc/(page)/(selected)/(username)/

		$this->conversations_delete();

		$submit = _button('post');
		$msg_id = request_var('p', 0);
		$mode = request_var('mode', '');
		$error = w();

		if ($submit || $mode == 'start' || $mode == 'reply') {
			$member = '';
			$dc_subject = '';
			$dc_message = '';

			if ($submit) {
				if ($mode == 'reply') {
					$parent_id = request_var('parent', 0);

					$sql = 'SELECT *
						FROM _dc
						WHERE msg_id = ?
							AND (privmsgs_to_userid = ? OR privmsgs_from_userid = ?)';
					if (!$to_userdata = sql_fieldrow(sql_filter($sql, $parent_id, $user->d('user_id'), $user->d('user_id')))) {
						fatal_error();
					}

					$privmsgs_to_userid = ($user->d('user_id') == $to_userdata['privmsgs_to_userid']) ? 'privmsgs_from_userid' : 'privmsgs_to_userid';
					$to_userdata['user_id'] = $to_userdata[$privmsgs_to_userid];
				} else {
					$member = request_var('member', '');
					if (!empty($member)) {
						$member = get_username_base($member, true);

						if ($member !== false) {
							$sql = 'SELECT user_id, username, username_base, user_email
								FROM _members
								WHERE username_base = ?
									AND user_type <> ?';
							if (!$to_userdata = sql_fieldrow(sql_filter($sql, $member, USER_INACTIVE))) {
								$error[] = 'NO_SUCH_USER';
							}

							if (!sizeof($error) && $to_userdata['user_id'] == $user->d('user_id')) {
								$error[] = 'NO_AUTO_DC';
							}
						} else {
							$error[] = 'NO_SUCH_USER';
							$member = '';
						}
					} else {
						$error[] = 'EMPTY_USER';
					}
				}

				if (isset($to_userdata) && isset($to_userdata['user_id'])) {
					// Check blocked member
					$sql = 'SELECT ban_id
						FROM _members_ban
						WHERE user_id = ?
							AND banned_user = ?';
					if ($ban_profile = sql_fieldrow(sql_filter($sql, $to_userdata['user_id'], $user->d('user_id')))) {
						$error[] = 'BLOCKED_MEMBER';
					}
				}

				$dc_message = request_var('message', '');
				if (empty($dc_message)) {
					$error[] = 'EMPTY_MESSAGE';
				}

				if (!sizeof($error)) {
					$dc_id = $comments->store_dc($mode, $to_userdata, $user->d(), $dc_subject, $dc_message, true, true);

					redirect(s_link('my dc read', $dc_id) . '#' . $dc_id);
				}
			}
		}

		//
		// Start error handling
		//
		if (sizeof($error)) {
			_style('error', array(
				'MESSAGE' => parse_error($error))
			);

			if ($mode == 'reply') {
				$mode = 'read';
			}
		}

		$s_hidden_fields = w();

		switch ($mode) {
			case 'start':
				//
				// Start new conversation
				//
				if (!$submit) {
					$member = request_var('member', '');
					if ($member != '') {
						$member = get_username_base($member);

						$sql = 'SELECT user_id, username, username_base
							FROM _members
							WHERE username_base = ?
								AND user_type <> ?';
						$row = sql_fieldrow(sql_filter($sql, $member, USER_INACTIVE));
					}
				}

				_style('dc_start', array(
					'MEMBER' => $member,
					'SUBJECT' => $dc_subject,
					'MESSAGE' => $dc_message)
				);

				$s_hidden_fields = array('mode' => 'start');
				break;
			case 'read':
				//
				// Show selected conversation
				//
				if (!$msg_id) {
					fatal_error();
				}

				$sql = 'SELECT *
					FROM _dc
					WHERE msg_id = ?
						AND (privmsgs_to_userid = ? OR privmsgs_from_userid = ?)
						AND msg_deleted <> ?';
				if (!$msg_data = sql_fieldrow(sql_filter($sql, $msg_id, $user->d('user_id'), $user->d('user_id'), $user->d('user_id')))) {
					fatal_error();
				}

				//
				// Get all messages for this conversation
				//
				$sql = 'SELECT c.*, m.user_id, m.username, m.username_base, m.user_avatar, m.user_sig, m.user_rank, m.user_gender, m.user_posts
					FROM _dc c, _members m
					WHERE c.parent_id = ?
						AND c.privmsgs_from_userid = m.user_id
					ORDER BY c.privmsgs_date';
				if (!$result = sql_rowset(sql_filter($sql, $msg_data['parent_id']))) {
					fatal_error();
				}

				$with_user = $msg_data['privmsgs_to_userid'];
				if ($with_user == $user->d('user_id')) {
					$with_user = $msg_data['privmsgs_from_userid'];
				}

				$sql = 'SELECT username
					FROM _members
					WHERE user_id = ?';
				$with_username = sql_field(sql_filter($sql, $with_user), 'username', '');

				_style('conv', array(
					'URL' => s_link('my dc'),
					'SUBJECT' => $with_username,
					'CAN_REPLY' => $result[0]['msg_can_reply'],)
				);

				foreach ($result as $row) {
					$user_profile = $comments->user_profile($row);

					_style('conv.row', array(
						'USERNAME' => $user_profile['username'],
						'AVATAR' => $user_profile['user_avatar'],
						'SIGNATURE' => ($row['user_sig'] != '') ? $comments->parse_message($row['user_sig']) : '',
						'PROFILE' => $user_profile['profile'],
						'MESSAGE' => $comments->parse_message($row['privmsgs_text']),
						'POST_ID' => $row['msg_id'],
						'POST_DATE' => $user->format_date($row['privmsgs_date']))
					);
				}

				$s_hidden_fields = array('mark[]' => $msg_data['parent_id'], 'p' => $msg_id, 'parent' => $msg_data['parent_id'], 'mode' => 'reply');
				break;
			default:
				//
				// Get all conversations for this member
				//
				$offset = request_var('offset', 0);

				$sql = 'SELECT COUNT(c.msg_id) AS total
					FROM _dc c, _dc c2, _members m, _members m2
					WHERE (c.privmsgs_to_userid = ? OR c.privmsgs_from_userid = ?)
						AND c.msg_id = c.parent_id
						AND c.msg_deleted <> ?
						AND c.privmsgs_from_userid = m.user_id
						AND c.privmsgs_to_userid = m2.user_id
						AND (IF(c.last_msg_id,c.last_msg_id,c.msg_id) = c2.msg_id)';
				$total_conv = sql_field(sql_filter($sql, $user->d('user_id'), $user->d('user_id'), $user->d('user_id')), 'total', 0);

				$sql = 'SELECT c.msg_id, c.parent_id, c.last_msg_id, c.root_conv, c.privmsgs_date, c.privmsgs_subject, c2.privmsgs_date as last_privmsgs_date, m.user_id, m.username, m.username_base, m2.user_id as user_id2, m2.username as username2, m2.username_base as username_base2
					FROM _dc c, _dc c2, _members m, _members m2
					WHERE (c.privmsgs_to_userid = ? OR c.privmsgs_from_userid = ?)
						AND c.msg_id = c.parent_id
						AND c.msg_deleted <> ?
						AND c.privmsgs_from_userid = m.user_id
						AND c.privmsgs_to_userid = m2.user_id
						AND (IF(c.last_msg_id,c.last_msg_id,c.msg_id) = c2.msg_id)
					ORDER BY c2.privmsgs_date DESC
					LIMIT ??, ??';
				if ($result = sql_rowset(sql_filter($sql, $user->d('user_id'), $user->d('user_id'), $user->d('user_id'), $offset, $config['posts_per_page']))) {
					_style('messages');

					foreach ($result as $row) {
						$dc_with = ($user->d('user_id') == $row['user_id']) ? '2' : '';
						if (!$row['last_msg_id']) {
							$row['last_msg_id'] = $row['msg_id'];
							$row['last_privmsgs_date'] = $row['privmsgs_date'];
						}

						$dc_subject = 'Conversaci&oacute;n con ' . $row['username'.$dc_with];

						_style('messages.item', array(
							'S_MARK_ID' => $row['parent_id'],
							'SUBJECT' => $dc_subject,
							'U_READ' => s_link('my dc read', $row['last_msg_id']) . '#' . $row['last_msg_id'],
							'POST_DATE' => $user->format_date($row['last_privmsgs_date'], 'j F Y \a \l\a\s H:i') . ' horas.',
							'ROOT_CONV' => $row['root_conv'],

							'DC_USERNAME' => $row['username' . $dc_with],
							'DC_PROFILE' => s_link('m', $row['username_base' . $dc_with]))
						);
					}

					build_num_pagination(s_link('my dc s%d'), $total_conv, $config['posts_per_page'], $offset);
				} else if ($total_conv) {
					redirect(s_link('my dc'));
				} else {
					_style('no_messages');
				}

				_style('dc_total', array(
					'TOTAL' => $total_conv)
				);
				break;
		}

		//
		// Get friends for this member
		//
		$sql = 'SELECT DISTINCT m.user_id, m.username, m.username_base
			FROM _members_friends f, _members m
			WHERE (f.user_id = ? AND f.buddy_id = m.user_id)
				OR (f.buddy_id = ? AND f.user_id = m.user_id)
			ORDER BY m.username';
		if ($result = sql_rowset(sql_filter($sql, $user->d('user_id'), $user->d('user_id')))) {
			_style('sdc_friends', array(
				'DC_START' => s_link('my dc start'))
			);

			foreach ($result as $row) {
				_style('sdc_friends.item', array(
					'USERNAME' => $row['username'],
					'URL' => s_link('my dc start', $row['username_base']))
				);
			}
		}

		//
		// Output template
		//
		$page_title = ($mode == 'read') ? lang('dconv_read') : lang('dconvs');

		$layout_vars = array(
			'L_CONV' => $page_title,
			'S_ACTION' => s_link('my dc'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden_fields)
		);

		page_layout($page_title, 'conversations', $layout_vars);
	}

	private function conversations_delete() {
		global $comments, $user;

		$mark	= request_var('mark', array(0));

		if (_button('delete') && $mark) {
			if (_button('confirm')) {
				$comments->dc_delete($mark);
			} else {
				$s_hidden = array('delete' => true);

				$i = 0;
				foreach ($mark as $item) {
					$s_hidden += array('mark[' . $i++ . ']' => $item);
				}

				// Output to template
				//
				$layout_vars = array(
					'MESSAGE_TEXT' => (sizeof($mark) == 1) ? lang('confirm_delete_pm') : lang('confirm_delete_pms'),

					'S_CONFIRM_ACTION' => s_link('my dc'),
					'S_HIDDEN_FIELDS' => s_hidden($s_hidden)
				);
				page_layout('DCONVS', 'confirm', $layout_vars);
			}

			redirect(s_link('my dc'));
		}

		return;
	}

	private function profile() {
		global $user, $config, $comments, $cache, $upload;

		$error = w();
		$fields = w('public_email timezone dateformat location sig msnm yim lastfm website occ interests os fav_genres fav_artists rank color');
		$length_ary = w('location sig msnm yim website occ interests os fav_genres fav_artists');

		$_fields = new stdClass;
		foreach ($fields as $field) {
			$_fields->$field = $user->d('user_' . $field);
		}

		$_fields->avatar = $user->d('user_avatar');
		$_fields->gender = $user->d('user_gender');
		$_fields->hideuser = $user->d('user_hideuser');
		$_fields->email_dc = $user->d('user_email_dc');

		$_fields->birthday_day = (int) substr($user->d('user_birthday'), 6, 2);
		$_fields->birthday_month = (int) substr($user->d('user_birthday'), 4, 2);
		$_fields->birthday_year = (int) substr($user->d('user_birthday'), 0, 4);

		if (_button()) {
			foreach ($_fields as $field => $value) {
				$_fields->$field = request_var($field, $value);
			}

			$_fields->password1 = request_var('password1', '');
			$_fields->password2 = request_var('password2', '');
			$_fields->hideuser = _button('hideuser');
			$_fields->email_dc = _button('email_dc');

			if (!empty($_fields->password1)) {
				if (empty($_fields->password2)) {
					$error[] = 'EMPTY_PASSWORD2';
				}

				if (!sizeof($error)) {
					if ($_fields->password1 != $_fields->password2) {
						$error[] = 'PASSWORD_MISMATCH';
					} else if (strlen($_fields->password1) > 30) {
						$error[] = 'PASSWORD_LONG';
					}
				}
			}

			unset($_fields->password1, $_fields->password2);

			foreach ($length_ary as $field) {
				if (strlen($_fields->$field) < 2) {
					$_fields->$field = '';
				}
			}

			if (!empty($_fields->website)) {
				if (!preg_match('#^http[s]?:\/\/#i', $_fields->website)) {
					$_fields->website = 'http://' . $_fields->website;
				}

				if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $_fields->website)) {
					$_fields->website = '';
				}
			}

			if (!empty($_fields->rank)) {
				$rank_word = explode(' ', $_fields->rank);
				if (sizeof($rank_word) > 10) {
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
			if (!empty($_fields->rank) && !sizeof($error)) {
				$sql = 'SELECT rank_id
					FROM _ranks
					WHERE rank_title = ?';
				if (!$rank_id = sql_field(sql_filter($sql, $_fields->rank), 'rank_id', 0)) {
					$insert = array(
						'rank_title' => $_fields->rank,
						'rank_min' => -1,
						'rank_max' => -1,
						'rank_special' => 1
					);
					$rank_id = sql_insert('ranks', $insert);
				}

				if ($user->d('user_rank')) {
					$sql = 'SELECT user_id
						FROM _members
						WHERE user_rank = ?';
					$size_rank = sql_rowset(sql_filter($sql, $user->d('user_rank')), false, 'user_id');

					if (sizeof($size_rank) == 1) {
						$sql = 'DELETE FROM _ranks
							WHERE rank_id = ?';
						sql_query(sql_filter($sql, $user->d('user_rank')));
					}
				}

				$_fields->rank = $rank_id;
				$cache->delete('ranks');
			}

			if (!$_fields->birthday_month || !$_fields->birthday_day || !$_fields->birthday_year) {
				$error[] = 'EMPTY_BIRTH_MONTH';
			}

			// Update user avatar
			if (!sizeof($error)) {
				$upload->avatar_process($user->d('username_base'), $_fields, $error);
			}

			if (!sizeof($error)) {
				if (!empty($_fields->sig)) {
					$_fields->sig = $comments->prepare($_fields->sig);
				}

				$_fields->birthday = (string) (leading_zero($_fields->birthday_year) . leading_zero($_fields->birthday_month) . leading_zero($_fields->birthday_day));
				unset($_fields->birthday_day, $_fields->birthday_month, $_fields->birthday_year);

				$_fields->dateformat = 'd M Y H:i';
				$_fields->hideuser = $user->d('user_hideuser');
				$_fields->email_dc = $user->d('user_email_dc');

				$member_data = w();
				foreach ($_fields as $field => $value) {
					if ($value != $user->d($field)) {
						$member_data['user_' . $field] = $_fields->$field;
					}
				}

				if (sizeof($member_data)) {
					$sql = 'UPDATE _members SET ' . sql_build('UPDATE', $member_data) . sql_filter('
						WHERE user_id = ?', $user->d('user_id'));

					$sql = 'UPDATE _members SET ??
						WHERE user_id = ?';
					sql_query(sql_filter($sql, sql_build('UPDATE', $member_data), $user->d('user_id')));
				}

				redirect(s_link('m', $user->d('username_base')));
			}
		}

		if (sizeof($error)) {
			_style('error', array(
				'MESSAGE' => parse_error($error))
			);
		}

		if ($user->d('user_avatar')) {
			_style('current_avatar', array(
				'IMAGE' => $config['assets_url'] . 'avatars/' . $user->d('user_avatar'))
			);
		}

		$s_genders_select = '';
		foreach (array(1 => 'MALE', 2 => 'FEMALE') as $id => $value) {
			$s_genders_select .= '<option value="' . $id . '"' . (($_fields->gender == $id) ? ' selected="true"' : '') . '>' . lang($value) . '</option>';
		}

		_style('gender', array(
			'GENDER_SELECT' => $s_genders_select)
		);

		$s_day_select = '';
		for ($i = 1; $i < 32; $i++) {
			$s_day_select .= '<option value="' . $i . '"' . (($_fields->birthday_day == $i) ? ' selected="true"' : '') . '>' . $i . '</option>';
		}

		$s_month_select = '';
		$months = array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');
		foreach ($months as $id => $value) {
			$s_month_select .= '<option value="' . ($id + 1) . '"' . (($_fields->birthday_month == ($id + 1)) ? ' selected="true"' : '') . '>' . $user->lang['datetime'][$value] . '</option>';
		}

		$s_year_select = '';
		for ($i = 2005; $i > 1899; $i--) {
			$s_year_select .= '<option value="' . $i . '"' . (($_fields->birthday_year == $i) ? ' selected="true"' : '') . '>' . $i . '</option>';
		}

		_style('birthday', array(
			'DAY' => $s_day_select,
			'MONTH' => $s_month_select,
			'YEAR' => $s_year_select)
		);

		$dateformat_select = '';
		foreach ($dateset as $id => $value) {
			$dateformat_select .= '<option value="' . $id . '"' . (($value == $_fields->dateformat) ? ' selected="selected"' : '') . '>' . $user->format_date(time(), $value) . '</option>';
		}

		$timezone_select = '';
		foreach ($user->lang['zones'] as $id => $value) {
			$timezone_select .= '<option value="' . $id . '"' . (($id == $_fields->timezone) ? ' selected="selected"' : '') . '>' . $value . '</option>';
		}

		unset($_fields->timezone, $_fields->dateformat);

		if ($user->d('rank')) {
			$sql = 'SELECT rank_title
				FROM _ranks
				WHERE rank_id = ?';
			$_fields->rank = sql_field(sql_filter($sql, $user->d('rank')), 'rank_title', '--');
		}

		$output_vars = array(
			'DATEFORMAT' => $dateformat_select,
			'TIMEZONE' => $timezone_select,
			'HIDEUSER_SELECTED' => ($_fields->hideuser) ? ' checked="checked"' : '',
			'EMAIL_DC_SELECTED' => ($_fields->email_dc) ? ' checked="checked"' : ''
		);

		foreach ($_fields as $field => $value) {
			$output_vars[strtoupper($field)] = $value;
		}
		v_style($output_vars);

		$this->_title = 'MEMBER_OPTIONS';
		$this->_template = 'profile';

		return;
	}

	private function userpage() {
		global $user, $comments;

		$mode = request_var('mode', 'main');

		if ($user->d('user_id') != $this->data['user_id'] && !in_array($mode, w('friend ban'))) {
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
					'URL' => s_link('m', $this->data['username_base'], 'ban'),
					'LANG' => lang('blocked_member_' . $banned_lang))
				);
			}
		}

		$profile_fields = $comments->user_profile($this->data);

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
			$panel_selection['start'] = array('L' => 'DCONV_START', 'U' => s_link('my dc start', $this->data['username_base']));
		} else {
			$panel_selection['dc'] = array('L' => 'DC', 'U' => s_link('my dc'));
		}

		$panel_selection += array(
			'friends' => array('L' => 'FRIENDS', 'U' => false)
		);

		foreach ($panel_selection as $link => $data) {
			_style('selected_panel', array(
				'LANG' => lang('userpage_' . $data['L']))
			);

			if ($mode == $link) {
				_style('selected_panel.strong');
				continue;
			}

			_style('selected_panel.a', array(
				'URL' => ($data['U'] !== false) ? $data['U'] : s_link('m', $this->data['username_base'], (($link != 'main') ? $link : '')))
			);
		}

		//
		// Check if friends
		//
		if ($user->d('user_id') != $this->data['user_id']) {
			$friend_add_lang = true;

			if ($user->is('member')) {
				$friend_add_lang = $this->is_friend($user->d('user_id'), $this->data['user_id']);
			}

			$friend_add_lang = ($friend_add_lang) ? 'friends_add' : 'friends_del';

			_style('friend', array(
				'U_FRIEND' => s_link('m', $this->data['username_base'], 'friend'),
				'L_FRIENDS_ADD' => lang($friend_add_lang))
			);
		}

		//
		// Generate page
		//
		v_style(array(
			'USERNAME' => $this->data['username'],
			'POSTER_RANK' => $profile_fields['user_rank'],
			'AVATAR_IMG' => $profile_fields['user_avatar'],
			'USER_ONLINE' => $online,

			'PM' => s_link('my dc start', $this->data['username_base']),
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

		if ($user->d('user_id') == $this->data['user_id']) {
			redirect(s_link('m', $this->data['username_base']));
		}

		$sql = 'SELECT *
			FROM _members_friends
			WHERE user_id = ?
				AND buddy_id = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $user->d('user_id'), $this->data['user_id']))) {
			$sql = 'DELETE FROM _members_friends
				WHERE user_id = ?
					AND buddy_id = ?';
			sql_query(sql_filter($sql, $user->d('user_id'), $this->data['user_id']));

			if ($row['friend_time']) {
				//$user->points_remove(1);
			}

			$user->delete_unread($this->data['user_id'], $user->d('user_id'));

			redirect(s_link('m', $this->data['username_base']));
		}

		$sql_insert = array(
			'user_id' => $user->d('user_id'),
			'buddy_id' => $this->data['user_id'],
			'friend_time' => time()
		);
		sql_insert('members_friends', $sql_insert);

		$user->save_unread(UH_FRIEND, $user->d('user_id'), 0, $this->data['user_id']);

		redirect(s_link('m', $user->d('username_base'), 'friends'));
	}

	public function friend_list() {
		global $user, $comments;

		$sql = 'SELECT DISTINCT u.user_id AS user_id, u.username, u.username_base, u.user_avatar, u.user_rank, u.user_gender, u.user_posts
			FROM _members_friends b, _members u
			WHERE (b.user_id = ?
				AND b.buddy_id = u.user_id) OR
				(b.buddy_id = ?
					AND b.user_id = u.user_id)
			ORDER BY u.username';
		if ($result = sql_rowset(sql_filter($sql, $this->data['user_id'], $this->data['user_id']))) {
			_style('friends');

			foreach ($result as $row) {
				$friend_profile = $comments->user_profile($row);

				_style('friends.row', array(
					'PROFILE' => $friend_profile['profile'],
					'USERNAME' => $friend_profile['username'],
					'AVATAR' => $friend_profile['user_avatar'],
					'RANK' => $friend_profile['user_rank'])
				);
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

		if ($user->d('user_id') == $this->data['user_id']) {
			redirect(s_link('m', $this->data['username_base']));
		}

		if ($epbi) {
			fatal_error();
		}

		$sql = 'SELECT ban_id
			FROM _members_ban
			WHERE user_id = ?
				AND banned_user = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $user->d('user_id'), $this->data['user_id']))) {
			$sql = 'DELETE FROM _members_ban
				WHERE ban_id = ?';
			sql_query(sql_filter($sql, $row['ban_id']));

			redirect(s_link('m', $this->data['username_base']));
		}

		$sql_insert = array(
			'user_id' => $user->d('user_id'),
			'banned_user' => $this->data['user_id'],
			'ban_time' => $user->time
		);
		sql_insert('members_ban', $sql_insert);

		$sql = 'DELETE FROM _members_friends
			WHERE user_id = ?
				AND buddy_id = ?';
		sql_query(sql_filter($sql, $user->d('user_id'), $this->data['user_id']));

		$sql = 'DELETE FROM _members_friends
			WHERE user_id = ?
				AND buddy_id = ?';
		sql_query(sql_filter($sql, $this->data['user_id'], $user->d('user_id')));

		$sql = 'DELETE FROM _members_viewers
			WHERE user_id = ?
				AND viewer_id = ?';
		sql_query(sql_filter($sql, $this->data['user_id'], $user->d('user_id')));

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
				'KEY' => lang($key),
				'VALUE' => $value)
			);
		}

		return true;
	}

	public function user_main() {
		global $user, $comments;

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

			$random_images = w();
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

			$random_images2 = w();
			foreach ($result_images as $row) {
				if (!isset($random_images2[$row['ub']])) {
					$random_images2[$row['ub']] = $row['image'];
				}
			}

			$total_a = 0;
			$selected_artists2 = w();

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
				'DATE' => $user->format_date($row['post_time'], lang('date_format')))
			);
		}

		//
		// GET USERPAGE MESSAGES
		//
		$comments_ref = s_link('m', $this->data['username_base']);

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
			$age .= ' ' . lang('years');
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

		$gender = lang($gender);

		$user_fields = array(
			//'JOINED' => ($this->data['user_regdate'] && (!$this->data['user_hideuser'] || $epbi2)) ? $user->format_date($this->data['user_regdate']) . sprintf(lang('joined_since'), $memberdays) : '',
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
			if ($value == '') continue;

			if (!$m) {
				_style('main.general');
				$m = 1;
			}

			_style('main.general.item', array(
				'KEY' => lang($key),
				'VALUE' => $value)
			);
		}

		//
		// Get Last.fm Feed
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
		// Get public messages
		//
		$comments_ref = s_link('m', $this->data['username_base']);
		if ($this->data['userpage_posts']) {
			$comments->reset();
			$comments->ref = $comments_ref;

			$sql = 'SELECT p.*, u2.user_id, u2.username, u2.username_base, u2.user_avatar
				FROM _members_posts p, _members u, _members u2
				WHERE p.userpage_id = ?
					AND p.userpage_id = u.user_id
					AND p.post_active = 1
					AND p.poster_id = u2.user_id
				ORDER BY p.post_time DESC
				LIMIT 50';

			$comments->data = array(
				'USER_ID_FIELD' => 'userpage_id',
				'S_DELETE_URL' => s_link('acp', 'user_post_delete', 'msg_id:%d'),
				'SQL' => sql_filter($sql, $this->data['user_id'])
			);

			$comments->view(0, '', $this->data['userpage_posts'], $this->data['userpage_posts'], 'main.posts');
		}

		if ($user->is('member')) {
			_style('main.box', array(
				'REF' => $comments_ref)
			);
		}

		return true;
	}
}
