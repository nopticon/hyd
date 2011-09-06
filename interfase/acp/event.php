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
if (!defined('IN_NUCLEO')) exit;

_auth('colab_admin');

$i_size = intval(ini_get('upload_max_filesize'));
$i_size *= 1048576;
$error = array();

if ($submit)
{
	require('./interfase/upload.php');
	$upload = new upload();
	
	$filepath = '..' . SDATA . 'events/';
	$filepath_1 = $filepath . 'future/';
	$filepath_2 = $filepath_1 . 'thumbnails/';
	
	$f = $upload->process($filepath_1, $_FILES['add_image'], array('jpg', 'jpeg'), $i_size);
	
	if (!sizeof($upload->error) && $f !== false)
	{
		$sql = 'SELECT MAX(id) AS total
			FROM _events';
		$result = $db->sql_query($sql);
		
		$img = 0;
		if ($row = $db->sql_fetchrow($result))
		{
			$img = $row['total'];
		}
		$db->sql_freeresult($result);
		
		// Create vars
		$e_title = request_var('e_title', '');
		$e_artist = request_var('e_artist', '', true);
		$e_year = request_var('e_year', 0);
		$e_month = request_var('e_month', 0);
		$e_day = request_var('e_day', 0);
		$e_hour = request_var('e_hour', 0);
		$e_mins = request_var('e_mins', 0);
		$v_date = gmmktime($e_hour, $e_mins, 0, $e_month, $e_day, $e_year) - $user->timezone - $user->dst;
		
		foreach ($f as $row)
		{
			$img++;
			
			$xa = $upload->resize($row, $filepath_1, $filepath_1, $img, array(600, 400), false, false, true);
			if ($xa === false)
			{
				continue;
			}
			$xb = $upload->resize($row, $filepath_1, $filepath_2, $img, array(100, 75), false, false);
			
			$insert = array(
				'id' => (int) $img,
				'title' => $e_title,
				'archive' => '',
				'date' => (int) $v_date
			);
			$sql = 'INSERT INTO _events' . $db->sql_build_array('INSERT', $insert);
			$db->sql_query($sql);
			$event_id = $db->sql_nextid();
			
			//
			$ex_artist = explode("\n", $e_artist);
			foreach ($ex_artist as $row)
			{
				$subdomain = get_subdomain($row);
				
				$sql = "SELECT *
					FROM _artists
					WHERE subdomain = '" . $db->sql_escape($subdomain) . "'";
				$result = $db->sql_query($sql);
				
				if ($a_row = $db->sql_fetchrow($result))
				{
					$sql = 'INSERT INTO _artists_events (a_artist, a_event)
						VALUES (' . (int) $a_row['ub'] . ', ' . (int) $event_id . ')';
					$db->sql_query($sql);
				}
				$db->sql_freeresult($result);
			}
			
			// Alice: Create topic
			$post_message = '<div class="a_center"> http://www.rockrepublik.net' . SDATA . 'events/future/' . $img . '.jpg </div>';
			$post_time = time();
			
			$insert = array(
				'topic_title' => $e_title,
				'topic_poster' => 1433,
				'topic_time' => $post_time,
				'forum_id' => 21,
				'topic_locked' => 0,
				'topic_announce' => 0,
				'topic_important' => 0,
				'topic_vote' => 1,
				'topic_featured' => 1,
				'topic_points' => 1
			);
			$db->sql_query('INSERT INTO _forum_topics' . $db->sql_build_array('INSERT', $insert));
			$topic_id = $db->sql_nextid();
			
			$insert = array(
				'topic_id' => (int) $topic_id,
				'forum_id' => 21,
				'poster_id' => 1433,
				'post_time' => $post_time,
				'poster_ip' => $user->ip,
				'post_text' => $post_message,
				'post_np' => ''
			);
			$db->sql_query('INSERT INTO _forum_posts' . $db->sql_build_array('INSERT', $insert));
			$post_id = $db->sql_nextid();
			
			$sql = 'UPDATE _events SET event_topic = ' . (int) $topic_id . '
				WHERE id = ' . (int) $event_id;
			$db->sql_query($sql);
			
			$insert = array(
				'topic_id' => (int) $topic_id,
				'vote_text' => '&iquest;Asistir&aacute;s a ' . $e_title . '?',
				'vote_start' => time(),
				'vote_length' => (int) ($poll_length * 86400)
			);
			$db->sql_query('INSERT INTO _poll_options' . $db->sql_build_array('INSERT', $insert));
			$poll_id = $db->sql_nextid();
			
			$poll_options = array('Si asistir&eacute;', 'No asistir&eacute;');
			
			$poll_option_id = 1;
			foreach ($poll_options as $option)
			{
				$insert_data['POLLRESULTS'][$poll_option_id] = array(
					'vote_id' => (int) $poll_id,
					'vote_option_id' => (int) $poll_option_id,
					'vote_option_text' => $option,
					'vote_result' => 0
				);
				$db->sql_query('INSERT INTO _poll_results' . $db->sql_build_array('INSERT', $insert_data['POLLRESULTS'][$poll_option_id]));
				$poll_option_id++;
			}
			
			$sql = 'UPDATE _forums
				SET forum_posts = forum_posts + 1, forum_last_topic_id = ' . $topic_id . ', forum_topics = forum_topics + 1
				WHERE forum_id = 21';
			$db->sql_query($sql);
			
			$sql = 'UPDATE _forum_topics
				SET topic_first_post_id = ' . $post_id . ', topic_last_post_id = ' . $post_id . '
				WHERE topic_id = ' . $topic_id;
			$db->sql_query($sql);
			
			$sql = 'UPDATE _members
				SET user_posts = user_posts + 1
				WHERE user_id = ' . $user->data['user_id'];
			$db->sql_query($sql);
			
			$sql = "SELECT SUM(forum_topics) AS topic_total, SUM(forum_posts) AS post_total 
				FROM _forums";
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				set_config('max_posts', $row['post_total']);
				set_config('max_topics', $row['topic_total']);
			}
			$db->sql_freeresult($result);
			
			// Notify
			$user->save_unread(UH_T, $topic_id);
		}
		
		redirect(s_link('topic', $topic_id));
	}
	else
	{
		$template->assign_block_vars('error', array(
			'MESSAGE' => parse_error($upload->error))
		);
	}
}

$template_vars = array(
	'S_UPLOAD_ACTION' => $u,
	'MAX_FILESIZE' => $i_size
);

page_layout('EVENTS', 'hidden_ue_body', $template_vars, false);

?>