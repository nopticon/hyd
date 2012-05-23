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

class __artist_auth extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('artist');
	}
	
	private function _show($rowset, $unique = false) {
		global $user, $comments;

		$total = count($rowset);

		foreach ($rowset as $i => $row) {
			if (!$i) _style('members');

			$prof = $comments->user_profile($row);

			_style('members.row', array(
				'USER_ID' => $prof['user_id'],
				'PROFILE' => $prof['profile'],
				'USERNAME' => $prof['username'],
				'COLOR' => $prof['user_color'],
				'AVATAR' => $prof['user_avatar'],
				'DELETE' => $unique || ($total > 1 && $prof['user_id'] != $user->d('user_id')) || ($user->is('founder') && $prof['user_id'] != $user->d('user_id')),
				'CHECK' => ($total == 1 && $unique))
			);
		}

		return;
	}
	
	public function _home() {
		global $config, $user, $cache;
		
		$this->_artist();
		
		if (_button()) {
			return $this->create();
		}
		
		if (_button('remove')) {
			return $this->remove();
		}
		
		$sql = 'SELECT u.user_id, u.user_type, u.username, u.username_base, u.user_color, u.user_avatar
			FROM _artists_auth a, _members u
			WHERE a.ub = ?
				AND a.user_id = u.user_id
			ORDER BY u.username';
		if ($result = sql_rowset(sql_filter($sql, $this->object['ub']))) {
			$this->_show($result);
		}

		return;
	}

	private function create() {
		if ($submit) {
			$s_members = request_var('s_members', array(0));
			$s_member = request_var('s_member', '');

			if (sizeof($s_members)) {
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_id IN (??)
					AND user_type NOT IN (??)';
				if ($s_members = sql_rowset(sql_filter($sql, implode(',', $s_members), USER_IGNORE . ((!$user->data['is_founder']) ? ', ' . USER_FOUNDER : '')), false, 'user_id')) {
					$s_members_a = w();
					$s_members_i = w();

					$sql = 'SELECT user_id
						FROM _artists_auth
						WHERE ub = ?';
					$result = sql_rowset(sql_filter($sql, $this->data['ub']));

					foreach ($result as $row) {
						$s_members_a[$row['user_id']] = true;
					}

					foreach ($s_members as $m) {
						if (!isset($s_members_a[$m])) {
							$s_members_i[] = $m;
						}
					}

					if (sizeof($s_members_i)) {
						$sql = 'SELECT user_id, user_color, user_rank
							FROM _members
							WHERE user_id IN (??)';
						$result = sql_rowset(sql_filter($sql, implode(',', $s_members_i)));

						$sd_members = w();
						foreach ($result as $row) {
							$sd_members[$row['user_id']] = $row;
						}

						foreach ($s_members_i as $m) {
							$sql_insert = array(
								'ub' => $this->data['ub'],
								'user_id' => $m
							);
							$sql = 'INSERT INTO _artists_auth' . sql_build('INSERT', $sql_insert);
							sql_query($sql);
						}

						foreach ($sd_members as $user_id => $item) {
							$update = array(
								'user_type' => USER_ARTIST,
								'user_auth_control' => 1
							);

							if ($item['user_color'] == '4D5358') {
								$update['user_color'] = '3DB5C2';
							}

							if (!$item['user_rank']) {
								$update['user_rank'] = (int) $config['default_a_rank'];
							}

							$sql = 'UPDATE _members SET ??
								WHERE user_id = ?
									AND user_type NOT IN (' . USER_INACTIVE . ', ' . USER_IGNORE . ', ' . USER_FOUNDER . ')';
							sql_query(sql_filter($sql, sql_build('UPDATE', $update), $user_id));

							$sql = 'SELECT fan_id
								FROM _artists_fav
								WHERE ub = ?
									AND user_id = ?';
							if ($fan_id = sql_field(sql_filter($sql, $this->data['ub'], $user_id), 'fan_id', 0)) {
								$sql = 'DELETE FROM _artists_fav
									WHERE fan_id = ?';
								sql_query(sql_filter($sql, $fan_id));
							}
						}

						//
						// Back to auth home
						//
						redirect(s_link('acp', array('artist_auth', 'a' => $this->data['subdomain'])));
					}
				}

				$s_member = '';

				_style('no_members', array(
					'MESSAGE' => $user->lang['CONTROL_A_AUTH_ADD_NOMATCH'])
				);
			}

			if (!empty($s_member)) {
				if ($s_member == '*') {
					$s_member = '';
				}

				if (preg_match_all('#\*#', $s_member, $st) > 1) {
					$s_member = str_replace('*', '', $s_member);
				}
			}

			if (!empty($s_member)) {
				$s_member = get_username_base(str_replace('*', '%', $s_member));

				$sql = 'SELECT user_id
					FROM _artists_auth
					WHERE ub = ?';
				$result = sql_rowset(sql_filter($sql, $this->data['ub']));

				$s_auth = array(GUEST);
				foreach ($result as $row) {
					$s_auth[] = $row['user_id'];
				}

				$sql = "SELECT user_id, user_type, username, username_base, user_color, user_avatar
					FROM _members
					WHERE username_base LIKE ?
						AND user_id NOT IN (??)
						AND user_type NOT IN (??)
					ORDER BY username";
				if ($row = sql_rowset(sql_filter($sql, $s_member, implode(',', $s_auth), USER_IGNORE . ((!$user->data['is_founder']) ? ", " . USER_FOUNDER : '')))) {
					if (count($row) < 11) {
						$this->__auth_table($row, true);
						$no_results = false;
					} else {
						_style('no_members', array(
							'MESSAGE' => $user->lang['CONTROL_A_AUTH_ADD_TOOMUCH'])
						);
					}
				} else {
					_style('no_members', array(
						'MESSAGE' => $user->lang['CONTROL_A_AUTH_ADD_NOMATCH'])
					);
				}
			} // IF !EMPTY
		}

		//
		// Output to template
		//
		v_style(array(
			'SHOW_INPUT' => !$submit || $no_results)
		);
		
		return;
	}
	
	private function remove() {
		$auth_url = s_link('acp', array('artist_auth', 'a' => $this->data['subdomain']));

		if (_button('cancel')) {
			redirect($auth_url);
		}

		$submit = _button();
		$confirm = _button('confirm');

		if ($submit || $confirm) {
			global $config, $user;

			$s_members = request_var('s_members', array(0));
			$s_members_i = w();

			if (sizeof($s_members)) {
				$sql = 'SELECT user_id
					FROM _artists_auth
					WHERE ub = ?';
				$result = sql_rowset(sql_filter($sql, $this->data['ub']));

				$s_auth = w();
				foreach ($result as $row) {
					$s_auth[$row['user_id']] = true;
				}

				foreach ($s_members as $m) {
					if (isset($s_auth[$m])) {
						$s_members_i[] = $m;
					}
				}
			}

			if (!sizeof($s_members_i)) {
				redirect($auth_url);
			}

			//
			// Check inputted members
			//
			$sql = 'SELECT user_id, username, user_color, user_rank
				FROM _members
				WHERE user_id IN (??)
					AND user_id <> ?
					AND user_type NOT IN (??)
				ORDER BY user_id';
			if (!$s_members = sql_rowset(sql_filter($sql, implode(',', $s_members_i), $user->data['user_id'], USER_IGNORE))) {
				redirect($auth_url);
			}

			//
			// Confirm
			//
			if ($confirm) {
				foreach ($s_members as $item) {
					$update = w();

					if (!in_array($item['user_id'], array(2, 3))) {
						$sql = 'SELECT COUNT(ub) AS total
							FROM _artists_auth
							WHERE user_id = ?';
						$total = sql_field(sql_filter($sql, $item['user_id']), 'total', 0);
						$keep_control = ($total == 1) ? false : true;

						$user_type = USER_ARTIST;
						if (!$keep_control) {
							$user_type = USER_NORMAL;
							if ($item['user_color'] == '492064') {
								$update['user_color'] = '4D5358';
							}

							if ($item['user_rank'] == $config['default_a_rank']) {
								$update['user_rank'] = 0;
							}

							$sql = 'SELECT *
								FROM _artists_fav
								WHERE user_id = ?';
							if (sql_fieldrow(sql_filter($sql, $item['user_id']))) {
								$user_type = USER_FAN;
							}
						}

						$update['user_auth_control'] = $keep_control;
						$update['user_type'] = $user_type;
					}

					if (sizeof($update)) {
						$sql = 'UPDATE _members SET ??
							WHERE user_id = ?';
						sql_query(sql_filter($sql, sql_build('UPDATE', $update), $item['user_id']));
					}

					$sql = 'DELETE FROM _artists_auth
						WHERE ub = ?
							AND user_id = ?';
					sql_query(sql_filter($sql, $this->data['ub'], $item['user_id']));
				}

				redirect($auth_url);
			}

			//
			// Display confirm dialog
			//
			$s_members_list = '';
			$s_members_hidden = s_hidden(array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage));
			foreach ($s_members as $data) {
				$s_members_list .= (($s_members_list != '') ? ', ' : '') . '<strong style="color:#' . $data['user_color'] . '; font-weight: bold">' . $data['username'] . '</strong>';
				$s_members_hidden .= s_hidden(array('s_members[]' => $data['user_id']));
			}

			$layout_vars = array(
				'MESSAGE_TEXT' => sprintf($user->lang[((sizeof($s_members) == 1) ? 'CONTROL_A_AUTH_DELETE2' : 'CONTROL_A_AUTH_DELETE')], $this->data['name'], $s_members_list),
				'S_CONFIRM_ACTION' => s_link('acp', array('artist_auth', 'a' => $this->data['subdomain'])),
				'S_HIDDEN_FIELDS' => $s_members_hidden
			);

			//
			// Output to template
			//
			page_layout('CONTROL_A_AUTH', 'confirm', $layout_vars);
		}

		redirect($auth_url);
		
		return;
	}
}

?>