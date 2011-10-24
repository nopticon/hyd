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
define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();

$mode = request_var('mode', '');
$submit = (isset($_POST['submit'])) ? true : false;

if (($mode == 'password' || $mode == 'verify')) {
	if ($user->data['is_bot']) {
		redirect(s_link());
	}
	
	if ($user->data['is_member']) {
		redirect(s_link('my', 'profile'));
	}
	
	$user->setup();
}

switch ($mode) {
	case 'password':
		$template_vars = array();
		
		if ($submit) {
			$email = request_var('address', '');
			if (empty($email)) {
				fatal_error();
			}
			
			if (!email_format($email)) {
				fatal_error();
			}
			
			$process = false;
			
			$sql = 'SELECT *
				FROM _members
				WHERE user_email = ?
					AND user_type NOT IN (??, ??, ??)
					AND user_active = 1';
			if ($userdata = sql_fieldrow(sql_filter($sql, $email, USER_INACTIVE, USER_IGNORE, USER_FOUNDER))) {
				$process = true;
			}
			
			$sql = 'SELECT *
				FROM _crypt_confirm
				WHERE crypt_userid = ?';
			if (sql_fieldrow($sql, $userdata['user_id'])) {
				fatal_error();
			}
			
			$sql = 'SELECT *
				FROM _banlist
				WHERE ban_userid = ?';
			if ($ban_data = sql_fieldrow($sql, $userdata['user_id'])) {
				fatal_error();
			}
			
			if ($process) {
				require('./interfase/emailer.php');
				$emailer = new emailer();
				
				$verification_code = md5(unique_id());
				
				$insert = array(
					'crypt_userid' => $userdata['user_id'],
					'crypt_code' => $verification_code,
					'crypt_time' => $user->time
				);
				$sql = 'INSERT INTO _crypt_confirm' . sql_build('INSERT', $insert);
				sql_query($sql);
				
				// Send email
				$emailer->from('info@rockrepublik.net');
				$emailer->use_template('user_activate_passwd', $config['default_lang']);
				$emailer->email_address($userdata['user_email']);
				
				$emailer->assign_vars(array(
					'USERNAME' => $userdata['username'],
					'U_ACTIVATE' => s_link('my', array('verify', $verification_code)))
				);
				$emailer->send();
				$emailer->reset();
			}
		}
		
		$template_vars += array(
			'PAGE_MODE' => (($process) ? 'submit' : 'nosubmit'),
			'S_ACTION' => s_link('my', 'password')
		);
		
		page_layout('SENDPASSWORD', 'password', $template_vars);
		break;
	case 'verify':
		$code = request_var('code', '');
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
		
		if ($submit) {
			$password = request_var('newkey', '');
			
			if (!empty($password)) {
				$crypt_password = user_password($password);
				
				$sql = 'UPDATE _members SET user_password = ?
					WHERE user_id = ?';
				sql_query(sql_filter($sql, $crypt_password, $crypt_data['user_id']));
				
				$sql = 'DELETE FROM _crypt_confirm
					WHERE crypt_code = ?
						AND crypt_userid = ?';
				sql_query(sql_filter($sql, $code, $crypt_data['user_id']));
				
				// Send email
				require('./interfase/emailer.php');
				$emailer = new emailer();
				
				$emailer->from('Rock Republik Networks <info@rockrepublik.net>');
				$emailer->use_template('user_confirm_passwd', $config['default_lang']);
				$emailer->email_address($crypt_data['user_email']);
				
				$emailer->assign_vars(array(
					'USERNAME' => $crypt_data['username'],
					'PASSWORD' => $password,
					'U_PROFILE' => s_link('m', $crypt_data['username_base']))
				);
				$emailer->send();
				$emailer->reset();
				
				//
				$template_vars = array(
					'PAGE_MODE' => 'updated'
				);
				page_layout('SENDPASSWORD', 'password', $template_vars);
			}
		}
		
		$template_vars = array(
			'PAGE_MODE' => 'verify',
			'S_ACTION' => s_link('my', array('verify', $code))
		);
		page_layout('SENDPASSWORD', 'password', $template_vars);
		break;
	case 'confirm':
		$code = request_var('code', '');
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
		
		// Unread
		$u_topics = array(288, 1455);
		foreach ($u_topics as $v)
		{
			$user->save_unread(UH_T, $v, 0, $user_id);
		}
		$user->points_add(3, $user_id);
		
		//
		$sql = 'SELECT *
			FROM _members_ref_assoc
			WHERE ref_uid = ?';
		if ($ref_assoc = sql_fieldrow(sql_filter($sql, $user_id))) {
			if ($user_id != $ref_assoc['ref_orig']) {
				$user->points_add(3, $ref_assoc['ref_orig']);
				
				$sql_insert = array(
					'user_id' => $user_id,
					'buddy_id' => $ref_assoc['ref_orig'],
					'friend_time' => time()
				);
				$sql = 'INSERT INTO _members_friends' . sql_build('INSERT', $sql_insert);
				sql_query($sql);
				
				$sql_insert = array(
					'user_id' => $ref_assoc['ref_orig'],
					'buddy_id' => $user_id,
					'friend_time' => time()
				);
				$sql = 'INSERT INTO _members_friends' . sql_build('INSERT', $sql_insert);
				sql_query($sql);
			
				$user->save_unread(UH_FRIEND, $user_id, 0, $ref_assoc['ref_orig']);
			}
			
			$sql = 'DELETE FROM _members_ref_assoc
				WHERE ref_id = ?';
			sql_query(sql_filter($sql, $ref_assoc['ref_id']));
		}
		
		//
		$sql = 'SELECT *
			FROM _members_ref_invite
			WHERE invite_email = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $crypt_data['user_email']))) {
			$sql = 'DELETE FROM _members_ref_invite
				WHERE invite_code = ?';
			sql_query(sql_filter($sql, $row['invite_code']));
		}
		
		//
		require('./interfase/emailer.php');
		$emailer = new emailer();
		
		$emailer->from('info@rockrepublik.net');
		$emailer->use_template('user_welcome_confirm');
		$emailer->email_address($crypt_data['user_email']);
		
		$emailer->assign_vars(array(
			'USERNAME' => $crypt_data['username'])
		);
		$emailer->send();
		$emailer->reset();
		
		//
		if (empty($user->data)) {
			$user->init();
		}
		if (empty($user->lang)) {
			$user->setup();
		}
		
		$custom_vars = array(
			'S_REDIRECT' => '',
			'MESSAGE_TITLE' => $user->lang['INFORMATION'],
			'MESSAGE_TEXT' => $user->lang['MEMBERSHIP_ADDED_CONFIRM']
		);
		page_layout('INFORMATION', 'message', $custom_vars);
		break;
}

//
// Check user session
//
if (!$user->data['is_member']) {
	do_login();
}

//
// Set user settings
//
$user->setup();

switch ($mode) {
	case 'profile':
		$user_fields = array(
			'public_email' => (string) $user->data['user_public_email'],
			'timezone' => (double) $user->data['user_timezone'],
			'dateformat' => (string) $user->data['user_dateformat'],
			'location' => (string) $user->data['user_location'],
			'sig' => (string) $user->data['user_sig'],
			'msnm' => (string) $user->data['user_msnm'],
			'yim' => (string) $user->data['user_yim'],
			'aim' => (string) $user->data['user_aim'],
			'icq' => (string) $user->data['user_icq'],
			'lastfm' => (string) $user->data['user_lastfm'],
			'website' => (string) $user->data['user_website'],
			'occ' => (string) $user->data['user_occ'],
			'interests' => (string) $user->data['user_interests'],
			'os' => (string) $user->data['user_os'],
			'fav_genres' => (string) $user->data['user_fav_genres'],
			'fav_artists' => (string) $user->data['user_fav_artists'],
			'rank' => '',
			'avatar' => '',
			'color' => (string) $user->data['user_color']
		);
		
		$user_fields['gender'] = (int) $user->data['user_gender'];
		
		$user_fields['birthday_day'] = (int) substr($user->data['user_birthday'], 6, 2);
		$user_fields['birthday_month'] = (int) substr($user->data['user_birthday'], 4, 2);
		$user_fields['birthday_year'] = (int) substr($user->data['user_birthday'], 0, 4);
		
		foreach ($user_fields as $name => $value) {
			$$name = $value;
		}
		
		$hideuser = (int) $user->data['user_hideuser'];
		$email_dc = (int) $user->data['user_email_dc'];
		
		$error = array();
		$dateset = array(
			'd M Y H:i',
			'M d, Y H:i'
		);
		
		if ($submit) {
			$e_mb = array('timezone');
			$e2_mb = array();
			foreach ($e_mb as $k) {
				$e2_mb[$k] = true;
			}
			
			foreach ($user_fields as $name => $value) {
				$multibyte = true;
				if ($e2_mb[$name]) {
					$multibyte = false;
				}
				$$name = request_var($name, $value, $multibyte);
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
			
			$check_length_ary = array('location', 'sig', 'msnm', 'yim', 'aim', 'icq', 'website', 'occ', 'interests', 'os', 'fav_genres', 'fav_artists');
			foreach ($check_length_ary as $name) {
				if (strlen($$name) < 3) {
					$$name = '';
				}
			}
			
			if ($icq && !preg_match('/[0-9]+$/', $icq)) {
				$icq = '';
			}
			
			if (!empty($website)) {
				if (!preg_match('#^http[s]?:\/\/#i', $website)) {
					$website = 'http://' . $website;
				}
		
				if (!preg_match('#^http[s]?\\:\\/\\/[a-z0-9\-]+\.([a-z0-9\-]+\.)?[a-z]+#i', $website)) {
					$website = '';
				}
			}

			if (!empty($color)) {
				$color = substr($color, 1);
				if ($color !== $user->data['user_color']) {
					$color = strtoupper($color);
					if (strlen($color) != 6) {
						$error[] = 'USERPAGE_ERROR';
					}
					
					if (!sizeof($error)) {
						$valid_hex = explode(',', '0,1,2,3,4,5,6,7,8,9,A,B,C,D,E,F');
						for ($i = 0, $end = strlen($color); $i < $end; $i++)
						{
							if (!in_array($color[$i], $valid_hex))
							{
								$error[] = 'USERPAGE_ERROR';
							}
						}
					}
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
				require('./interfase/functions_avatar.php');
				
				if ($xavatar->process()) {
					$avatar = $xavatar->file();
				}
			}
			
			if (!sizeof($error)) {
				require('./interfase/comments.php');
				$comments = new _comments();
				
				if (!empty($sig)) {
					$sig = $comments->prepare($sig);
				}
				
				unset($user_fields['birthday_day'], $user_fields['birthday_month'], $user_fields['birthday_year']);
				
				$dateformat = $dateset[$dateformat];
				$user_fields['hideuser'] = $user->data['user_hideuser'];
				$user_fields['email_dc'] = $user->data['user_email_dc'];
				
				$member_data = array();
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
		//
		// END SUBMIT
		//
		
		//unset($user_fields['gender'], $user_fields['birthday_day'], $user_fields['birthday_month'], $user_fields['birthday_year']);
		
		if (sizeof($error)) {
			$error = preg_replace('#^([0-9A-Z_]+)$#e', "(isset(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);
			
			$template->assign_block_vars('error', array(
				'MESSAGE' => (sizeof($error)) ? implode('<br />', $error) : '')
			);
		}
		
		if ($user->data['user_avatar']) {
			$template->assign_block_vars('current_avatar', array(
				'IMAGE' => $config['avatar_path'] . '/' . $user->data['user_avatar'])
			);
		}
		
		$s_genders_select = '';
		foreach (array(1 => 'MALE', 2 => 'FEMALE') as $id => $value) {
			$s_genders_select .= '<option value="' . $id . '"' . (($gender == $id) ? ' selected="true"' : '') . '>' . $user->lang[$value] . '</option>';
		}
		
		$template->assign_block_vars('gender', array(
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
		
		$template->assign_block_vars('birthday', array(
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
		
		$template->assign_vars($output_vars);
		
		page_layout('MEMBER_OPTIONS', 'profile');
		break;
}

?>