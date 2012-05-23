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

class __event extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('colab');
	}
	
	public function _home() {
		global $config, $user, $cache, $upload;
		
		$error = w();
		
		if ($this->submit) {
			$filepath = $config['events_path'];
			$filepath_1 = $filepath . 'future/';
			$filepath_2 = $filepath_1 . 'thumbnails/';
			
			$f = $upload->process($filepath_1, 'event_image', 'jpg jpeg');
			
			if (!sizeof($upload->error) && $f !== false) {
				$img = sql_total('_events');
				
				// Create vars
				$event_name = request_var('event_name', '');
				$event_artists = request_var('event_artists', '', true);
				$event_year = request_var('event_year', 0);
				$event_month = request_var('event_month', 0);
				$event_day = request_var('event_day', 0);
				$event_hours = request_var('event_hours', 0);
				$event_minutes = request_var('event_minutes', 0);
				$event_current_topic = request_var('event_current_topic', 0);
				
				$v_date = gmmktime($event_hours, $event_minutes, 0, $event_month, $event_day, $event_year) - $user->timezone - $user->dst;
				
				foreach ($f as $row) {
					$xa = $upload->resize($row, $filepath_1, $filepath_1, $img, array(600, 400), false, false, true);
					if ($xa === false) {
						continue;
					}
					$xb = $upload->resize($row, $filepath_1, $filepath_2, $img, array(100, 75), false, false);
					
					$event_alias = friendly($event_name);
					
					$insert = array(
						'event_alias' => $event_alias,
						'title' => $event_name,
						'archive' => '',
						'date' => (int) $v_date,
						'event_update' => time()
					);
					$sql = 'INSERT INTO _events' . sql_build('INSERT', $insert);
					$event_id = sql_query_nextid($sql);
					
					//
					$artists_ary = explode(nr(), $event_artists);
					foreach ($artists_ary as $row) {
						$subdomain = get_subdomain($row);
						
						$sql = 'SELECT *
							FROM _artists
							WHERE subdomain = ?';
						if ($a_row = sql_fieldrow(sql_filter($sql, $subdomain))) {
							$sql = 'SELECT *
								FROM _artists_events
								WHERE a_artist = ?
									AND a_event = ?';
							if (!sql_fieldrow(sql_filter($sql, $a_row['ub'], $event_id))) {
								$sql_insert = array(
									'a_artist' => $a_row['ub'],
									'a_event' => $event_id
								);
								$sql = 'INSERT INTO _artists_events' . sql_build('INSERT', $sql_insert);
								sql_query($sql);
							}
						}
					}
					
					// Alice: Create topic
					$event_url = $config['events_url'] . 'future/' . $img  . '.jpg';
					
					//$post_message = '<div class="a_mid"> ' . $event_url . ' </div>';
					$post_message = 'Evento publicado';
					$post_time = time();
					$forum_id = 21;
					$poster_id = 1433;
					
					$sql = 'SELECT *
						FROM _forum_topics
						WHERE topic_id = ?';
					if (!$row_current_topic = sql_fieldrow(sql_filter($sql, $event_current_topic))) {
						$insert = array(
							'topic_title' => $event_name,
							'topic_poster' => $poster_id,
							'topic_time' => $post_time,
							'forum_id' => $forum_id,
							'topic_locked' => 0,
							'topic_announce' => 0,
							'topic_important' => 0,
							'topic_vote' => 1,
							'topic_featured' => 1,
							'topic_points' => 1
						);
						$sql = 'INSERT INTO _forum_topics' . sql_build('INSERT', $insert);
						$topic_id = sql_query_nextid($sql);
						
						$event_current_topic = 0;
					} else {
						$topic_id = $event_current_topic;
						
						$post_message .= ' en la secci&oacute;n de eventos';
						
						$sql = 'UPDATE _forum_topics SET topic_title = ?
							WHERE topic_id = ?';
						sql_query(sql_filter($sql, $event_name, $topic_id));
					}
					
					$post_message .= '.';
					
					$insert = array(
						'topic_id' => (int) $topic_id,
						'forum_id' => $forum_id,
						'poster_id' => $poster_id,
						'post_time' => $post_time,
						'poster_ip' => $user->ip,
						'post_text' => $post_message,
						'post_np' => ''
					);
					$sql = 'INSERT INTO _forum_posts' . sql_build('INSERT', $insert);
					$post_id = sql_query_nextid($sql);
					
					$sql = 'UPDATE _events SET event_topic = ?
						WHERE id = ?';
					sql_query(sql_filter($sql, $topic_id, $event_id));
					
					$insert = array(
						'topic_id' => (int) $topic_id,
						'vote_text' => '&iquest;Asistir&aacute;s a ' . $event_name . '?',
						'vote_start' => time(),
						'vote_length' => (int) ($poll_length * 86400)
					);
					$sql = 'INSERT INTO _poll_options' . sql_build('INSERT', $insert);
					$poll_id = sql_query_nextid($sql);
					
					$poll_options = array(1 => 'Si asistir&eacute;');
					
					foreach ($poll_options as $option_id => $option_text) {
						$sql_insert = array(
							'vote_id' => (int) $poll_id,
							'vote_option_id' => (int) $option_id,
							'vote_option_text' => $option_text,
							'vote_result' => 0
						);
						$sql = 'INSERT INTO _poll_results' . sql_build('INSERT', $sql_insert);
						sql_query($sql);
						
						$poll_option_id++;
					}
					
					$sql = 'UPDATE _forums SET forum_posts = forum_posts + 1, forum_last_topic_id = ?' . ((!$event_current_topic) ? ', forum_topics = forum_topics + 1 ' : '') . '
						WHERE forum_id = ?';
					sql_query(sql_filter($sql, $topic_id, $forum_id));
					
					$sql = 'UPDATE _forum_topics SET topic_first_post_id = ?, topic_last_post_id = ?
						WHERE topic_id = ?';
					sql_query(sql_filter($sql, $post_id, $post_id, $topic_id));
					
					$sql = 'UPDATE _members SET user_posts = user_posts + 1
						WHERE user_id = ?';
					sql_query(sql_filter($sql, $poster_id));
					
					// Notify
					$user->save_unread(UH_T, $topic_id);
					
					redirect(s_link('events', $event_alias));
				}
			}

			_style('error', array(
				'MESSAGE' => parse_error($upload->error))
			);
		}
		
		$sql = 'SELECT topic_id, topic_title
			FROM _forum_topics t
			LEFT OUTER JOIN _events e ON t.topic_id = e.event_topic
			WHERE e.event_topic IS NULL
				AND forum_id = 21
			ORDER BY topic_time DESC';
		$topics = sql_rowset($sql);
		
		foreach ($topics as $i => $row) {
			if (!$i) _style('topics');
			
			_style('topics.row', array(
				'TOPIC_ID' => $row['topic_id'],
				'TOPIC_TITLE' => $row['topic_title'])
			);
		}
		
		return;
	}
}

?>