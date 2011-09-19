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

if (!$user->data['is_member']) {
	if ($user->data['is_bot']) {
		redirect(s_link());
	}
	do_login();
}

//
//  Get profile username
$viewprofile = request_var('member', '');
$mode = request_var('mode', '');

if (empty($viewprofile)) {
	fatal_error();
}

$viewprofile = phpbb_clean_username($viewprofile);

$sql = "SELECT *
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
		)";
if (!$profiledata = sql_fieldrow(sql_filter($sql, $viewprofile, USER_INACTIVE, USER_IGNORE, $user->data['user_id']))) {
	fatal_error();
}

//
if (empty($mode)) {
	$mode = 'main';
}

$user->setup();

require('./interfase/comments.php');
$comments = new _comments();

require('./interfase/userpage.php');
$userpage = new userpage();

$current_time = time();
$epbi = $user->_team_auth('all', $profiledata['user_id']);
$epbi2 = $user->_team_auth('all');

//
// UPDATE LAST PROFILE VIEWERS LIST
// 1 && (0 || 1 && 0 && 1) && 1
if ($user->data['is_member'] && $user->data['user_id'] != $profiledata['user_id'] && !in_array($mode, array('friend', 'ban'))) {
	$is_blocked_member = false;
	if (!$epbi) {
		$sql = 'SELECT ban_id
			FROM _members_ban
			WHERE user_id = ?
				AND banned_user = ?';
		if ($banned_row = sql_fieldrow(sql_filter($sql, $user->data['user_id'], $profiledata['user_id']))) {
			$is_blocked_member = true;
			$banned_user_lang = 'BLOCKED_MEMBER_REMOVE';
		}
		
		$template->assign_block_vars('block_member', array(
			'URL' => s_link('m', array($profiledata['username_base'], 'ban')),
			'LANG' => $user->lang[$banned_user_lang])
		);
	}
	
	$update_viewer_log = true;
	if ($is_blocked_member || ($user->data['user_hideuser'] && !$epbi) || ($user->_team_auth('founder') && $user->data['user_hideuser'])) {
		$update_viewer_log = false;
	}
	
	if ($update_viewer_log) {
		$sql = 'UPDATE _members_viewers
			SET datetime = ?, user_ip = ?
			WHERE user_id = ? 
				AND viewer_id = ?';
		sql_query(sql_filter($sql, $current_time, $user->ip, $profiledata['user_id'], $user->data['user_id']));
		
		if (!sql_affectedrows()) {
			$sql_insert = array(
				'user_id' => $profiledata['user_id'],
				'viewer_id' => $user->data['user_id'],
				'user_ip' => $user->ip,
				'datetime' => $user->ip
			);
			$sql = 'INSERT INTO _members_viewers' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
			
			$sql = 'SELECT viewer_id
				FROM _members_viewers
				WHERE user_id = ?
				ORDER BY datetime DESC
				LIMIT 9, 1';
			if ($row = sql_fieldrow(sql_filter($sql, $profiledata['user_id']))) {
				$sql = 'DELETE FROM _members_viewers
					WHERE user_id = ?
						AND viewer_id = ?';
				sql_query(sql_filter($sql, $profiledata['user_id'], $row['viewer_id']));
			}
		}
	}
}

//
// Get extra information for this user
//
$profile_fields = $comments->user_profile($profiledata);

switch ($mode) {
	case 'friend':
		$userpage->friend_add();
		break;
	case 'ban':
		$userpage->user_ban();
		break;
	case 'favs':
		break;
	case 'main':
	default:
		$userpage->user_main();
		break;
	case 'friends':
		$userpage->friend_list();
		break;
	case 'stats':
		$userpage->user_stats();
		break;
}

$panel_selection = array(
	'main' => array('L' => 'MAIN', 'U' => false),
	'dc' => array('L' => 'DC', 'U' => s_link('my', 'dc')),
	'friends' => array('L' => 'FRIENDS', 'U' => false)
);

foreach ($panel_selection as $link => $data) {
	$template->assign_block_vars('selected_panel', array(
		'LANG' => $user->lang['USERPAGE_' . $data['L']])
	);
	
	if ($mode == $link) {
		$template->assign_block_vars('selected_panel.strong', array());
		continue;
	}
	
	$template->assign_block_vars('selected_panel.a', array(
		'URL' => ($data['U'] !== false) ? $data['U'] : s_link('m', array($profiledata['username_base'], (($link != 'main') ? $link : ''))))
	);
}

//
// Check if friends
//
if ($user->data['user_id'] != $profiledata['user_id']) {
	$friend_add_lang = true;
	
	if ($user->data['is_member']) {
		$friend_add_lang = $userpage->is_friend($user->data['user_id'], $profiledata['user_id']);
	}
	
	$friend_add_lang = ($friend_add_lang) ? 'FRIENDS_ADD' : 'FRIENDS_DEL';
	
	$template->assign_block_vars('friend', array(
		'U_FRIEND' => s_link('m', array($profiledata['username_base'], 'friend')),
		'L_FRIENDS_ADD' => $user->lang[$friend_add_lang])
	);
}

$template->assign_block_vars('customcolor', array(
	'COLOR' => $profiledata['user_color'])
);

//
// Generate page
//
$template_vars = array(
	'USERNAME' => $profiledata['username'],
	'USERNAME_COLOR' => $profiledata['user_color'],
	'POSTER_RANK' => $profile_fields['user_rank'],
	'AVATAR_IMG' => $profile_fields['user_avatar'],
	'USER_ONLINE' => $online,
	
	'PM' => s_link('my', array('dc', 'start', $profiledata['username_base'])),
	'WEBSITE' => $profiledata['user_website'],
	'MSN' => $profiledata['user_msnm']
);

$use_template = 'userpage_body';
$use_m_template = 'profiles/' . $profiledata['username_base'];
if (@file_exists(ROOT . 'template/' . $use_m_template . '.htm'))
{
	$use_template = $use_m_template;
}

page_layout($profiledata['username'], $use_template, $template_vars);

?>