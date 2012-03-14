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

require_once(ROOT . 'interfase/functions_admin.php');

class a extends common {
	public $methods = array(
		'news' => array('add', 'edit', 'delete'),
		'aposts' => array('edit', 'delete'),
		'auth' => array('add', 'delete'),
		'gallery' => array('add', 'edit', 'delete'),
		'biography' => array('edit'),
		//'lyrics' => array('add', 'edit', 'delete'),
		'stats' => array(),
		'video' => array('add', 'delete'),
		//'downloads' => array('add', 'edit', 'delete'),
		//'dposts' => array('edit', 'delete')
	);

	public function home() {
		global $config, $user;

		if (!$this->setup()) {
			$sql_where = '';
			if ($user->data['user_type'] == USER_ARTIST) {
				$sql = 'SELECT a.ub
					FROM _artists_auth au, _artists a, _members m
					WHERE au.ub = a.ub
						AND au.user_id = m.user_id
						AND m.user_id = ?
						AND m.user_type = ?
					ORDER BY m.username';
				if ($mod_ary = sql_rowset(sql_filter($sql, $user->data['user_id'], USER_ARTIST), false, 'ub')) {
					$sql_where = sql_filter('WHERE ub IN (??)', implode(',', array_map('intval', $mod_ary)));
				}
			}

			$sql = 'SELECT *
				FROM _artists
				' . $sql_where . '
				ORDER BY name';
			if ($selected_artists = sql_rowset($sql, 'ub')) {
				//
				// Get artists images
				$sql = 'SELECT *
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

				_style('select_a');

				$tcol = 0;
				foreach ($selected_artists as $ub => $data) {
					if (!$tcol) {
						_style('select_a.row');
					}
					
					$image = ($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif';

					_style('select_a.row.col', array(
						'NAME' => $data['name'],
						'IMAGE' => $config['artists_url'] . $image,
						'URL' => s_link_control('a', array('a' => $data['subdomain'])),
						'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
						'GENRE' => $data['genre'])
					);

					$tcol = ($tcol == 4) ? 0 : $tcol + 1;
				}
			}
		}

		return;
	}
	
	public function _news_edit() {
		global $user, $configm, $comments;

		$submit = _button();
		$id = $this->control->get_var('id', 0);
		if (!$id) {
			fatal_error();
		}

		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?
				AND forum_id = ?
				AND topic_ub = ?';
		if (!$nsdata = sql_fieldrow(sql_filter($sql, $id, $config['ub_fans_f'], $this->data['ub']))) {
			fatal_error();
		}

		$sql = 'SELECT *
			FROM _forum_posts
			WHERE post_id = ?
				AND topic_id = ?
				AND forum_id = ?';
		if (!$nsdata2 = sql_fieldrow(sql_filter($sql, $nsdata['topic_first_post_id'], $nsdata['topic_id'], $nsdata['forum_id']))) {
			fatal_error();
		}

		$post_title = preg_replace('#(.*?): (.*?)#', '\\2', $nsdata['topic_title']);
		$message = $nsdata2['post_text'];

		if ($submit) {
			$post_title = $this->control->get_var('title', '');
			$message = $this->control->get_var('message', '', true);
			$current_time = time();
			$error = array();

			// Check subject
			if (empty($post_title)) {
				$error[] = 'EMPTY_SUBJECT';
			}

			// Check message
			if (empty($message)) {
				$error[] = 'EMPTY_MESSAGE';
			}

			if (!sizeof($error)) {
				$message = $comments->prepare($message);
				if ($message != $nsdata2['post_text']) {
					$update_data = array(
						'TOPIC' => array(
							'topic_title' => $this->data['name'] . ': ' . $post_title,
							'topic_time' => (int) $current_time
						),
						'POST' => array(
							'post_time' => (int) $current_time,
							'poster_ip' => $user->ip,
							'post_text' => $message
						)
					);

					$sql = 'UPDATE _forum_topics SET ??
						WHERE topic_id = ?';
					sql_query(sql_filter($sql, sql_build('UPDATE', $update_data['TOPIC']), $nsdata['topic_id']));

					$sql = 'UPDATE _forum_posts SET ??
						WHERE post_id = ?';
					sql_query(sql_filter($sql, sql_build('UPDATE', $update_data['POST']), $nsdata['topic_first_post_id']));

					$user->save_unread(UH_N, $nsdata['topic_id']);
				}

				redirect(s_link('a', $this->data['subdomain']));
			}

			if (sizeof($error)) {
				_style('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}

		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $nsdata['topic_id']), 'A_NEWS_EDIT');

		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $nsdata['topic_id']);

		v_style(array(
			'TOPIC_TITLE' => $post_title,
			'MESSAGE' => $message,
			'S_HIDDEN' => s_hidden($s_hidden))
		);

		return;
	}

	public function _news_delete() {
		if (isset($_POST['cancel'])) {
			redirect(s_link('a', $this->data['subdomain']));
		}

		global $config, $user;

		$id = $this->control->get_var('id', 0);

		if (!$id) {
			fatal_error();
		}

		$sql = 'SELECT *
			FROM _forum_topics
			WHERE topic_id = ?
				AND forum_id = ?
				AND topic_ub = ?';
		if (!$nsdata = sql_fieldrow(sql_filter($sql, $id, $config['ub_fans_f'], $this->data['ub']))) {
			fatal_error();
		}

		if (isset($_POST['confirm'])) {
			$sql_a = array();

			$sql = 'SELECT poster_id, COUNT(post_id) AS posts
				FROM _forum_posts
				WHERE topic_id = ?
				GROUP BY poster_id';
			$result = sql_rowset(sql_filter($sql, $id));

			foreach ($result as $row) {
				$sql = 'UPDATE _members SET user_posts = user_posts - ??
					WHERE user_id = ?';
				$sql_a[] = sql_filter($sql, $row['posts'], $row['poster_id']);
			}

			$sql_a[] = sql_filter('DELETE FROM _forum_topics WHERE topic_id = ?', $id);
			$sql_a[] = sql_filter('DELETE FROM _forum_posts WHERE topic_id = ?', $id);
			$sql_a[] = sql_filter('DELETE FROM _forum_topics_fav WHERE topic_id = ?', $id);
			$sql_a[] = sql_filter('UPDATE _artists SET news = news - 1 WHERE ub = ?', $this->data['ub']);

			sql_query($sql_a);

			sync('forum', $config['ub_fans_f']);
			$user->delete_all_unread(UH_N, $id);

			redirect(s_link('a', $this->data['subdomain']));
		}

		//
		// Show confirm dialog
		//
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $nsdata['topic_id']);

		v_style(array(
			'MESSAGE_TEXT' => $user->lang['CONTROL_A_NEWS_DELETE'] . '<br /><br /><h1>' . $nsdata['topic_title'] . '</h1>',

			'S_CONFIRM_ACTION' => s_link('control'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden))
		);

		//
		// Output to template
		//
		page_layout('CONTROL_A_NEWS', 'confirm');
	}

	public function __auth_table($row, $check_unique = false) {
		global $user;

		$comments = new _comments();

		$tcol = $trow = $items = 0;
		$total = count($row);

		_style('members');

		foreach ($row as $_row) {
			if (!$tcol) {
				_style('members.row');
				$trow++;
			}

			$auth_profile = $comments->user_profile($row);

			_style('members.row.col', array(
				'USER_ID' => $auth_profile['user_id'],
				'PROFILE' => $auth_profile['profile'],
				'USERNAME' => $auth_profile['username'],
				'COLOR' => $auth_profile['user_color'],
				'AVATAR' => $auth_profile['user_avatar'],
				'DELETE' => $check_unique || ($total > 1 && $auth_profile['user_id'] != $user->data['user_id']) || ($user->data['is_founder'] && $auth_profile['user_id'] != $user->data['user_id']),
				'CHECK' => ($total == 1 && $check_unique))
			);

			$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			$items++;
		}

		if ($trow > 1) {
			for ($i = 0, $end = ((4 * $trow) - $items); $i < $end; $i++) {
				_style('members.row.blank');
			}
		}

		return;
	}

	public function _auth_home() {
		global $user;

		$results = false;

		$sql = 'SELECT u.user_id, u.user_type, u.username, u.username_base, u.user_color, u.user_avatar
			FROM _artists_auth a, _members u
			WHERE a.ub = ?
				AND a.user_id = u.user_id
			ORDER BY u.username';
		if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
			$results = true;
			$this->__auth_table($result);
		} else {
			_style('no_members', array(
				'MESSAGE' => $user->lang['CONTROL_A_AUTH_NOMEMBERS'])
			);
		}

		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'delete');

		v_style(array(
			'S_HIDDEN' => s_hidden($s_hidden),
			'RESULTS' => $results,
			'ADD_MEMBER_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
	}

	public function _auth_add() {
		global $config, $user;

		$submit = isset($_POST['submit']) ? true : false;
		$no_results = true;

		if ($submit) {
			$s_members = $this->control->get_var('s_members', array(0));
			$s_member = $this->control->get_var('s_member', '');

			if (sizeof($s_members)) {
				$sql = 'SELECT user_id
					FROM _members
					WHERE user_id IN (??)
					AND user_type NOT IN (??)';
				if ($s_members = sql_rowset(sql_filter($sql, implode(',', $s_members), USER_IGNORE . ((!$user->data['is_founder']) ? ', ' . USER_FOUNDER : '')), false, 'user_id')) {
					$s_members_a = array();
					$s_members_i = array();

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

						$sd_members = array();
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
						redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
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
				$s_member = phpbb_clean_username(str_replace('*', '%', $s_member));

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
					WHERE username LIKE ?
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

		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage);

		if ($submit && !$no_results) {
			$s_hidden['s_member'] = str_replace('%', '*', $s_member);
		}

		//
		// Output to template
		//
		v_style(array(
			'SHOW_INPUT' => !$submit || $no_results,
			'S_HIDDEN' => s_hidden($s_hidden),
			'ADD_MEMBER_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
	}

	public function _auth_delete() {
		$auth_url = s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode));

		if (isset($_POST['cancel'])) {
			redirect($auth_url);
		}

		$submit = isset($_POST['submit']) ? true : false;
		$confirm = isset($_POST['confirm']) ? true : false;

		if ($submit || $confirm) {
			global $config, $user;

			$s_members = $this->control->get_var('s_members', array(0));
			$s_members_i = array();

			if (sizeof($s_members)) {
				$sql = 'SELECT user_id
					FROM _artists_auth
					WHERE ub = ?';
				$result = sql_rowset(sql_filter($sql, $this->data['ub']));

				$s_auth = array();
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
					$update = array();

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
				'S_CONFIRM_ACTION' => s_link('control'),
				'S_HIDDEN_FIELDS' => $s_members_hidden
			);

			//
			// Output to template
			//
			page_layout('CONTROL_A_AUTH', 'confirm', $layout_vars);
		}

		redirect($auth_url);
	}

	public function _gallery_home() {
		global $user;

		$sql = 'SELECT g.*
			FROM _artists a, _artists_images g
			WHERE a.ub = ?
				AND a.ub = g.ub
			ORDER BY image ASC';
		if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
			_style('gallery');

			$tcol = 0;
			foreach ($result as $row) {
				if (!$tcol) _style('gallery.row');

				_style('gallery.row.col', array(
					'ITEM' => $row['image'],
					'URL' => s_link('a', array($this->data['subdomain'], 4, $row['image'], 'view')),
					'U_FOOTER' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'footer', 'image' => $row['image'])),
					'IMAGE' => SDATA . 'artists/' . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
					'RIMAGE' => get_a_imagepath(SDATA . 'artists/' . $this->data['ub'], $row['image'] . '.jpg', array('x1', 'gallery')),
					'WIDTH' => $row['width'],
					'HEIGHT' => $row['height'],
					'TFOOTER' => $row['image_footer'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'])
				);

				$tcol = ($tcol == 3) ? 0 : $tcol + 1;
			}
		} else {
			_style('empty', array(
				'MESSAGE' => $user->lang['CONTROL_A_GALLERY_EMPTY'])
			);
		}

		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'delete');

		v_style(array(
			'S_HIDDEN' => s_hidden($s_hidden),
			'ADD_IMAGE_URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'add')))
		);
	}

	public function _gallery_add() {
		global $user;

		if (isset($_POST['submit']) && isset($_FILES['add_image'])) {
			$filepath = '..' . SDATA . 'artists/' . $this->data['ub'] . '/';
			$filepath_1 = $filepath . 'x1/';
			$filepath_2 = $filepath . 'gallery/';
			$filepath_3 = $filepath . 'thumbnails/';

			$f = $upload->process($filepath_1, $_FILES['add_image'], array('jpg'));

			if (!sizeof($upload->error) && $f !== false) {
				$sql = 'SELECT MAX(image) AS total
					FROM _artists_images
					WHERE ub = ?';
				$img = sql_field(sql_filter($sql, $this->data['ub']), 'total', 0);

				$a = 0;
				foreach ($f as $row) {
					$img++;

					$xa = $upload->resize($row, $filepath_1, $filepath_1, $img, array(600, 400), false, false, true);
					if ($xa === false) {
						continue;
					}

					$xb = $upload->resize($row, $filepath_1, $filepath_2, $img, array(300, 225), false, false);
					$xc = $upload->resize($row, $filepath_2, $filepath_3, $img, array(100, 75), false, false);

					$insert = array(
						'ub' => (int) $this->data['ub'],
						'image' => (int) $img,
						'width' => $xa['width'],
						'height' => $xa['height']
					);
					$sql = 'INSERT INTO _artists_images' . sql_build('INSERT', $insert);
					sql_query($sql);

					$a++;
				}

				if ($a) {
					$sql = 'UPDATE _artists SET images = images + ??
						WHERE ub = ?';
					sql_query(sql_filter($sql, $a, $this->data['ub']));
				}

				redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
			} else {
				_style('error', array(
					'MESSAGE' => parse_error($upload->error))
				);
			}
		}

		v_style(array(
			'S_HIDDEN' => s_hidden(array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage)),
			'MAX_FILESIZE' => $filesize)
		);

		return;
	}

	public function _gallery_delete() {
		global $user;

		$error = false;
		if (isset($_POST['submit'])) {
			$s_images = $this->control->get_var('ls_images', array(0));
			if (sizeof($s_images)) {
				$affected = array();

				$common_path = './..' . SDATA . 'artists/' . $this->data['ub'] . '/';
				$path = array(
					$common_path . 'x1/',
					$common_path . 'gallery/',
					$common_path . 'thumbnails/',
				);

				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub = ?
						AND image IN (??)
					ORDER BY image';
				$result = sql_rowset(sql_filter($sql, $this->data['ub'], implode(',', $s_images)));

				foreach ($result as $row) {
					foreach ($path as $path_row) {
						$filepath = $path_row . $row['image'] . '.jpg';
						_rm($filepath);
					}
					$affected[] = $row['image'];
				}

				if (count($affected)) {
					$sql = 'DELETE FROM _artists_images
						WHERE ub = ?
							AND image IN (??)';
					sql_query(sql_filter($sql, $this->data['ub'], implode(',', $affected)));

					$sql = 'UPDATE _artists SET images = images - ??
						WHERE ub = ?';
					sql_query(sql_filter($sql, sql_affectedrows(), $this->data['ub']));
				}
			}
		}

		redirect(s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode)));
	}

	public function _gallery_footer() {
		global $user;

		$a = $this->control->get_var('image', '');
		$t = $this->control->get_var('value', '');

		$sql = 'SELECT *
			FROM _artists_images
			WHERE ub = ?
				AND image = ?';
		if (!$row = sql_fieldrow(sql_filter($sql, $this->data['ub'], $a))) {
			fatal_error();
		}

		$sql = 'UPDATE _artists_images SET image_footer = ?
			WHERE ub = ?
				AND image = ?';
		sql_query(sql_filter($sql, $t, $this->data['ub'], $a));

		$this->e($t);
	}

	//
	// Stats
	//
	public function stats() {
		if (!$this->setup()) {
			fatal_error();
		}

		$this->nav();
		$this->call_method();
	}

	public function _stats_home() {
		global $user;

		$sql = 'SELECT *, SUM(members + guests) AS total
			FROM _artists_stats
			WHERE ub = ?
			GROUP BY date
			ORDER BY date DESC';
		$stats = sql_rowset(sql_filter($sql, $this->data['ub']), 'date');

		$years_sum = array();
		$years_temp = array();
		$years = array();

		foreach ($stats as $date => $void) {
			$year = substr($date, 0, 4);

			if (!isset($years_temp[$year])) {
				$years[] = $year;
				$years_temp[$year] = true;
			}

			if (!isset($years_sum[$year])) {
				$years_sum[$year] = 0;
			}

			$years_sum[$year] += $void['total'];
		}
		unset($years_temp);

		if (sizeof($years)) {
			rsort($years);
		} else {
			$years[] = date('Y');
		}

		$total_graph = 0;
		foreach ($years as $year) {
			_style('year', array(
				'YEAR' => $year)
			);

			if (!isset($years_sum[$year])) {
				$years_sum[$year] = 0;
			}

			for ($i = 1; $i < 13; $i++) {
				$month = (($i < 10) ? '0' : '') . $i;
				$monthdata = (isset($stats[$year . $month])) ? $stats[$year . $month] : array();
				$monthdata['total'] = isset($monthdata['total']) ? $monthdata['total'] : 0;
				$monthdata['percent'] = ($years_sum[$year] > 0) ? $monthdata['total'] / $years_sum[$year] : 0;
				$monthdata['members'] = isset($monthdata['members']) ? $monthdata['members'] : 0;
				$monthdata['guests'] = isset($monthdata['guests']) ? $monthdata['guests'] : 0;
				$monthdata['unix'] = gmmktime(0, 0, 0, $i, 1, $year) - $user->timezone - $user->dst;
				$total_graph += $monthdata['total'];

				_style('year.month', array(
					'NAME' => $user->format_date($monthdata['unix'], 'F'),
					'TOTAL' => $monthdata['total'],
					'MEMBERS' => $monthdata['members'],
					'GUESTS' => $monthdata['guests'],
					'PERCENT' => sprintf("%.1d", ($monthdata['percent'] * 100)))
				);
			}
		}

		v_style(array(
			'BEFORE_VIEWS' => number_format($this->data['views']),
			'SHOW_VIEWS_LEGEND' => ($this->data['views'] > $total_graph))
		);

		return;
	}

	//
	// Downloads
	//
	public function downloads() {
		if (!$this->setup()) {
			fatal_error();
		}

		$this->nav();
		$this->call_method();
	}

	public function _downloads_home() {
		global $user;

		$sql = 'SELECT *
			FROM _dl
			WHERE ub = ?
			ORDER BY title';
		if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
			$downloads_type = array(
				1 => '/net/icons/browse.gif',
				2 => '/net/icons/store.gif'
			);

			_style('downloads');
			$tcol = 0;

			foreach ($result as $row) {
				if (!$tcol) {
					_style('downloads.row');
				}

				_style('downloads.row.col', array(
					'ITEM' => $row['id'],
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => 'edit', 'd' => $row['id'])),
					'POSTS_URL' => s_link('a', array($this->data['subdomain'], 9, $row['id'])) . '#dpf',
					'IMAGE_TYPE' => $downloads_type[$row['ud']],
					'DOWNLOAD_TITLE' => $row['title'],
					'VIEWS' => $row['views'],
					'DOWNLOADS' => $row['downloads'],
					'POSTS' => $row['posts'])
				);

				$tcol = ($tcol == 2) ? 0 : $tcol + 1;
			}
		} else {
			_style('empty', array(
				'MESSAGE' => $user->lang['CONTROL_A_DOWNLOADS_EMPTY'])
			);
		}

		return;
	}

	//
	// D Posts
	//
	public function dposts() {
		if (!$this->setup()) {
			fatal_error();
		}

		if ($this->manage == 'home') {
			redirect(s_link('a', $this->data['subdomain']));
		}

		$this->nav();
		$this->call_method();
	}

	public function _dposts_edit() {
		global $user, $config;

		$submit = isset($_POST['submit']) ? true : false;

		$id = $this->control->get_var('id', 0);

		if (!$id) {
			fatal_error();
		}

		$sql = 'SELECT a.ub, d.*, p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists a, _dl d, _dl_posts p, _members m
			WHERE a.ub = ?
				AND a.ub = d.ub
				AND d.id = p.download_id
				AND p.post_id = ' . (int) $id . '
				AND p.poster_id = m.user_id';
		if (!$pdata = sql_fieldrow(sql_filter($sql, $this->data['ub']))) {
			fatal_error();
		}

		$message = $pdata['post_text'];

		if ($submit) {
			$message = $this->control->get_var('message', '', true);
			$error = array();

			// Check message
			if (empty($message)) {
				$error[] = 'EMPTY_MESSAGE';
			}

			if (!sizeof($error)) {
				$message = $comments->prepare($message);

				$sql = 'UPDATE _dl_posts SET post_text = ?
					WHERE post_id = ?';
				sql_query(sql_filter($sql, $message, $pdata['post_id']));

				redirect(s_link('a', array($this->data['subdomain'], 9, $pdata['download_id'])));
			}

			if (sizeof($error)) {
				_style('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}

		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']), 'A_NEWS_EDIT');
		$this->control->set_nav(array('a' => $this->data['subdomain'], 'mode' => 'downloads', 'manage' => $this->manage, 'id' => $pdata['download_id']), $pdata['title']);

		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']);

		v_style(array(
			'P_MEMBER' => ($pdata['user_id'] != GUEST) ? $pdata['username'] : (($pdata['post_username'] != '') ? $pdata['post_username'] : $user->lang['GUEST']),
			'MESSAGE' => $message,
			'S_HIDDEN' => s_hidden($s_hidden))
		);

		return;
	}

	public function _dposts_delete() {
		global $config, $user;

		$id = $this->control->get_var('id', 0);

		if (!$id) {
			fatal_error();
		}

		$sql = 'SELECT a.ub, d.*, p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists a, _dl d, _dl_posts p, _members m
			WHERE a.ub = ?
				AND a.ub = d.ub
				AND d.id = p.download_id
				AND p.post_id = ?
				AND p.poster_id = m.user_id';
		if (!$pdata = sql_fieldrow(sql_filter($sql, $this->data['ub'], $id))) {
			fatal_error();
		}

		if (isset($_POST['cancel'])) {
			redirect(s_link('a', array($this->data['subdomain'], 9, $pdata['download_id'])));
		}

		if (isset($_POST['confirm'])) {
			$delete_forever = (isset($_POST['delete_forever'])) ? true : false;
			$delete_forever = ($user->data['is_founder'] && $delete_forever) ? true : false;

			if ($delete_forever) {
				$sql = 'DELETE FROM _dl_posts
					WHERE post_id = ?';
				sql_query(sql_filter($sql, $id));
			} else {
				$sql = 'UPDATE _dl_posts SET post_active = 0
					WHERE post_id = ?';
				sql_query(sql_filter($sql, $id));

				// TODO: LOG THIS ACTION: $this->control->log
			}

			$sql = 'UPDATE _dl SET posts = posts - 1
				WHERE id = ?';
			sql_query(sql_filter($sql, $pdata['download_id']));

			$user->delete_all_unread(UH_M, $id);

			redirect(s_link('a', array($this->data['subdomain'], 9, $pdata['download_id'])));
		}

		//
		// Show confirm dialog
		//
		$s_hidden = array('module' => $this->control->module, 'a' => $this->data['subdomain'], 'mode' => $this->mode, 'manage' => $this->manage, 'id' => $pdata['post_id']);

		//
		// Output to template
		//
		$layout_vars = array(
			'MESSAGE_TEXT' => $user->lang['CONTROL_A_APOSTS_DELETE'],

			'S_CONFIRM_ACTION' => s_link('control'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden),
			'DELETE_FOREVER' => $user->data['is_founder']
		);
		page_layout('CONTROL_A_APOSTS', 'confirm', $layout_vars);
	}
}

?>