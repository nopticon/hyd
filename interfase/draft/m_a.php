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
			'MESSAGE_TEXT' => lang('control_a_news_delete') . '<br /><br /><h1>' . $nsdata['topic_title'] . '</h1>',

			'S_CONFIRM_ACTION' => s_link('control'),
			'S_HIDDEN_FIELDS' => s_hidden($s_hidden))
		);

		//
		// Output to template
		//
		page_layout('CONTROL_A_NEWS', 'confirm');
	}
}

?>