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

$user->init(false);

$mode = request_var('mode', '');

switch ($mode) {
	case 'login':
		if ($user->data['is_member'] && !isset($_POST['admin'])) {
			redirect(s_link());
		}
		
		if (isset($_POST['login']) && (!$user->data['is_member'] || isset($_POST['admin']))) {
			$username = phpbb_clean_username(request_var('username', ''));
			$password = request_var('password', '');
			$ref = request_var('ref', '');
			$adm = (isset($_POST['admin'])) ? 1 : 0;
			
			if (!empty($username) && !empty($password)) {
				$sql = 'SELECT user_id, username, user_password, user_type, user_return_unread, user_country, user_avatar, user_location, user_gender, user_birthday
					FROM _members
					WHERE username = ?';
				if ($row = sql_fieldrow(sql_filter($sql, $username))) {
					if ((user_password($password) == $row['user_password']) && ($row['user_type'] != USER_INACTIVE && $row['user_type'] != USER_IGNORE)) {
						$user->session_create($row['user_id'], $adm);
						
						$ref = ($ref == '' || ($row['user_return_unread'] && preg_match('#' . $config['server_name'] . '/$#', $ref))) ? s_link('new') : $ref;
						
						if (!$row['user_country'] || !$row['user_location'] || !$row['user_gender'] || !$row['user_birthday'] || !$row['user_avatar']) {
							$ref = s_link('my', 'profile');
						}
						redirect($ref);
					}
				}
			}
			
			do_login('', $adm);
		}

		do_login();
		
		redirect(s_link());
		break;
	case 'logout':
		if ($user->data['is_member']) {
			$user->session_kill();
		}
		
		if ($user->data['is_founder']) {
			redirect(s_link());
		}
		
		$user->setup();
		
		$ref = s_link();
		$message = $user->lang['LOGOUT_THANKS'] . '<br /><br />' . sprintf($user->lang['CLICK_RETURN_COVER'], '<a href="' . $ref . '">', '</a>');
		
		meta_refresh(15, $ref);
		trigger_error($message);
		break;
}

redirect(s_link());

?>