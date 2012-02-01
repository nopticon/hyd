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
require_once('./interfase/common.php');
require_once(ROOT . 'interfase/comments.php');

//
// Cancel 
//
if (isset($_POST['cancel'])) {
	redirect(s_link('my', 'dc'));
}

$user->init();
$user->setup();

if (!$user->is('member')) {
	do_login();
}

$comments = new _comments();

/*
 * Delete conversations
 */
$mark	= request_var('mark', array(0));

if (isset($_POST['delete']) && $mark) {
	if (isset($_POST['confirm'])) {
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
			'MESSAGE_TEXT' => (sizeof($mark) == 1) ? $user->lang['CONFIRM_DELETE_PM'] : $user->lang['CONFIRM_DELETE_PMS'], 

			'S_CONFIRM_ACTION' => s_link('my', 'dc'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden)
		);
		page_layout('DCONVS', 'confirm', $layout_vars);
	}
	
	redirect(s_link('my', 'dc'));
}

//
// Submit
//
$submit = (isset($_POST['post'])) ? true : 0;
$msg_id = intval(request_var('p', 0));
$mode = request_var('mode', '');
$error = array();

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
			if (!$to_userdata = sql_fieldrow(sql_filter($sql, $parent_id, $user->data['user_id'], $user->data['user_id']))) {
				fatal_error();
			}
			
			$privmsgs_to_userid = ($user->data['user_id'] == $to_userdata['privmsgs_to_userid']) ? 'privmsgs_from_userid' : 'privmsgs_to_userid';
			$to_userdata['user_id'] = $to_userdata[$privmsgs_to_userid];
		} else {
			$member = request_var('member', '');
			if (!empty($member)) {
				$member = get_username_base(phpbb_clean_username($member), true);
				if ($member !== false) {
					$sql = 'SELECT user_id, username, username_base, user_email
						FROM _members
						WHERE username_base = ?
							AND user_type <> ?';
					if (!$to_userdata = sql_fieldrow(sql_filter($sql, $member, USER_IGNORE))) {
						$error[] = 'NO_SUCH_USER';
					}

					if (!sizeof($error) && $to_userdata['user_id'] == $user->data['user_id']) {
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
			if ($ban_profile = sql_fieldrow(sql_filter($sql, $to_userdata['user_id'], $user->data['user_id']))) {
				$error[] = 'BLOCKED_MEMBER';
			}
		}
		
		$dc_message = request_var('message', '');
		if (empty($dc_message)) {
			$error[] = 'EMPTY_MESSAGE';
		}
		
		if (!sizeof($error)) {
			$dc_id = $comments->store_dc($mode, $to_userdata, $user->data, $dc_subject, $dc_message, true, true);
			
			redirect(s_link('my', array('dc', 'read', $dc_id)) . '#' . $dc_id);
		}
	}
}

//
// Start error handling
//
if (sizeof($error)) {
	$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);
	
	_style('error', array(
		'MESSAGE' => implode('<br />', $error))
	);
	
	if ($mode == 'reply') {
		$mode = 'read';
	}
}

$s_hidden_fields = array();

switch ($mode) {
	case 'start':
		//
		// Start new conversation
		//
		if (!$submit) {
			$member = request_var('member', '');
			if ($member != '') {
				$member = get_username_base(phpbb_clean_username($member));
				
				$sql = 'SELECT user_id, username, username_base
					FROM _members
					WHERE username_base = ?
						AND user_type <> ?';
				$row = sql_fieldrow(sql_filter($sql, $member, USER_IGNORE));
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
		if (!$msg_data = sql_fieldrow(sql_filter($sql, $msg_id, $user->data['user_id'], $user->data['user_id'], $user->data['user_id']))) {
			fatal_error();
		}
		
		//
		// Get all messages for this conversation
		//
		$sql = 'SELECT c.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar, m.user_sig, m.user_rank, m.user_gender, m.user_posts
			FROM _dc c, _members m
			WHERE c.parent_id = ?
				AND c.privmsgs_from_userid = m.user_id
			ORDER BY c.privmsgs_date';
		if (!$result = sql_rowset(sql_filter($sql, $msg_data['parent_id']))) {
			fatal_error();
		}
		
		$with_user = $msg_data['privmsgs_to_userid'];
		if ($with_user == $user->data['user_id']) {
			$with_user = $msg_data['privmsgs_from_userid'];
		}
		
		$sql = 'SELECT username
			FROM _members
			WHERE user_id = ?';
		$with_username = sql_field(sql_filter($sql, $with_user), 'username', '');
		
		_style('conv', array(
			'URL' => s_link('my', 'dc'),
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
				'MESSAGE' => $comments->parse_message($row['privmsgs_text'], 'bold orange'),
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
		$total_conv = sql_field(sql_filter($sql, $user->data['user_id'], $user->data['user_id'], $user->data['user_id']), 'total', 0);
		
		$sql = 'SELECT c.msg_id, c.parent_id, c.last_msg_id, c.root_conv, c.privmsgs_date, c.privmsgs_subject, c2.privmsgs_date as last_privmsgs_date, m.user_id, m.username, m.username_base, m.user_color, m2.user_id as user_id2, m2.username as username2, m2.username_base as username_base2, m2.user_color as user_color2
			FROM _dc c, _dc c2, _members m, _members m2
			WHERE (c.privmsgs_to_userid = ? OR c.privmsgs_from_userid = ?)
				AND c.msg_id = c.parent_id
				AND c.msg_deleted <> ?
				AND c.privmsgs_from_userid = m.user_id
				AND c.privmsgs_to_userid = m2.user_id
				AND (IF(c.last_msg_id,c.last_msg_id,c.msg_id) = c2.msg_id)
			ORDER BY c2.privmsgs_date DESC 
			LIMIT ??, ??';
		if ($result = sql_rowset(sql_filter($sql, $user->data['user_id'], $user->data['user_id'], $user->data['user_id'], $offset, $config['posts_per_page']))) {
			_style('messages', array());
			
			foreach ($result as $row) {
				$dc_with = ($user->data['user_id'] == $row['user_id']) ? '2' : '';
				if (!$row['last_msg_id']) {
					$row['last_msg_id'] = $row['msg_id'];
					$row['last_privmsgs_date'] = $row['privmsgs_date'];
				}
				
				$dc_subject = 'Conversaci&oacute;n con ' . $row['username'.$dc_with];
				
				_style('messages.item', array(
					'S_MARK_ID' => $row['parent_id'],
					'SUBJECT' => $dc_subject,
					'U_READ' => s_link('my', array('dc', 'read', $row['last_msg_id'])) . '#' . $row['last_msg_id'],
					'POST_DATE' => $user->format_date($row['last_privmsgs_date'], 'j F Y \a \l\a\s H:i') . ' horas.',
					'ROOT_CONV' => $row['root_conv'],
					
					'DC_USERNAME' => $row['username'.$dc_with],
					'DC_PROFILE' => s_link('m', $row['username_base'.$dc_with]),
					'DC_COLOR' => $row['user_color'.$dc_with])
				);
			}
			
			build_num_pagination(s_link('my', array('dc', 's%d')), $total_conv, $config['posts_per_page'], $offset);
		} else if ($total_conv) {
			redirect(s_link('my', 'dc'));
		} else {
			_style('no_messages', array());
		}
		
		_style('dc_total', array(
			'TOTAL' => $total_conv)
		);
		break;
}

//
// Get friends for this member
//
$sql = 'SELECT DISTINCT m.user_id, m.username, m.username_base, m.user_color
	FROM _members_friends f, _members m
	WHERE (f.user_id = ? AND f.buddy_id = m.user_id)
		OR (f.buddy_id = ? AND f.user_id = m.user_id)
	ORDER BY m.username';
if ($result = sql_rowset(sql_filter($sql, $user->data['user_id'], $user->data['user_id']))) {
	_style('sdc_friends', array(
		'DC_START' => s_link('my', array('dc', 'start')))
	);
	
	foreach ($result as $row) {
		_style('sdc_friends.item', array(
			'USERNAME' => $row['username'],
			'URL' => s_link('my', array('dc', 'start', $row['username_base'])),
			'USER_COLOR' => $row['user_color'])
		);
	}
}

//
// Output template
//
$page_title = ($mode == 'read') ? $user->lang['DCONV_READ'] : $user->lang['DCONVS'];

$layout_vars = array(
	'L_CONV' => $page_title,
	'S_ACTION' => s_link('my', 'dc'),
	'S_HIDDEN_FIELDS' => s_hidden($s_hidden_fields)
);

page_layout($page_title, 'conversations', $layout_vars);

?>