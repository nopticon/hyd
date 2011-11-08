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

require_once(ROOT . 'interfase/downloads.php');

class _events extends downloads { 
	public $data = array();
	public $images = array();
	public $timetoday = 0;
	
	public function __construct($get_timetoday = false) {
		if ($get_timetoday) {
			global $user;
			
			$current_time = time();
			$minutes = date('is', $current_time);
			$this->timetoday = (int) ($current_time - (60 * intval($minutes[0].$minutes[1])) - intval($minutes[2].$minutes[3])) - (3600 * $user->format_date($current_time, 'H'));
		}
		
		return;
	}
	
	public function _setup() {
		$event_id = request_var('id', '');
		
		if (!empty($event_id)) {
			$event_field = (!is_numb($event_id)) ? 'event_alias' : 'id';
			
			$sql = 'SELECT *
				FROM _events
				WHERE ?? = ?';
			if ($row = sql_fieldrow(sql_filter($sql, $event_field, $event_id))) {
				$row['id'] = intval($row['id']);
				$this->data = $row;
				
				return true;
			}
		}
		
		return false;
	}
	
	public function _nextevent() {
		global $config, $user, $template;
		
		$nevent = array();
		
		$sql = 'SELECT *
			FROM _events
			WHERE date >= ?
			ORDER BY date ASC
			LIMIT 2';
		$result = sql_rowset(sql_filter($sql, $this->timetoday));
		
		foreach ($result as $row) {
			$filename = $config['events_url'] . 'future/thumbnails/' . $row['id'] . '.jpg';

			$template->assign_block_vars('next_event', array(
				'URL' => s_link('events', $row['event_alias']),
				'TITLE' => $row['title'],
				'DATE' => $user->format_date($row['date'], $user->lang['DATE_FORMAT']),
				'IMAGE' => $filename)
			); 
		}
		
		return;		
	}	
	
	public function _lastevent($start = 0) {
		global $config, $template;
		
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
			
			$filename = $config['events_url'] . 'gallery/' . $row['id'] . '/thumbnails/' . $row2['image'] . '.jpg';
			
			$template->assign_block_vars('last_event', array(
				'URL' => s_link('events', $row['event_alias']),
				'TITLE' => $row['title'],
				'IMAGE' => $filename)
			);
		}
		
		return true;
	}
	
	public function view() {
		global $user, $config, $template;
		
		$mode = request_var('mode', '');
		
		if ($mode == 'view' || $mode == 'fav') {
			$download_id = request_var('download_id', 0);
			
			if (!$download_id) {
				redirect(s_link('events', $this->data['event_alias']));
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
				redirect(s_link('events', $this->data['event_alias']));
			}
		}
		
		switch ($mode) {
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
				redirect(s_link('events', array($this->data['event_alias'], $imagedata['image'], 'view')));
				break;
			case 'view':
			default:
				$t_offset = intval(request_var('offset', 0));
				
				if ($mode == 'view') {
					$sql = 'UPDATE _events_images
						SET views = views + 1
						WHERE event_id = ?
							AND image = ?';
					sql_query(sql_filter($sql, $this->data['id'], $imagedata['image']));
					
					$template->assign_block_vars('selected', array(
						'IMAGE' => $config['assets_url'] . 'events/gallery/' . $this->data['id'] . '/' . $imagedata['image'] . '.jpg',
						'WIDTH' => $imagedata['width'], 
						'HEIGHT' => $imagedata['height'],
						'FOOTER' => $imagedata['image_footer'])
					);
					
					if ($user->_team_auth('founder')) {
						$template->assign_block_vars('selected.update', array(
							'URL' => s_link('ajax', 'eif'),
							'EID' => $this->data['id'],
							'PID' => $imagedata['image'])
						);
					}

					$is_fav = false;
					if ($user->data['is_member']) {
						$sql = 'SELECT member_id
							FROM _events_fav
							WHERE event_id = ?
								AND image_id = ?
								AND member_id = ?';
						if (sql_field(sql_filter($sql, $this->data['id'], $imagedata['image'], $user->data['user_id']), 'member_id', 0)) {
							$is_fav = true;
						}
					}
					
					if (!$is_fav || !$user->data['is_member']) {
						$template->assign_block_vars('selected.fav', array(
							'URL' => s_link('events', array($this->data['id'], $imagedata['image'], 'fav')))
						);
					}
				} else {
					if (!$t_offset && $user->data['user_type'] != USER_FOUNDER) {
						$sql = 'UPDATE _events SET views = views + 1
							WHERE id = ?';
						sql_query(sql_filter($sql, $this->data['id']));
					}
				}
				
				// Get event thumbnails
				$t_per_page = 9;
				
				if ($mode == 'view' && $download_id) {
					$val = 1;
					
					$sql = 'SELECT MAX(image) AS total
						FROM _events_images
						WHERE event_id = ?';
					if ($maximage = sql_field(sql_filter($sql, $this->data['id']), 'total', 0)) {
						$val = ($download_id == $maximage) ? 2 : 1;
					}
					
					$t_offset = floor(($imagedata['prev_images'] - $val) / $t_per_page) * $t_per_page;
				}
				
				if ($this->data['images']) {
					$exception_sql = (isset($download_id) && $download_id) ? sql_filter(' AND g.image <> ? ', $download_id) : '';
					
					$sql = 'SELECT g.*
						FROM _events e, _events_images g
						WHERE e.id = ?
							AND e.id = g.event_id ' . 
							$exception_sql . '
						ORDER BY g.image ASC 
						LIMIT ??, ??';
					if (!$result = sql_rowset(sql_filter($sql, $this->data['id'], $t_offset, $t_per_page))) {
						redirect(s_link('events', $this->data['id']));
					}
					
					build_num_pagination(s_link('events', array($this->data['id'], 's%d')), $this->data['images'], $t_per_page, $t_offset, 'IMG_');
					
					$template->assign_block_vars('thumbnails', array());
					
					foreach ($result as $row) {
						$template->assign_block_vars('thumbnails.item', array(
							'URL' => s_link('events', array($this->data['event_alias'], $row['image'], 'view')),
							'IMAGE' => $config['assets_url'] . 'events/gallery/' . $this->data['id'] . '/thumbnails/' . $row['image'] . '.jpg',
							'RIMAGE' => $config['assets_url'] . 'events/gallery/' . $this->data['id'] . '/' . $row['image'] . '.jpg',
							'FOOTER' => $row['image_footer'],
							'WIDTH' => $row['width'], 
							'HEIGHT' => $row['height'])
						);
					}
					
					// Credits
					$sql = 'SELECT *
						FROM _events_colab c, _members m
						WHERE c.colab_event = ?
							AND c.colab_uid = m.user_id
						ORDER BY m.username';
					if ($result = sql_rowset(sql_filter($sql, $this->data['id']))) {
						$template->assign_block_vars('collab', array());
						
						foreach ($result as $row) {
							$template->assign_block_vars('collab.row', array(
								'PROFILE' => s_link('m', $row['username_base']),
								'USERNAME' => $row['username'])
							);
						}
					}
				} else {
					$template->assign_block_vars('event_flyer', array(
						'IMAGE_SRC' => $config['assets_url'] . 'events/future/' . $this->data['id'] . '.jpg')
					);
				}
				
				list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
				$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;
				
				$event_date = $user->format_date($this->data['date'], 'j F Y');
				
				if ($this->data['date'] >= $midnight) {
					if ($this->data['date'] >= $midnight && $this->data['date'] < $midnight + 86400) {
						$event_date_format = $user->lang['EVENT_TODAY'];
					} else if ($this->data['date'] >= $midnight + 86400 && $this->data['date'] < $midnight + (86400 * 2)) {
						$event_date_format = $user->lang['EVENT_TOMORROW'];
					} else {
						$event_date_format = sprintf($user->lang['EVENT_AFTER'], $event_date);
					}
				} else {
					if ($this->data['date'] >= ($midnight - 86400)) {
						$event_date_format = $user->lang['EVENT_YESTERDAY'];
					} else {
						$event_date_format = sprintf($user->lang['EVENT_BEFORE'], $event_date);
					}
				}
				
				$template->assign_vars(array(
					'EVENT_NAME' => $this->data['title'],
					'EVENT_DATE' => $event_date_format)
				);
				
				require_once(ROOT . 'interfase/comments.php');
				$comments = new _comments();
				
				$posts_offset = request_var('ps', 0);
				$topic_id = $this->data['event_topic'];
				
				$sql = 'SELECT p.*, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar, u.user_posts, u.user_gender, u.user_rank/*, u.user_sig*/
					FROM _forum_posts p, _members u
					WHERE p.topic_id = ?
						AND u.user_id = p.poster_id
						AND p.post_deleted = 0
					ORDER BY p.post_time DESC
					LIMIT ??, ??';
				if (!$messages = sql_rowset(sql_filter($sql, $topic_id, $posts_offset, $config['posts_per_page']))) {
					redirect(s_link('topic', $topic_id));
				}
				
				if (!$posts_offset) {
					//unset($messages[0]);
				}
				
				$i = 0;
				foreach ($messages as $row) {
					if (!$i) {
						$controls = array();
						$user_profile = array();
						$unset_user_profile = array('user_id', 'user_posts', 'user_gender');
						
						$template->assign_block_vars('messages', array());
					}
					
					if ($user->data['is_member']) {
						$controls[$row['post_id']]['reply'] = s_link('post', array($row['post_id'], 'reply')) . '#reply';
						
						if ($mod_auth) {
							$controls[$row['post_id']]['edit'] = s_link('mcp', array('edit', $row['post_id']));
							$controls[$row['post_id']]['delete'] = s_link('mcp', array('post', $row['post_id']));
						}
					}
					
					$user_profile[$row['user_id']] = $comments->user_profile($row, '', $unset_user_profile);	
					
					$data = array(
						'POST_ID' => $row['post_id'],
						'DATETIME' => $user->format_date($row['post_time']),
						'MESSAGE' => $comments->parse_message($row['post_text']),
						'PLAYING' => $row['post_np'],
						'DELETED' => $row['post_deleted'],
						'UNREAD' => 0
					);
					
					foreach ($user_profile[$row['user_id']] as $key => $value) {
						$data[strtoupper($key)] = $value;
					}
					
					$template->assign_block_vars('messages.row', $data);
				
					if (isset($controls[$row['post_id']])) {
						$template->assign_block_vars('messages.row.controls', array());
						
						foreach ($controls[$row['post_id']] as $item => $url) {
							$template->assign_block_vars('messages.row.controls.'.$item, array(
								'URL' => $url)
							);
						}
					}
					
					$i++;
				}
				
				$publish_ref = ($posts_offset) ? s_link('events', array($this->data['event_alias'], 's' . $t_offset)) : s_link('events', $this->data['event_alias']);
				
				// Posting box
				if ($user->data['is_member']) {
					$template->assign_block_vars('publish', array(
						'REF' => $publish_ref)
					);
				}
				
				break;
		}
	}
	
	public function home() {
		global $config, $template, $user;
		
		$timezone = $config['board_timezone'] * 3600;

		list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
		$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;
		
		$g = getdate($midnight);
		$week = mktime(0, 0, 0, $m, ($d + (7 - ($g['wday'] - 1)) - (!$g['wday'] ? 7 : 0)), $y) - $timezone;
		
		$per_page = 6;
		
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
			
			$gallery = array_slice($gallery, $gallery_offset, $per_page);
			
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
					'URL' => s_link('events', $item['event_alias']),
					'TITLE' => $item['title'],
					'IMAGE' => $config['assets_url'] . 'events/gallery/' . $item['id'] . '/thumbnails/' . $random_images[$item['id']] . '.jpg',
					'DATETIME' => $user->format_date($item['date'], $user->lang['DATE_FORMAT']))
				);
			}
			
			build_num_pagination(s_link('events', 'g%d'), $total_gallery, $per_page, $gallery_offset);
			
			unset($this->data['is_gallery']);
		}
		
		if (sizeof($this->data)) {
			$template->assign_block_vars('future', array());
			
			foreach ($this->data as $is_date => $data) {
				$template->assign_block_vars('future.set', array(
					'L_TITLE' => $user->lang['UE_' . strtoupper($is_date)])
				);
				
				foreach ($data as $item) {
					$template->assign_block_vars('future.set.item', array(
						'ITEM_ID' => $item['id'],
						'TITLE' => $item['title'],
						'DATE' => $user->format_date($item['date'], $user->lang['DATE_FORMAT']),
						'THUMBNAIL' => $config['assets_url'] . 'events/future/thumbnails/' . $item['id'] . '.jpg',
						'SRC' => $config['assets_url'] . 'events/future/' . $item['id'] . '.jpg',
						'U_TOPIC' => s_link('events', $item['id']))
					);
				}
			}
		}
	}
}

?>