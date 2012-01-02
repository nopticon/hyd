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

/*
U			USERS									ADM
A			ARTISTS								-
E			EVENTS								-
N			NEWS									-
P			POSTS									-
D			DOWNLOADS							-
C			ARTISTS MESSAGES			ADM / USER_MOD / USER_FAN
M			DOWNLOADS MESSAGES		ADM / USER_MOD / USER_FAN
W			WALLPAPERS / ART			-
F			ARTISTS NEWS					-
I			ARTISTS IMAGES				-
*/

define('IN_NUCLEO', true);
require_once('./interfase/common.php');

$user->init();

if (!$user->data['is_member']) {
	if ($user->data['is_bot']) {
		redirect(s_link('cover'));
	}
	do_login();
}

$unread_element = request_var('elem', 0);
$unread_item = request_var('item', 0);

if (isset($_POST['items']) && (isset($_POST['delete']) || isset($_POST['delete_all']))) {
	$items = (is_array($_POST['items']) && !empty($_POST['items'])) ? $_POST['items'] : array();
	
	if (isset($_POST['delete_all'])) {
		foreach ($items as $element => $data) {
			$user->delete_unread($element, $data);
		}
	} else {
		foreach ($items as $element => $void) {
			if (isset($_POST['delete'][$element])) {
				$user->delete_unread($element, $items[$element]);
				break;
			}
		}
	}
	
	redirect(s_link('new'));
} else if (isset($_POST['options'])) {
	$mark_option = (isset($_POST['mark_read_option'])) ? $_POST['mark_read_option'] : $user->data['user_mark_items'];
	$mark_option = intval($mark_option);
	
	if ($user->data['user_mark_items'] != $mark_option) {
		$sql = 'UPDATE _members SET user_mark_items = ?
			WHERE user_id = ?';
		sql_query(sql_filter($sql, $mark_option, $user->data['user_id']));
	}
	
	redirect(s_link('new'));
} else if ($unread_element && $unread_item) {
	$url = '';
	$delete_item = true;
	
	switch ($unread_element) {
		case UH_U:
			$url = '';
			break;
		case UH_A:
			$sql = 'SELECT subdomain
				FROM _artists
				WHERE ub = ?';
			if ($result = sql_field(sql_filter($sql, $unread_item), 'subdomain', '')) {
				$url = s_link('a', $result);
			}
			break;
		case UH_E:
			$url = s_link('events', $unread_item);
			break;
		case UH_N:
			$sql = 'SELECT a.subdomain
				FROM _artists a, _forum_topics t
				WHERE t.topic_id = ?
					AND t.topic_ub = a.ub';
			if ($result = sql_field(sql_filter($sql, $unread_item), 'subdomain', '')) {
				$url = s_link('a', $result);
			}
			break;
		case UH_GN:
			$url = s_link('news', $unread_item);
			break;
		case UH_D:
			$sql = 'SELECT a.subdomain
				FROM _artists a, _dl d
				WHERE d.id = ?
					AND d.ub = a.ub';
			if ($result = sql_field(sql_filter($sql, $unread_item), 'subdomain', '')) {
				$url = s_link('a', array($result, 9, $unread_item));
				$delete_item = false;
			}
			break;
		case UH_C:
			$sql = 'SELECT a.subdomain
				FROM _artists a, _artists_posts p
				WHERE post_id = ?
					AND p.post_ub = a.ub';
			if ($result = sql_field(sql_filter($sql, $unread_item), 'subdomain', '')) {
				$url = s_link('a', array($result, 12, $unread_item));
				$delete_item = false;
			}
			break;
		case UH_FRIEND:
			$sql = 'SELECT username_base
				FROM _members
				WHERE user_id = ?';
			if ($result = sql_field(sql_filter($sql, $unread_item), 'username_base', '')) {
				$url = s_link('m', $result);
			}
			break;
		case UH_UPM:
			$sql = 'SELECT username_base
				FROM _members m, _members_posts p
				WHERE m.user_id = p.userpage_id
					AND p.post_id = ?';
			if ($result = sql_field(sql_filter($sql, $unread_item), 'username_base', '')) {
				$url = s_link('m', array($result, 'messages'));
			}
			break;
	}
	
	if ($url != '') {
		if ($user->data['user_mark_items'] && $delete_item) {
			$user->delete_unread($unread_element, $unread_item);
		}
		
		redirect($url);
	}
	
	redirect(s_link('new'));
	
}

$user->setup();

/*
 * Show unread list
 */
$sql = 'SELECT element
	FROM _members_unread
	WHERE user_id = ?
	GROUP BY element
	ORDER BY element, item';
if ($result = sql_rowset(sql_filter($sql, $user->data['user_id']))) {
	
	require_once(ROOT . 'interfase/downloads.php');
	require_once(ROOT . './interfase/unread.php');
	
	$downloads = new downloads();
	$unread = new unread();
	
	$template->assign_block_vars('items', array());
	
	foreach ($result as $row) {
		switch ($row['element']) {
			case UH_NOTE:
				$unread->conversations();
				break;
			case UH_FRIEND:
				$unread->friends();
				break;
			case UH_UPM:
				$unread->members_posts();
				break;
			case UH_N:
				$unread->artists_news();
				break;
			case UH_GN:
				$unread->site_news();
				break;
			case UH_A:
				$unread->artists();
				break;
			case UH_D:
				$unread->downloads();
				break;
			case UH_T:
				$unread->board();
				break;
			case UH_C:
				$unread->artists_comments();
				break;
			case UH_M:
				$unread->downloads_comments();
				break;
			case UH_AF:
				$unread->artists_fav();
				break;
			case UH_E:
				$unread->events();
				break;
			case UH_U:
				break;
		}
	}
} else {
	$template->assign_block_vars('no_items', array());
}

$template->assign_vars(array(
	'S_UNREAD_ACTION' => s_link('new'))
);

//
// Load sidebar
page_layout('UNREAD_ITEMS', 'unread');

?>