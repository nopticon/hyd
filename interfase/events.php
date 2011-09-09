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

/*
STRUCT
---
EVENT_ID			INT(11)
EVENT_TITLE		VARCHAR(255)
EVENT_INFO		TEXT
EVENT_ARCHIVE	VARCHAR(50)
EVENT_IMAGE		VARCHAR(50)
EVENT_VIEWS		INT(11)
EVENT_TIME		INT(11)
EVENT_IMAGES	TINYTINT(3)
EVENT_ALLOWD	TINYTINT(1)
EVENT_DIMAGES	INT(11)

EVENT_USER_ID	MEDIUMINT(8)
EVENT_TEXT		TEXT
EVENT_POINTS	VARCHAR(10)
*/

include('./interfase/downloads.php');

class _events extends downloads
{
	var $data = array();
	var $images = array();
	var $timetoday = 0;
	
	function _events($get_timetoday = false)
	{
		if ($get_timetoday)
		{
			global $user;
			
			$current_time = time();
			$minutes = date('is', $current_time);
			$this->timetoday = (int) ($current_time - (60 * intval($minutes[0].$minutes[1])) - intval($minutes[2].$minutes[3])) - (3600 * $user->format_date($current_time, 'H'));
		}
		
		return;
	}
	
	function _setup()
	{
		$event_id = intval(request_var('id', 0));
		if ($event_id > 0) {
			$sql = 'SELECT *
				FROM _events
				WHERE id = ?
					AND UNIX_TIMESTAMP() > date 
				ORDER BY id';
			if ($row = sql_fieldrow(sql_filter($sql, $event_id))) {
				$row['id'] = intval($row['id']);
				$this->data = $row;
				
				return true;
			}
		}
		
		return false;
	}
	
	function _nextevent() {
		global $user, $template;
		
		$nevent = array();
		$sql = 'SELECT *
			FROM _events
			WHERE date >= ?
			ORDER BY date ASC
			LIMIT 2';
		$result = sql_rowset(sql_filter($sql, $this->timetoday));
		
		foreach ($result as $row) {
			$this->filename = SDATA . 'events/future/thumbnails/' . $row['id'] . '.jpg';

			$template->assign_block_vars('next_event', array(
				'URL' => s_link('events') . '#' . $row['id'],
				'TITLE' => $row['title'],
				'IMAGE' => $this->filename)
			); 
		}
		
		return;		
	}	
	
	/*
	function _nextevent()
	{
		global $user, $template;
		
		$sql = 'SELECT *
			FROM _events
			WHERE date >= ?
			ORDER BY date ASC
			LIMIT 1';
		if ($row = sql_rowset(sql_filter($sql, $this->timetoday))) {
			$this->filename = SDATA . 'events/future/thumbnails/' . $row['id'] . '.jpg';
			
			$template->assign_block_vars('next_event', array(
				'URL' => s_link('events') . '#' . $row['id'],
				'TITLE' => $row['title'],
				'IMAGE' => $this->filename)
			);
		}
	}
	*/
	
	function _lastevent($start = 0)
	{
		global $template;
		
		$sql = 'SELECT *
			FROM _events
			WHERE (date < ? OR date > ?)
				AND images > 0
			ORDER BY date DESC
			LIMIT ??, ??';
		if ($row = sql_fieldrow(sql_filter($sql, $this->timetoday, $this->timetoday, $start, 1))) {
			$sql = 'SELECT *
				FROM _events_images
				WHERE event_id = ?
				ORDER BY RAND()';
			$row2 = sql_fieldrow(sql_filter($sql, $row['id']));
			
			$template->assign_block_vars('last_event', array(
				'URL' => s_link('events', $row['id']),
				'TITLE' => $row['title'],
				'IMAGE' => SDATA . 'events/gallery/' . $row['id'] . '/thumbnails/' . $row2['image'] . '.jpg')
			);
		}
		
		return true;
	}
	
	function view()
	{
		global $user, $config, $template;
		
		$mode = request_var('mode', '');
		
		if ($mode == 'save' || $mode == 'view' || $mode == 'fav')
		{
			$download_id = request_var('download_id', 0);
			
			if (!$download_id)
			{
				redirect(s_link('events', $this->data['id']));
			}
			
			if ($mode == 'view') {
				$sql = 'SELECT e.*, COUNT(e2.image) AS prev_images
					FROM _events_images e, _events_images e2
					WHERE e.event_id = ?
						AND e.event_id = e2.event_id 
						AND e.image = ?
						AND e2.image <= ?
					GROUP BY e.image 
					ORDER BY e.image ASC';
				$sql = sql_filter($sql, $this->data['id'], $download_id, $download_id);
			} else {
				$sql = 'SELECT e2.*
					FROM _events_images e2
					LEFT JOIN _events e ON e.id = e2.event_id
					WHERE e2.event_id = ?
						AND e2.image = ?';
				$sql = sql_filter($sql, $this->data['id'], $download_id);
			}
			
			if (!$imagedata = sql_fieldrow($sql)) {
				redirect(s_link('events', $this->data['id']));
			}
		}
		
		switch ($mode)
		{
			case 'save':
				if (!$this->data['allow_download'] || !$imagedata['allow_dl'])
				{
					redirect(s_link('events', array($this->data['id'], $imagedata['image'], 'view')));
				}
				
				$this->filename = $this->data['title'] . '_' . $imagedata['image'] . '.jpg';
				$this->filepath = 'data/events/gallery/' . $this->data['id'] . '/' . $imagedata['image'] . '.jpg';
				
				$sql = 'UPDATE _events_images
					SET downloads = downloads + 1
					WHERE event_id = ?
						AND image = ?';
				sql_query(sql_filter($sql, $this->data['id'], $imagedata['image']));
				
				$this->dl_file();				
				break;
			case 'fav':
				if (!$user->data['is_member']) {
					do_login();
				}
				
				$sql = 'SELECT *
					FROM _events_fav
					WHERE event_id = ?
						AND image_id = ?
						AND member_id = ?';
				if ($row = sql_fieldrow(sql_filter($sql, $this->data['id'], $imagedata['image'], $user->data['user_id']))) {
					$sql = 'UPDATE _events_fav SET fav_date = ?
						WHERE event_id = ?
							AND image_id = ?';
					sql_query(sql_filter($sql, time(), $this->data['id'], $imagedata['image']));
				} else {
					$sql_insert = array(
						'event_id' => (int) $this->data['id'],
						'image_id' => (int) $imagedata['image'],
						'member_id' => (int) $user->data['user_id'],
						'fav_date' => time()
					);
					$sql = 'INSERT INTO _events_fav' . sql_build('INSERT', $sql_insert);
					sql_query($sql);
				}
				redirect(s_link('events', array($this->data['id'], $imagedata['image'], 'view')));
				
				break;
			case 'view':
			default:
				$t_offset = intval(request_var('offset', 0));
				
				if ($mode == 'view')
				{
					$sql = 'UPDATE _events_images
						SET views = views + 1
						WHERE event_id = ?
							AND image = ?';
					sql_query(sql_filter($sql, $this->data['id'], $imagedata['image']));
					
					$template->assign_block_vars('selected', array(
						'IMAGE' => SDATA . 'events/gallery/' . $this->data['id'] . '/' . $imagedata['image'] . '.jpg',
						'WIDTH' => $imagedata['width'], 
						'HEIGHT' => $imagedata['height'],
						'FOOTER' => $imagedata['image_footer'])
					);
					
					if ($user->_team_auth('founder'))
					{
						$template->assign_block_vars('selected.update', array(
							'URL' => s_link('ajax', 'eif'),
							'EID' => $this->data['id'],
							'PID' => $imagedata['image'])
						);
					}
					
					if ($this->data['allow_download'] && $imagedata['allow_dl'])
					{
						$template->assign_block_vars('selected.download', array(
							'URL' => s_link('events', array($this->data['id'], $imagedata['image'], 'save')))
						);
					}
					
					$is_fav = false;
					if ($user->data['is_member'])
					{
						$sql = 'SELECT member_id
							FROM _events_fav
							WHERE event_id = ?
								AND image_id = ?
								AND member_id = ?';
						if (sql_field(sql_filter($sql, $this->data['id'], $imagedata['image'], $user->data['user_id']))) {
							$is_fav = true;
						}
					}
					
					if (!$is_fav || !$user->data['is_member'])
					{
						$template->assign_block_vars('selected.fav', array(
							'URL' => s_link('events', array($this->data['id'], $imagedata['image'], 'fav')))
						);
					}
				}
				else
				{
					if (!$t_offset && $user->data['user_type'] != USER_FOUNDER)
					{
						$sql = 'UPDATE _events SET views = views + 1
							WHERE id = ?';
						sql_query(sql_filter($sql, $this->data['id']));
						
						$this->data['views']++;
					}
				}
				
				//
				// GET THUMBNAILS
				//
				$t_per_page = 12;
				
				if ($mode == 'view' && $download_id)
				{
					$val = 1;
					
					$sql = 'SELECT MAX(image) AS total
						FROM _events_images
						WHERE event_id = ?';
					if ($maximage = sql_field(sql_filter($sql, $this->data['id']), 'total', 0)) {
						$val = ($download_id == $maximage) ? 2 : 1;
					}
					
					$t_offset = floor(($imagedata['prev_images'] - $val) / $t_per_page) * $t_per_page;
				}
				
				if ($this->data['images'])
				{
					$exception_sql = (isset($download_id) && $download_id) ? sql_filter(' AND g.image <> ? ', $download_id) : '';
					
					$sql = 'SELECT g.*
						FROM _events e, _events_images g
						WHERE e.id = ?
							AND e.id = g.event_id ' . 
							$exception_sql . '
						ORDER BY g.image ASC 
						LIMIT ??, ??';
					if ($result = sql_rowset(sql_filter($sql, $this->data['id'], $t_offset, $t_per_image))) {
						build_num_pagination(s_link('events', array($this->data['id'], 's%d')), $this->data['images'], $t_per_page, $t_offset, 'IMG_');
						
						$template->assign_block_vars('thumbnails', array());
						
						foreach ($result as $row) {
							$template->assign_block_vars('thumbnails.item', array(
								'URL' => s_link('events', array($this->data['id'], $row['image'], 'view')),
								'IMAGE' => SDATA . 'events/gallery/' . $this->data['id'] . '/thumbnails/' . $row['image'] . '.jpg',
								'RIMAGE' => SDATA . 'events/gallery/' . $this->data['id'] . '/' . $row['image'] . '.jpg',
								'FOOTER' => $row['image_footer'],
								'WIDTH' => $row['width'], 
								'HEIGHT' => $row['height'])
							);
						}
					} else {
						redirect(s_link('events', $this->data['id']));
					}
				} else {
					$template->assign_block_vars('no_images', array());
				}
				
				// Credits
				$sql = 'SELECT *
					FROM _events_colab c, _members m
					WHERE c.colab_event = ?
						AND c.colab_uid = m.user_id
					ORDER BY m.username';
				$result = sql_rowset(sql_filter($sql, $this->data['id']));
				
				$colabs = array();
				foreach ($result as $row) {
					$colabs[] = '<a href="' . s_link('m', $row['username_base']) . '">' . $row['username'] . '</a>';
				}
				
				if (!empty($this->data['event_colab'])) {
					$colabs[] = $this->data['event_colab'];
				}
				
				$template->assign_vars(array(
					'TITLE' => $this->data['title'],
					'IMAGES' => $this->data['images'],
					'DATE' => $user->format_date($this->data['date'], 'd F Y'),
					'VIEWS' => $this->data['views'],
					'POSTS' => $this->data['posts'],
					'COLAB' => implode(', ', $colabs))
				);
				
				require('./interfase/comments.php');
				$comments = new _comments();
				
				$comments_ref = ($t_offset) ? s_link('events', array($this->data['id'], 's' . $t_offset)) : s_link('events', $this->data['id']);
				
				if ($this->data['posts'])
				{
					$posts_offset = intval(request_var('ps', 0));
					$comments->ref = $comments_ref;
					
					$sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar, m.user_rank, m.user_posts, m.user_gender, m.user_sig
						FROM _events_posts p, _members m
						WHERE p.event_id = ?
							AND p.post_active = 1
							AND p.poster_id = m.user_id
						ORDER BY p.post_time DESC
						LIMIT ??, ??';
					
					$comments->data = array(
						'A_LINKS_CLASS' => 'bold red',
						'SQL' => sql_filter($sql, $this->data['id'], $posts_offset, $config['s_posts'])
					);
					
					$comments->view($posts_offset, 'ps', $this->data['posts'], $config['s_posts'], '', 'MSG_', 'TOPIC_');
				}
				
				//
				// Posting box
				//
				$template->assign_block_vars('posting_box', array());
				
				if ($user->data['is_member']) {
					$template->assign_block_vars('posting_box.box', array(
						'REF' => $comments_ref)
					);
				} else {
					$template->assign_block_vars('posting_box.only_registered', array(
						'LEGEND' => sprintf($user->lang['LOGIN_TO_POST'], '', s_link('my', 'register')))
					);
				}
				
				break;
		}
	}
	
	function home()
	{
		global $config, $template, $user;
		
		$timezone = $config['board_timezone'] * 3600;

		list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
		$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;
		
		$g = getdate($midnight);
		$week = mktime(0, 0, 0, $m, ($d + (7 - ($g['wday'] - 1)) - (!$g['wday'] ? 7 : 0)), $y) - $timezone;
		
		$sql = 'SELECT *
			FROM _events
			ORDER BY date ASC';
		$result = sql_rowset($sql);
		
		foreach ($result as $row) {
			if ($row['date'] >= $midnight && !$row['images']) {
				if ($row['date'] >= $midnight && $row['date'] < $midnight + 86400) {
					$this->data['is_today'][] = $row;
				} else if ($row['date'] >= $midnight + 86400 && $row['date'] < $midnight + (86400 * 2)) {
					$this->data['is_tomorrow'][] = $row;
				} else if ($row['date'] >= $midnight + (86400 * 2) && $row['date'] < $week) {
					$this->data['is_week'][] = $row;
				} else {
					$this->data['is_future'][] = $row;
				}
			} else {
				if ($row['images']) {
					$this->data['is_gallery'][] = $row;
				}
			}
		}
		
		$total_gallery = sizeof($this->data['is_gallery']);
		
		if ($total_gallery) {
			$gallery_offset = request_var('gallery_offset', 0);
			
			$gallery = $this->data['is_gallery'];
			@krsort($gallery);
			
			$gallery = array_slice($gallery, $gallery_offset, 4);
			
			$event_ids = array();
			foreach ($gallery as $item)
			{
				$event_ids[] = $item['id'];
			}
			
			$sql = 'SELECT *
				FROM _events_images
				WHERE event_id IN (??)
				ORDER BY RAND()';
			$result = sql_rowset(sql_filter($sql, implode(',', $event_ids)));
			
			$random_images = array();
			foreach ($result as $row) {
				$random_images[$row['event_id']] = $row['image'];
			}
			
			$template->assign_block_vars('gallery', array(
				'EVENTS' => $total_gallery)
			);
			
			foreach ($gallery as $item)
			{
				$template->assign_block_vars('gallery.item', array(
					'URL' => s_link('events', $item['id']),
					'TITLE' => $item['title'],
					'IMAGE' => SDATA . 'events/gallery/' . $item['id'] . '/thumbnails/' . $random_images[$item['id']] . '.jpg',
					'DATETIME' => $user->format_date($item['date'], $user->lang['DATE_FORMAT'])
				));
			}
			
			build_num_pagination(s_link('events', 'g%d'), $total_gallery, 4, $gallery_offset);
			
			unset($this->data['is_gallery']);
		}
		
		if (sizeof($this->data))
		{
			$template->assign_block_vars('future', array());
			
			foreach ($this->data as $is_date => $data)
			{
				$template->assign_block_vars('future.set', array(
					'L_TITLE' => $user->lang['UE_' . strtoupper($is_date)])
				);
				
				foreach ($data as $item)
				{
					$template->assign_block_vars('future.set.item', array(
						'ITEM_ID' => $item['id'],
						'TITLE' => $item['title'],
						'DATE' => $user->format_date($item['date']),
						'THUMBNAIL' => SDATA . 'events/future/thumbnails/' . $item['id'] . '.jpg',
						'SRC' => SDATA . 'events/future/' . $item['id'] . '.jpg',
						'U_TOPIC' => ($item['event_topic']) ? s_link('topic', $item['event_topic']) : '')
					);
				} // FOREACH
			} // FOREACH
		}
	}
}

?>