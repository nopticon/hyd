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
require_once(ROOT . 'interfase/emailer.php');

class layout extends downloads {
	//
	// Home
	//
	public function _1() {
		global $user, $config;
		
		//
		// Gallery
		//
		if ($this->data['images']) {
			$simage = $this->get_images(false, $this->data['ub'], true);
			$imagedata = $this->images[$this->data['ub']][$simage];
			$image = $imagedata['path'];
		} else {
			if (!$this->data['um'] && !$this->data['uv'] && $this->data['bio'] == '') {
				$default_image = 'default.jpg';
			} else {
				$default_image = 'default2.jpg';
			}
			
			$image = 'default/' . $default_image;
		}
		
		_style('ub_image', array(
			'IMAGE' => $image)
		);
		
		if ($this->data['images'] > 1) {
			_style('ub_image.view', array(
				'URL' => s_link('a', array($this->data['subdomain'], 4, $imagedata['image'], 'view')))
			);
		}
		
		//
		// News
		//
		if ($this->auth['user']) {
			_style('publish', array(
				'TITLE' => ($this->auth['mod']) ? $user->lang['SEND_NEWS'] : $user->lang['SEND_POST'],
				'URL' => s_link('a', array($this->data['subdomain'])))
			);
		}
		
		if ($this->data['news'] || $this->data['posts']) {
			$sql = "(SELECT ?, p.topic_id, p.post_text, t.topic_time as post_time, m.user_id, m.username, m.username_base, m.user_avatar
				FROM _forum_topics t, _forum_posts p, _members m
				WHERE t.forum_id = ?
					AND t.topic_ub = ?
					AND t.topic_poster = m.user_id
					AND p.post_id = t.topic_first_post_id
					AND t.topic_important = 0
				ORDER BY t.topic_time DESC)
					UNION ALL
				(SELECT ?, p.post_id, p.post_text, p.post_time, m.user_id, m.username, m.username_base, m.user_avatar
				FROM _artists a, _artists_posts p, _members m
				WHERE p.post_ub = ? 
					AND p.post_ub = a.ub
					AND p.post_active = 1 
					AND p.poster_id = m.user_id 
				ORDER BY p.post_time DESC 
				LIMIT ??, ??)
				ORDER BY post_time DESC";
			if ($result = sql_rowset(sql_filter($sql, 'news', $config['ub_fans_f'], $this->data['ub'], 'post', $this->data['ub'], 0, 10))) {
				_style('news');
				
				$user_profile = array();
				
				foreach ($result as $row) {
					$uid = $row['user_id'];
					
					if (!isset($user_profile[$uid]) || ($uid == GUEST)) {
						$user_profile[$uid] = $this->msg->user_profile($row);
					}
					
					$row_data = array(
						'POST_ID' => $row['post_id'],
						'DATETIME' => $user->format_date($row['post_time']),
						'MESSAGE' => $this->msg->parse_message($row['post_text']),
						'S_DELETE' => false
					);
					
					foreach ($user_profile[$uid] as $key => $value) {
						$row_data[strtoupper($key)] = $value;
					}
					
					_style('news.row', $row_data);
				}
			}
			
			$total_rows = $this->data['news'] + $this->data['posts']; 
		}
		
		//
		// Auth Members
		//
		if ($this->auth['mod'] || $this->data['mods_legend']) {
			$sql = 'SELECT b.ub, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar, u.user_avatar_type
				FROM _artists_auth a, _artists b, _members u
				WHERE a.ub = ?
					AND a.ub = b.ub 
					AND a.user_id = u.user_id 
				ORDER BY u.username';
			if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
				foreach ($result as $i => $row) {
					if (!$i) {
						_style('mods');
					}
					
					$user_profile = $this->msg->user_profile($row);
					
					_style('mods.item', array(
						'PROFILE' => $user_profile['profile'],
						'USERNAME' => $user_profile['username'],
						'COLOR' => $user_profile['user_color'])
					);
				}
				
				$this->msg->reset();
				
				if ($this->auth['mod']) {
					_style('mods.manage', array(
						'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'auth')))
					);
				}
			}
		}
		
		return;
	}
	
	//
	// Biography
	//
	public function _2() {
		global $config;
		
		if ($this->data['featured_image']) {
			_style('featured_image', array(
				'IMAGE' => $config['artists_url'] . $this->data['ub'] . '/gallery/' . $this->data['featured_image'] . '.jpg',
				'URL' => s_link('a', array($this->data['subdomain'], 4, $this->data['featured_image'], 'view')))
			);
		}
		
		//
		// Parse Biography
		//		
		v_style(array(
			'UB_BIO' => $this->msg->parse_message($this->data['bio']))
		);
		
		return;
	}
	
	public function _3() {
		return;
	}
	
	//
	// Gallery
	//
	public function _4() {
		global $config;
		
		$mode = request_var('mode', '');
		$download_id = intval(request_var('download_id', 0));
		
		if ($mode == 'view') {
			if (!$download_id) {
				redirect(s_link('a', array($this->data['subdomain'], 4)));
			}
			
			if ($mode == 'view') {
				$sql = 'SELECT g.*, COUNT(g2.image) AS prev_images
					FROM _artists_images g, _artists_images g2
					WHERE g.ub = ?
						AND g2.ub = g.ub 
						AND g.image = ?
						AND g2.image <= ?
					GROUP BY g.image 
					ORDER BY g.image ASC';
				$sql = sql_filter($sql, $this->data['ub'], $download_id, $download_id);
			} else {
				$sql = 'SELECT g.*
					FROM _artists a, _artists_images g
					WHERE a.ub = ?
						AND a.ub = g.ub 
						AND g.image = ?';
				$sql = sql_filter($sql, $this->data['ub'], $download_id);
			}
			
			if (!$imagedata = sql_fieldrow($sql)) {
				redirect(s_link('a', array($this->data['subdomain'], 4)));
			}
		}
		
		switch ($mode)
		{
			case 'view':
			default:
				if ($mode == 'view') {
					if (!$this->auth['mod']) {
						$sql = 'UPDATE _artists_images SET views = views + 1
							WHERE ub = ?
								AND image = ?';
						sql_query(sql_filter($sql, $this->data['ub'], $imagedata['image']));
					}
					
					_style('selected', array(
						'IMAGE' => $config['artists_url'] . $this->data['ub'] . '/gallery/' . $imagedata['image'] . '.jpg',
						'WIDTH' => $imagedata['width'], 
						'HEIGHT' => $imagedata['height'])
					);
					
					if ($imagedata['allow_dl']) {
						_style('selected.download', array(
							'URL' => s_link('a', array($this->data['subdomain'], 4, $imagedata['image'], 'save')))
						);
					}
					
					$this->data['images']--;
				}
				
				//
				// Get thumbnails
				//
				$sql_image = ($download_id) ? sql_filter(' AND g.image <> ? ', $download_id) : '';
				
				$sql = 'SELECT g.*
					FROM _artists a, _artists_images g
					WHERE a.ub = ' . $this->data['ub'] . '
						AND a.ub = g.ub
						' . $sql_image . '
					ORDER BY image DESC';
				if (!$result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
					redirect(s_link('a', array($this->data['subdomain'], 4)));
				}
				
				$tcol = 0;
				_style('thumbnails');
				
				foreach ($result as $row) {
					if (!$tcol) _style('thumbnails.row');
					
					_style('thumbnails.row.col', array(
						'URL' => s_link('a', array($this->data['subdomain'], 4, $row['image'], 'view')),
						'IMAGE' => $config['artists_url'] . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
						'RIMAGE' => get_a_imagepath($config['artists_path'], $config['artists_url'], $this->data['ub'], $row['image'] . '.jpg', array('x1', 'gallery')),
						'WIDTH' => $row['width'], 
						'HEIGHT' => $row['height'],
						'FOOTER' => $row['image_footer'])
					);
					
					$tcol = ($tcol == 3) ? 0 : $tcol + 1;
				}
				break;
		}
		
		return;
	}
	
	public function _5() {
		return;
	}
	
	//
	// Lyrics
	//
	public function _6() {
		global $config, $lang;
		
		$mode = request_var('mode', '');
		$download_id = intval(request_var('download_id', 0));
		
		if ($mode == 'view' || $mode == 'save') {
			if (!$download_id) {
				redirect(s_link('a', array($this->data['subdomain'], 6)));
			}
			
			$sql = 'SELECT l.*
				FROM _artists_lyrics l
				LEFT JOIN _artists a ON a.ub = l.ub
				WHERE l.ub = ?
					AND l.id = ?';
			if (!$lyric_data = sql_fieldrow(sql_filter($sql, $this->data['ub'], $download_id))) {
				redirect(s_link('a', array($this->data['subdomain'], 6)));
			}
		}
		
		switch ($mode) {
			case 'view':
			default:
				if ($mode == 'view') {
					if (!$this->auth['mod']) {
						$sql = 'UPDATE _artists_lyrics SET views = views + 1
							WHERE ub = ?
							AND id = ?';
						sql_query(sql_filter($sql, $this->data['ub'], $lyric_data['id']));
						
						$lyric_data['views']++;
					}
					
					_style('read', array(
						'TITLE' => $lyric_data['title'],
						'AUTHOR' => $lyric_data['author'],
						'TEXT' => str_replace("\n", '<br />', $lyric_data['text']),
						'VIEWS' => $lyric_data['views'])
					);
				}
				
				$sql = 'SELECT l.*
					FROM _artists_lyrics l
					LEFT JOIN _artists a ON a.ub = l.ub
					WHERE l.ub = ?
					ORDER BY title';
				$result = sql_rowset(sql_filter($sql, $this->data['ub']));
				
				foreach ($result as $i => $row) {
					if (!$i) _style('select');
					
					_style('select.item', array(
						'URL' => s_link('a', array($this->data['subdomain'], 6, $row['id'], 'view')) . '#read',
						'TITLE' => $row['title'],
						'SELECTED' => ($download_id && $download_id == $row['id']) ? true : false)
					);
				}
				break;
		}
		
		return;
	}
	
	public function _7() {
		return;
	}
	
	public function _8() {
		return;
	}
	
	//
	// Downloads
	//
	public function _9() {
		$this->dl_setup();
		
		$mode = request_var('dl_mode', '');
		if ($mode == '') {
			$mode = 'view';
		}
		
		if (!in_array($mode, array('view', 'save', 'vote', 'fav'))) {
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		$mode = 'dl_' . $mode;
		if (!method_exists($this, $mode)) {
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		return $this->$mode();
	}
	
	public function _10() {
		return;
	}
	
	//
	// Messages
	//
	public function _12() {
		global $user, $config;
		
		$post_id = request_var('post_id', 0);
		if (!$post_id) {
			fatal_error();
		}
		
		$sql = 'SELECT a.ub, p.*, m.user_id, m.username, m.username_base, m.user_color
			FROM _artists a, _artists_posts p, _members m
			WHERE a.ub = ?
				AND p.post_id = ?
				AND a.ub = p.post_ub
				AND p.poster_id = m.user_id';
		if (!$pdata = sql_fieldrow($sql, $this->data['ub'], $post_id)) {
			fatal_error();
		}
		
		$mode = request_var('mode', '');
		
		if ($mode == 'report') {
			
		} else {
			$comments_ref = s_link('a', array($this->data['subdomain'], 12, $pdata['post_id'], 'reply'));
			
			$start = intval(request_var('rs', 0));
			$this->msg->ref = $comments_ref;
			$this->msg->auth = $this->auth;
			
			$sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color, m.user_avatar
				FROM _artists_posts p, _members m, _artists a
				WHERE p.post_ub = ?
					AND (p.post_id = ? OR p.post_reply = ?)
					AND p.post_active = 1
					AND p.post_ub = a.ub
					AND p.poster_id = m.user_id
				ORDER BY p.post_reply ASC, p.post_time DESC
				LIMIT ??, ??'; 
			
			$this->msg->data = array(
				'A_LINKS_CLASS' => 'bold red',
				'SQL' => sql_filter($sql, $this->data['ub'], $post_id, $post_id, $start, $config['s_posts'])
			);
			
			if ($this->auth['user']) {
				$this->msg->data['CONTROL']['reply'] = array(
					'REPLY' => array(
						'URL' => s_link('a', array($this->data['subdomain'], 12, '%d')) . '#reply',
						'ID' => 'post_id'
					)
				);
			}
			
			if ($this->auth['user'] && !$this->auth['mod']) {
				$this->msg->data['CONTROL']['report'] = array(
					'REPORT' => array(
						'URL' => s_link('a', array($this->data['subdomain'], 12, '%d', 'report')),
						'ID' => 'post_id'
					)
				);
			}
			
			if ($this->auth['mod']) {
				$this->msg->data['CONTROL']['auth'] = array();
				
				if ($this->auth['adm'] && $user->is('founder')) {
					$this->msg->data['CONTROL']['auth']['EDIT'] = array(
						'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'edit', 'id' => '%d')),
						'ID' => 'post_id'
					);
				}
				
				$this->msg->data['CONTROL']['auth']['DELETE'] = array(
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'delete', 'id' => '%d')),
					'ID' => 'post_id'
				);
			}
			
			$sql = preg_replace('/LIMIT ([0-9]+), ([0-9]+)/', '', $this->msg->data['SQL']);
			
			$reply_result = sql_rowset($sql);
			$total_posts = count($reply_result);
			unset($reply_result);
			
			$this->msg->view($start, 'rs', $total_posts, $config['s_posts'], 'reply_msg', 'RMSG_', '', false);
			
			v_style(array(
				'PARENT_ID' => $post_id)
			);
		}
		
		if ($this->auth['post'] && ($this->data['a_active'] || $user->is('founder'))) {
			if ($this->auth['user']) {
				_style('reply_post_box', array(
					'REF' => $comments_ref)
				);
			} else {
				_style('reply_no_guest_posting', array(
					'LEGEND' => sprintf($user->lang['UB_NO_GUEST_POSTING'], $this->data['name'], s_link('my', 'register')))
				);
			}
		} else {
			_style('reply_no_post_auth');
			
			if ($this->auth['post_until']) {
				_style('reply_no_post_auth.until', array(
					'UNTIL_DATETIME' => $user->format_date($this->auth['post_until']))
				);
			}
		}
		
		return;
	}
	
	//
	// Email
	//
	public function _13() {
		if (empty($this->data['email'])) {
			fatal_error();
		}
		
		if (!$this->auth['user']) {
			do_login();
		}
		
		global $user, $config;
		
		$error_msg = '';
		$subject = '';
		$message = '';
		$current_time = time();
		
		if (_button()) {
			$subject = request_var('subject', '');
			$message = request_var('message', '', true);
			
			if (empty($subject) || empty($message)) {
				$error_msg .= (($error_msg != '') ? '<br />' : '') . $user->lang['FIELDS_EMPTY'];
			}
			
			if (empty($error_msg)) {
				$sql = 'UPDATE _artists SET last_email = ?, last_email_user = ?
					WHERE ub = ?';
				sql_query(sql_filter($sql, $current_time, $user->d('user_id'), $this->data['ub']));
				
				$emailer = new emailer($config['smtp_delivery']);

				$emailer->from($user->d('user_email'));

				$email_headers = 'X-AntiAbuse: User_id - ' . $user->d('user_id') . "\n";
				$email_headers .= 'X-AntiAbuse: Username - ' . $user->d('username') . "\n";
				$email_headers .= 'X-AntiAbuse: User IP - ' . $user->ip . "\n";

				$emailer->use_template('mmg_send_email', $config['default_lang']);
				$emailer->email_address($this->data['email']);
				$emailer->set_subject($subject);
				$emailer->extra_headers($email_headers);

				$emailer->assign_vars(array(
					'SITENAME' => $config['sitename'], 
					'BOARD_EMAIL' => $config['board_email'], 
					'FROM_USERNAME' => $user->d('username'), 
					'UB_NAME' => $this->data['name'], 
					'MESSAGE' => $message
				));
				$emailer->send();
				$emailer->reset();
				
				redirect(s_link('a', $this->data['subdomain']));
			}
		}
		
		if ($error_msg != '') {
			_style('error');
		}
		
		v_style(array(
			'ERROR_MESSAGE' => $error_msg,
			
			'SUBJECT' => $subject,
			'MESSAGE' => $message)
		);
		
		return;
	}
	
	//
	// Redirect to Website
	//
	public function _14() {
		if ($this->data['www'] == '') {
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		global $user;
		
		if (!$this->data['www_awc'] && !check_www($this->data['www'])) {
			trigger_error(sprintf($user->lang['LINKS_CANT_REDIRECT'], $this->data['www']));
		}
		
		$sql = 'UPDATE _artists SET www_views = www_views + 1
			WHERE ub = ?';
		sql_query(sql_filter($sql, $this->data['ub']));
		
		header('Location: http://' . $this->data['www']);
		exit;
	}
	
	//
	// Favorites
	//
	public function _15() {
		global $user;
		
		if (!$this->auth['user']) {
			do_login(sprintf($user->lang['LOGIN_BE_FAN'], $this->data['name']));
		}
		
		$url = s_link('a', $this->data['subdomain']);
		
		if ($this->auth['smod']) {
			redirect($url);
		}
		
		if ($this->auth['fav']) {
			$sql_member = array('user_a_favs' => $user->d('user_a_favs') - 1);
			
			if ($user->d('user_a_favs') == 1 && $user->d('user_type') == USER_FAN) {
				$sql_member += array('user_type' => USER_NORMAL, 'user_color' => '4D5358');
			}
			
			$sql = 'DELETE FROM _artists_fav
				WHERE ub = ?
					AND user_id = ?';
			sql_query(sql_filter($sql, $this->data['ub'], $user->d('user_id')));
			
			$user->delete_all_unread(UH_AF, $user->d('user_id'));
		} else {
			$sql_member = array('user_a_favs' => $user->d('user_a_favs') + 1);
			
			if ($user->d('user_type') == USER_NORMAL) {
				$sql_member += array('user_type' => USER_FAN, 'user_color' => '7A0B43');
			}
			
			$sql_insert = array(
				'ub' => (int) $this->data['ub'],
				'user_id' => (int) $user->d('user_id'),
				'joined' => time()
			);
			$sql = 'INSERT INTO _artists_fav' . sql_build('INSERT', $sql_insert);
			$fav_nextid = sql_query_nextid($sql);
			
			$user->save_unread(UH_AF, $fav_nextid, $this->data['ub']);
		}
		
		$sql = 'UPDATE _members SET ??
			WHERE user_id = ?';
		sql_query(sql_build($sql, sql_build('UPDATE', $sql_member), $user->d('user_id')));
		
		redirect($url);
		
		return;
	}
	
	public function _16() {
		return;
	}
	
	//
	// Vote
	//
	public function _17() {
		if (!$this->auth['user']) {
			do_login();
		}
		
		$option_id = intval(request_var('vote_id', 0));
		$url = s_link('a', $this->data['subdomain']);
		
		if ($this->auth['mod'] || !$option_id || !in_array($option_id, $this->voting['ub'])) {
			redirect($url);
		}
		
		global $user;
		
		$sql = 'SELECT user_id
			FROM _artists_voters
			WHERE ub = ?
				AND user_id = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
			redirect($url);
		}
		
		//
		$sql = 'UPDATE _artists_votes SET vote_result = vote_result + 1
			WHERE ub = ?
				AND option_id = ?';
		sql_query(sql_filter($sql, $this->data['ub'], $option_id));
		
		if (!sql_affectedrows()) {
			$sql_insert = array(
				'ub' => $this->data['ub'],
				'option_id' => $option_id,
				'vote_result' => 1
			);
			$sql = 'INSERT INTO _artists_votes' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
		}
		
		$sql_insert = array(
			'ub' => $this->data['ub'],
			'user_id' => $user->d('user_id'),
			'user_option' => $option_id
		);
		$sql = 'INSERT INTO _artists_voters' . sql_build('INSERT', $sql_insert);
		sql_query($sql);
		
		$sql = 'UPDATE _artists SET votes = votes + 1
			WHERE ub = ?';
		sql_query(sql_filter($sql, $this->data['ub']));
		
		redirect($url);
	}
	
	public function _18() {
		global $user;
		
		$sql = 'SELECT *
			FROM _artists_video
			WHERE video_a = ?
			ORDER BY video_added DESC';
		$result = sql_rowset(sql_filter($sql, $this->data['ub']));
		
		foreach ($result as $i => $row) {
			if (!$i) _style('video');
			
			_style('video.row', array(
				'NAME' => $row['video_name'],
				'CODE' => $row['video_code'],
				'TIME' => $user->format_date($row['video_added']))
			);
		}
		
		return;
	}
}

//
// Class: _artists
//
class _artists extends layout {
	public $auth = array();
	public $data = array();
	public $adata = array();
	public $images = array();
	public $layout = array();
	public $voting = array();
	public $msg = array();
	public $ajx = true;
	
	public function __construct() {
		$this->layout = array(
			'_01' => array('code' => 1, 'text' => 'UB_L01', 'tpl' => 'main'),
			'_02' => array('code' => 2, 'text' => 'UB_L02', 'tpl' => 'bio'),
			'_03' => array('code' => 3, 'text' => 'UB_L03', 'tpl' => 'albums'),
			'_04' => array('code' => 4, 'text' => 'UB_L04', 'tpl' => 'gallery'),
			'_05' => array('code' => 5, 'text' => 'UB_L05', 'tpl' => 'tabs'),
			'_06' => array('code' => 6, 'text' => 'UB_L06', 'tpl' => 'lyrics'),
			'_07' => array('code' => 7, 'text' => 'UB_L07', 'tpl' => 'interviews'),
			'_09' => array('code' => 9, 'text' => 'DOWNLOADS', 'tpl' => 'downloads'),
			'_13' => array('code' => 13, 'text' => '', 'tpl' => 'email'),
			'_16' => array('code' => 16, 'text' => '', 'tpl' => 'news'),
			'_18' => array('code' => 18, 'text' => 'UB_L17', 'tpl' => 'video')
		);
		
		$this->voting = array(
			'ub' => array(1, 2, 3, 5),
			'ud' => array(1, 2, 3, 4, 5)
		);
	}
	
	public function v($property, $value = -5000) {
		if ($value != -5000) {
			$this->data[$property] = $value;
			return $value;
		}
		
		if (!isset($this->data[$property])) {
			return false;
		}
		
		return $this->data[$property];
	}
	
	public function get_data() {
		$sql = 'SELECT *
			FROM _artists
			ORDER BY name ASC';
		$this->adata = sql_rowset($sql, 'ub');
		
		return;
	}
	
	public function _setup() {
		global $user;
		
		$_a = request_var('id', '');
		if (!empty($_a)) {
			if (preg_match('/([0-9a-zA-Z]+)/', $_a)) {
				$sql = 'SELECT * 
					FROM _artists
					WHERE subdomain = ? 
					LIMIT 1';
				if ($this->data = sql_fieldrow(sql_filter($sql, strtolower($_a)))) {
					return true;
				}
			}
			
			fatal_error();
		}
		
		return false;
	}
	
	public function _auth() {
		global $user;
		
		$this->auth['user'] = ($user->is('member')) ? true : false;
		$this->auth['adm'] = ($user->is('founder')) ? true : false;
		$this->auth['mod'] = ($this->auth['adm']) ? true : false;
		$this->auth['smod'] = false;
		$this->auth['fav'] = false;
		$this->auth['post'] = true;
		
		if (!$this->auth['user'] || $this->data['layout'] == 14) {
			return;
		}
		
		if ($user->is('artist')) {
			$sql = 'SELECT u.user_id
				FROM _members u, _artists_auth a, _artists b
				WHERE a.ub = ?
					AND a.user_id = ? 
					AND a.user_id = u.user_id
					AND b.ub = a.ub
					AND b.ub = a.ub';
			if (sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
				$this->auth['smod'] = $this->auth['mod'] = true;
				return;
			}
		}
		
		$sql = 'SELECT aa.*
			FROM _artists_access aa
			LEFT JOIN _artists a ON aa.ub = a.ub
			RIGHT JOIN _members m ON aa.user_id = m.user_id
			WHERE aa.ub = ?
				AND aa.user_id = ?';
		if ($row = sql_fieldrow(sql_filter($sql, $this->data['ub'] , $user->d('user_id')))) {
			$current_time = time();
			
			if (!$row['ban_time'] || $row['ban_time'] > $current_time) {
				if ($row['ban_access']) {
					global $user;
					
					$message = (!$row['ban_time']) ? 'UB_BANNED' : sprintf($user->lang['UB_BANNED_UNTIL'], $user->format_date($row['ban_time']));
					trigger_error($message);
				} else {
					$this->auth['post'] = false;
					$this->auth['post_until'] = ($row['ban_time']) ? $row['ban_time'] : 0;
				}
			} else {
				$sql = 'DELETE FROM _artists_access
					WHERE user_id = ?
						AND ub = ?';
				sql_query(sql_filter($sql, $user->d('user_id'), $this->data['ub']));
			}
		}
		
		if ($user->d('user_type') != USER_NORMAL) {
			$sql = 'SELECT f.* 
				FROM _artists b, _artists_fav f, _members m 
				WHERE b.ub = ? 
					AND b.ub = f.ub 
					AND f.user_id = ? 
					AND f.user_id = m.user_id 
					AND m.user_type <> ??';
			if (sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id'), USER_IGNORE))) {
				$this->auth['fav'] = true;
			}
		}
		
		return;
	}
	
	public function call_layout() {
		$layout = '_' . $this->data['layout'];
		if (!method_exists($this, $layout)) {
			redirect(s_link('a', $this->data['subdomain']));
		}
		
		return $this->$layout();
	}
	
	public function stats($id) {
		if (is_array($id)) {
			$all_stats = array();
			foreach ($id as $item) {
				$all_stats[$item] = $this->stats($item);
			}
			return $all_stats;
		}
		
		$t_ub = $s_ub = 0;
		foreach ($this->adata as $data) {
			if ($data[$id] > $s_ub) {
				$s_ub = $data[$id];
				$t_ub = $data['ub'];
			}
		}
		
		return ($t_ub) ? $this->adata[$t_ub] : false;
	}
	
	public function last_records() {
		global $user, $config, $cache;
		
		if (!$a_records = $cache->get('a_records')) {
			$sql = 'SELECT ub, subdomain, name, genre
				FROM _artists
				ORDER BY datetime DESC
				LIMIT 3';
			$a_records = sql_rowset($sql, 'ub');
			
			$cache->save('a_records', $a_records);
		}
		
		if (!$ai_records = $cache->get('ai_records')) {
			$ai_records = array();
			
			foreach ($a_records as $row) {
				$sql = 'SELECT ub, images
					FROM _artists_images
					WHERE ub = ?
					ORDER BY image';
				$result = sql_rowset(sql_filter($sql, $row['ub']));
				
				foreach ($result as $row) {
					$ai_records[$row['ub']][] = $row2['image'];
				}
			}
			
			$cache->save('ai_records', $ai_records);
		}
		
		_style('a_records');
		
		foreach ($a_records as $row) {
			_style('a_records.item', array(
				'URL' => s_link('a', $row['subdomain']),
				'NAME' => $row['name'],
				'GENRE' => $row['genre'])
			);
			
			if (isset($ai_records[$row['ub']])) {
				$ai_select = array_rand($ai_records[$row['ub']]);
				
				_style('a_records.item.image', array(
					'IMAGE' => $config['artists_url'] . $row['ub'] . '/thumbnails/' . $ai_records[$row['ub']][$ai_select] . '.jpg')
				);
			}
		}
	}
	
	public function latest_music() {
		$sql = 'SELECT d.id, d.title, a.subdomain, a.name
			FROM _dl d, _artists a
			WHERE d.ud = 1
				AND d.ub = a.ub
			ORDER BY d.date DESC
			LIMIT 0, 10';
		$result = sql_rowset($sql);
		
		foreach ($result as $row) {
			_style('downloads', array(
				'URL' => s_link('a', array($row['subdomain'], 9, $row['id'])),
				'A' => $row['name'],
				'T' => $row['title'])
			);
		}
		
		return true;
	}
	
	public function top_stats() {
		global $user, $config;
		
		_style('a_stats');
		
		$all_data = $this->stats(array('datetime', 'views', 'votes', 'posts'));
		
		if ($all_data['datetime']) {
			global $cache;
			
			$a_random = array();
			if (!$a_random = $cache->get('a_last_images')) {
				$sql = 'SELECT *
					FROM _artists_images
					WHERE ub = ?
					ORDER BY image';
				if ($a_random = sql_rowset(sql_filter($sql, $all_data['datetime']['ub']), false, 'image')) {
					$cache->save('a_last_images', $a_random);
				}
			}
			
			if (sizeof($a_random)) {
				$selected_image = array_rand($a_random);
				if (isset($a_random[$selected_image])) {
					_style('a_stats.gallery', array(
						'IMAGE' => $config['artists_url'] . $all_data['datetime']['ub'] . '/thumbnails/' . $a_random[$selected_image] . '.jpg',
						'URL' => s_link('a', $all_data['datetime']['subdomain']))
					);
				}
			}
		}
		
		foreach ($all_data as $id => $data) {
			if ($data['name'] != '') {
				_style('a_stats.item', array(
					'LANG' => $user->lang['UB_TOP_' . strtoupper($id)],
					'URL' => s_link('a', $data['subdomain']),
					'NAME' => $data['name'],
					'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
					'GENRE' => $data['genre'])
				);
			}
		}
		
		return;
	}
	
	public function thumbnails() {
		global $cache, $config;
		
		if (!$a_recent = $cache->get('a_recent')) {
			$sql = 'SELECT ub
				FROM _artists
				ORDER BY datetime DESC
				LIMIT 10';
			$result = sql_rowset($sql);
			
			$a_recent = array();
			foreach ($result as $row) {
				$a_recent[$row['ub']] = 1;
			}
			
			$cache->save('a_recent', $a_recent);
		}
		
		$a_ary = array();
		for ($i = 0; $i < 3; $i++) {
			$_a = array_rand($a_recent);
			if (!$this->adata[$_a]['images'] || isset($a_ary[$_a])) {
				$i--;
				continue;
			}
			$a_ary[$_a] = $this->adata[$_a];
		}
		
		for ($i = 0; $i < 2; $i++) {
			$_a = array_rand($this->adata);
			if (!$this->adata[$_a]['images'] || isset($a_ary[$_a])) {
				$i--;
				continue;
			}
			$a_ary[$_a] = $this->adata[$_a];
		}
		
		if (sizeof($a_ary)) {
			$sql = 'SELECT *
				FROM _artists_images
				WHERE ub IN (??)
				ORDER BY RAND()';
			$result = sql_rowset(sql_filter($sql, implode(',', array_keys($a_ary))));
			
			$random_images = array();
			foreach ($result as $row) {
				if (!isset($random_images[$row['ub']])) {
					$random_images[$row['ub']] = $row['image'];
				}
			}
			
			_style('thumbnails');
			
			foreach ($a_ary as $ub => $data) {
				_style('thumbnails.item', array(
					'NAME' => $data['name'],
					'IMAGE' => $config['artists_url'] . $ub . '/thumbnails/' . $random_images[$ub] . '.jpg',
					'URL' => s_link('a', $data['subdomain']),
					'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
					'GENRE' => $data['genre'])
				);
			}
		}
		
		return;
	}
	
	public function get_images($mainframe = false, $ub = 0, $rand = false) {
		if ($this->images) {
			return;
		}
		
		global $config;
		
		if ($mainframe) {
			$sql = 'SELECT i.* 
				FROM _artists_images i, _artists a 
				WHERE i.ub = a.ub 
				ORDER BY i.image';
		} else {
			if ($ub) {
				$sql = 'SELECT i.* 
					FROM _artists_images i 
					LEFT JOIN _artists a ON a.ub = i.ub 
					WHERE i.ub = ? 
					ORDER BY ' . (($rand) ? 'RAND() LIMIT 1' : 'image');
				$sql = sql_filter($sql, $ub);
			}
		}
		
		if ($ub && !$mainframe) {
			if ($row = sql_fieldrow($sql)) {
				$this->images[$row['ub']][$row['image']] = array(
					'path' => $config['artists_url'] . $row['ub'] . '/gallery/' . $row['image'] . '.jpg',
					'image' => $row['image'],
					'allow_dl' => $row['allow_dl']
				);
			}
			
			return $row['image'];
		}
		
		$result = sql_rowset($sql);
		
		foreach ($result as $row) {
			$this->images[$row['ub']][$row['image']] = array(
				'path' => $config['artists_url'] . $row['ub'] . '/gallery/' . $row['image'] . '.jpg',
				'image' => $row['image'],
				'allow_dl' => $row['allow_dl']
			);
		}
		
		return;
	}
	
	public function downloads() {
		$this->dl_sql();
		
		if (empty($this->ud_song)) {
			return;
		}
		
		global $config;
		
		_style('downloads');
		
		$ud_in_ary = array();
		foreach ($this->ud_song as $ud => $dl_data) {
			$dl_size = sizeof($dl_data);
			if (!$dl_size) {
				continue;
			}
			
			$ud_size = ($dl_size > $config['main_dl']) ? $config['main_dl'] : $dl_size;
			$download_type = $this->dl_type($ud);
			
			_style('downloads.panel', array(
				'UD' => $download_type['lang'],
				'TOTAL_COUNT' => $dl_size)
			);
			
			for ($i = 0; $i < $ud_size; $i++) {
				$ud_rand = array_rand($dl_data);
				
				if (isset($ud_in_ary[$ud][$ud_rand])) {
					$i--;
					continue;
				}
				
				$ud_in_ary[$ud][$ud_rand] = true;
				
				_style('downloads.panel.item', array(
					'UB' => $this->adata[$dl_data[$ud_rand]['ub']]['name'],
					'TITLE' => $dl_data[$ud_rand]['title'],
					'URL' => s_link('a', array($this->adata[$dl_data[$ud_rand]['ub']]['subdomain'], 9, $dl_data[$ud_rand]['id'])))
				);
			}
		}
		
		return;
	}
	
	public function _list() {
		global $user, $config;
		
		$sql = 'SELECT *
			FROM _artists
			ORDER BY local DESC, name ASC';
		$result = sql_rowset($sql);
		
		$alphabet = array();
		foreach ($result as $row) {
			$this->adata[$row['local']][$row['ub']] = $row;
			
			$alpha_id = strtolower($row['name']);
			$alpha_id = $alpha_id{0};
			if (!isset($alphabet[$alpha_id])) {
				if (preg_match('/([0-9])/', $alpha_id)) {
					$alpha_id = '#';
				}
				$alphabet[$alpha_id] = true;
			}
		}
		
		$selected_char = '';
		$s_alphabet = intval(request_var('alphabet', 0));
		
		if ($s_alphabet) {
			$selected_char = chr(octdec($s_alphabet));
			if (!preg_match('/([\#a-z])/', $selected_char)) {
				redirect(s_link('a'));
			}
		}
		
		if ($s_alphabet) {
			$sql_where = 'WHERE ' . (($selected_char == '#') ? "name NOT RLIKE '^[a-z]'" : sql_filter('name LIKE ?', $selected_char . '%'));
		} else {
			$sql_where = 'WHERE images > 1';
		}
		
		$sql_order = (!$s_alphabet) ? 'RAND() LIMIT 12' : 'name';
		
		$sql = 'SELECT *
			FROM _artists
			' . $sql_where . '
			ORDER BY ' . $sql_order;
		if (!$selected_artists = sql_rowset($sql, 'ub')) {
			redirect(s_link('a'));
		}
		
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
		
		_style('search_match');
		
		if (!$s_alphabet) {
			_style('search_match.ajx');
			$this->ajx = false;
		}
		
		$tcol = 0;
		foreach ($selected_artists as $ub => $data) {
			$image = ($data['images']) ? $ub . '/thumbnails/' . $random_images[$ub] . '.jpg' : 'default/shadow.gif';
			
			if (!$tcol) _style('row');
			
			_style('row.col', array(
				'NAME' => $data['name'],
				'IMAGE' => $config['artists_url'] . $image,
				'URL' => s_link('a', $data['subdomain']),
				'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
				'GENRE' => $data['genre'])
			);
			
			$tcol = ($tcol == 3) ? 0 : $tcol + 1;
		}
		
		ksort($alphabet);
		
		foreach ($alphabet as $key => $null) {
			_style('alphabet_item', array(
				'CHAR' => strtoupper($key),
				'URL' => s_link('a', '_' . decoct(ord($key))))
			);
		}
		
		v_style(array(
			'TOTAL_A' => $config['max_artists'],
			'SELECTED_LETTER' => ($selected_char) ? strtoupper($selected_char) : '')
		);
		
		return;
	}
	
	public function _panel() {
		global $user, $config, $template;
		
		$this->data['layout'] = request_var('layout', 0);
		$this->_auth();
		
		if (!$this->data['layout']) {
			$this->data['layout'] = 1;
		}
		
		switch ($this->data['layout']) {
			case 14:
			case 15:
			case 17:
				$this->call_layout();
				break;
			default:
				//
				// Nav
				//
				$s_layout = array();
				$s_layout['a']['_01'] = true;
				$s_layout['a']['_02'] = ($this->data['bio'] != '') ? true : false;
				// $s_layout['a']['_03'] = true;
				$s_layout['a']['_04'] = ($this->data['images'] > 1) ? true : false;
				// $s_layout['_05'] = true;
				$s_layout['a']['_06'] = ($this->data['lirics'] > 0) ? true : false;
				// $s_layout['a']['_07'] = true;
				$s_layout['a']['_09'] = ($this->data['layout'] == 9) ? true : false;
				$s_layout['a']['_12'] = ($this->data['layout'] == 12) ? true : false;
				$s_layout['a']['_18'] = ($this->data['a_video'] > 0) ? true : false;
				
				foreach ($this->layout as $item => $data) {
					$s_layout['x'][$item] = $data['code'];
					
					if ($data['text'] == '') {
						$s_layout['e'][$item] = $data['code'];
					}
					
					if (isset($s_layout['a'][$item]) && $s_layout['a'][$item] && $data['tpl'] != '') {
						$s_layout['s'][$data['code']] = $data;
					}
					
					if (($this->data['layout'] == $data['code']) && $data['tpl'] != '') {
						$this->data['template'] = $data['tpl'];
					}
				}
				
				if (!in_array($this->data['layout'], $s_layout['x']) || (!isset($s_layout['s'][$this->data['layout']]) && !in_array($this->data['layout'], $s_layout['e']))) {
					redirect(s_link('a', $this->data['subdomain']));
				}
				
				//
				// Call selected layout
				//
				$this->call_layout();
				
				//
				// Build nav
				//
				foreach ($s_layout['s'] as $data) {
					_style('nav', array(
						'LANG' => $user->lang[$data['text']])
					);
					
					if ($this->data['layout'] == $data['code']) {
						_style('nav.strong');
						continue;
					}
					
					if ($data['code'] === 1) $data['code'] = ''; 
					
					_style('nav.a', array(
						'URL' => s_link('a', array($this->data['subdomain'], $data['code'])))
					);
				}
				
				//
				// Update stats
				//				
				if (!$this->auth['mod']) {
					$update_views = false;
					$current_time = time();
					$current_month = date('Ym', $current_time);
					
					if ($this->auth['user']) {
						$sql_viewers = array(
							'datetime' => (int) $current_time,
							'user_ip' => $user->ip
						);
						
						$sql_viewers2 = array(
							'ub' => (int) $this->data['ub'],
							'user_id' => (int) $user->d('user_id')
						);
						
						$sql = 'UPDATE _artists_viewers SET ??
							WHERE ??';
						sql_query(sql_filter($sql, sql_build('UPDATE', $sql_viewers), sql_build('SELECT', $sql_viewers2)));
						
						if (!sql_affectedrows()) {
							$update_views = true;
							$sql_stats = array('ub' => (int) $this->data['ub'], 'date' => (int) $current_month);
							
							$sql = 'INSERT INTO _artists_viewers' . sql_build('INSERT', $sql_viewers + $sql_viewers2);
							sql_query($sql);
							
							$sql = 'UPDATE _artists_stats SET members = members + 1
								WHERE ??';
							sql_query(sql_filter($sql, sql_build('SELECT', $sql_stats)));
							
							if (!sql_affectedrows()) {
								$sql_insert = array(
									'members' => 1,
									'guests' => 0
								);
								$sql = 'INSERT INTO _artists_stats' . sql_build('INSERT', $sql_stats + $sql_insert);
								sql_query($sql);
							}
							
							$sql = 'SELECT user_id
								FROM _artists_viewers
								WHERE ub = ?
								ORDER BY datetime DESC
								LIMIT 10, 1';
							if ($row = sql_fieldrow(sql_filter($sql, $this->data['ub']))) {
								$sql = 'DELETE FROM _artists_viewers
									WHERE ub = ?
										AND user_id = ?';
								sql_query(sql_filter($sql, $this->data['ub'], $row['user_id']));
							}
						}
					}
					
					if ((($this->auth['user'] && $update_views) || (!$this->auth['user'] && $this->data['layout'] == 1)) && !isset($_REQUEST['ps'])) {
						$sql = 'UPDATE _artists SET views = views + 1
							WHERE ub = ?';
						sql_query(sql_filter($sql, $this->data['ub']));
						$this->data['views']++;
						
						if ((!$this->auth['user'] && $this->data['layout'] == 1) && !isset($_REQUEST['ps'])) {
							$sql_stats = array(
								'ub' => (int) $this->data['ub'],
								'date' => (int) $current_month
							);
							$sql = 'UPDATE _artists_stats SET guests = guests + 1
								WHERE ??';
							sql_query(sql_filter($sql, sql_build('SELECT', $sql_stats)));
							
							if (!sql_affectedrows()) {
								$sql_insert = array(
									'members' => 0,
									'guests' => 1
								);
								$sql = 'INSERT INTO _artists_stats' . sql_build('INSERT', $sql_stats + $sql_insert);
								sql_query($sql);
							}
						}
					}
				}
				
				//
				// Own events
				//
				$timezone = $config['board_timezone'] * 3600;
		
				list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
				$midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;
				
				$g = getdate($midnight);
				$week = mktime(0, 0, 0, $m, ($d + (7 - ($g['wday'] - 1)) - (!$g['wday'] ? 7 : 0)), $y) - $timezone;
				
				$sql = 'SELECT *
					FROM _events e, _artists_events ae
					WHERE ae.a_artist = ?
						AND ae.a_event = e.id
					ORDER BY e.date';
				$result = sql_rowset(sql_filter($sql, $this->data['ub']));
				
				$events = array();
				foreach ($result as $row) {
					if ($row['date'] >= $midnight) {
						if ($row['date'] >= $midnight && $row['date'] < $midnight + 86400) {
							$events['is_today'][] = $row;
						} else if ($row['date'] >= $midnight + 86400 && $row['date'] < $midnight + (86400 * 2)) {
							$events['is_tomorrow'][] = $row;
						} else if ($row['date'] >= $midnight + (86400 * 2) && $row['date'] < $week) {
							$events['is_week'][] = $row;
						} else {
							$events['is_future'][] = $row;
						}
					} else if ($row['images']) {
						$events['is_gallery'][] = $row;
					}
				}
				
				if (isset($events['is_gallery']) && sizeof($events['is_gallery'])) {
					$gallery = $events['is_gallery'];
					@krsort($gallery);
					
					_style('events_gallery');
					foreach ($gallery as $row) {
						_style('events_gallery.item', array(
							'URL' => s_link('events', $row['event_alias']),
							'TITLE' => $row['title'],
							'DATETIME' => $user->format_date($row['date'], $user->lang['DATE_FORMAT']))
						);
					}
					
					unset($events['is_gallery']);
				}
				
				if (sizeof($events)) {
					_style('events_future');
					
					foreach ($events as $is_date => $data) {
						_style('events_future.set', array(
							'L_TITLE' => $user->lang['UE_' . strtoupper($is_date)])
						);
						
						foreach ($data as $item) {
							_style('events_future.set.row', array(
								'ITEM_ID' => $item['id'],
								'TITLE' => $item['title'],
								'DATE' => $user->format_date($item['date']),
								'THUMBNAIL' => $config['events_url'] . 'future/thumbnails/' . $item['id'] . '.jpg',
								'SRC' => $config['events_url'] . 'future/' . $item['id'] . '.jpg')
							);
						}
					}
				}
				
				//
				// Poll
				//
				$user_voted = false;
				if ($this->auth['user'] && !$this->auth['mod']) {
					$sql = 'SELECT *
						FROM _artists_voters
						WHERE ub = ?
							AND user_id = ?';
					if (sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
						$user_voted = true;
					}
				}
				
				_style('ub_poll');
				
				if ($this->auth['mod'] || !$this->auth['user'] || $user_voted) {
					$sql = 'SELECT option_id, vote_result
						FROM _artists_votes
						WHERE ub = ?
						ORDER BY option_id';
					$results = sql_rowset(sql_filter($sql, $this->data['ub']), 'option_id', 'vote_result');
					
					_style('ub_poll.results');
					
					foreach ($this->voting['ub'] as $item) {
						$vote_result = (isset($results[$item])) ? intval($results[$item]) : 0;
						$vote_percent = ($this->data['votes'] > 0) ? $vote_result / $this->data['votes'] : 0;
		
						_style('ub_poll.results.item', array(
							'CAPTION' => $user->lang['UB_VC' . $item],
							'RESULT' => $vote_result,
							'PERCENT' => sprintf("%.1d", ($vote_percent * 100)))
						);
					}
				} else {
					_style('ub_poll.options', array(
						'S_VOTE_ACTION' => s_link('a', array($this->data['subdomain'], 17)))
					);
					
					foreach ($this->voting['ub'] as $item) {
						_style('ub_poll.options.item', array(
							'ID' => $item,
							'CAPTION' => $user->lang['UB_VC' . $item])
						);
					}
				}
				
				//
				// Downloads
				//
				if ($this->data['um'] || $this->data['uv']) {
					$this->dl_sql($this->data['ub'], 'ud, title');
					
					foreach ($this->ud_song as $key => $data) {
						$download_type = $this->dl_type($key);
						_style('ud_block', array('LANG' => $download_type['lang']));
						
						foreach ($data as $song) {
							_style('ud_block.item', array(
								'TITLE' => $song['title'])
							);
							
							if (isset($this->dl_data['id']) && ($song['id'] == $this->dl_data['id'])) {
								_style('ud_block.item.strong');
								continue;
							}
							
							_style('ud_block.item.a', array(
								'URL' => s_link('a', array($this->data['subdomain'], 9, $song['id'])))
							);
						}
					}
				}
				
				//
				// Art
				//
				/*if ($this->data['arts']) {
					_style('art_block');
						
					$sql = 'SELECT *
						FROM _art
						WHERE ub = ?
						ORDER BY title';
					$result = sql_rowset(sql_filter($sql, $this->data['ub']));
					
					foreach ($result as $row) {
						_style('art_block.item', array('TITLE' => $row['title']));
						
						if ($row['id'] == $this->ud['TITLE']) {
							_style('art_block.item.strong');
							continue;
						}
						
						_style('art_block.item.a', array('URL' => s_link('art', $row['id'])));
					}
				}*/
				
				//
				// Messages
				//
				/*
				$ref_layout = in_array($this->data['layout'], array(9, 12, 16)) ? 1 : $this->data['layout'];
				$comments_ref = s_link('a', array($this->data['subdomain'], $ref_layout));
				
				if ($this->data['posts']) {
					$start = intval(request_var('ps', 0));
					$this->msg->ref = $comments_ref;
					$this->msg->auth = $this->auth;
					
					$sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_color
						FROM _artists_posts p, _members m, _artists a
						WHERE p.post_ub = ? 
							AND p.post_ub = a.ub
							AND p.post_active = 1 
							AND p.poster_id = m.user_id 
						ORDER BY p.post_time DESC 
						LIMIT ??, ??';
					
					$this->msg->data = array(
						'A_LINKS_CLASS' => 'bold red',
						'SQL' => sql_filter($sql, $this->data['ub'], $start, $config['s_posts'])
					);
					
					if ($this->auth['user']) {
						$this->msg->data['CONTROL']['reply'] = array(
							'REPLY' => array(
								'URL' => s_link('a', array($this->data['subdomain'], 12, '%d')) . '#reply',
								'ID' => 'post_id'
							)
						);
					}
					
					if ($this->auth['user'] && !$this->auth['mod']) {
						$this->msg->data['CONTROL']['report'] = array(
							'REPORT' => array(
								'URL' => s_link('a', array($this->data['subdomain'], 12, '%d', 'report')),
								'ID' => 'post_id'
							)
						);
					}
					
					if ($this->auth['mod']) {
						$this->msg->data['CONTROL']['auth'] = array();
						
						if ($this->auth['adm'] && $user->is('founder')) {
							$this->msg->data['CONTROL']['auth']['EDIT'] = array(
								'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'edit', 'id' => '%d')),
								'ID' => 'post_id'
							);
						}
						
						$this->msg->data['CONTROL']['auth']['DELETE'] = array(
							'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'aposts', 'manage' => 'delete', 'id' => '%d')),
							'ID' => 'post_id'
						);
					}
					
					$this->msg->view($start, 'ps', $this->data['posts'], $config['s_posts'], '', 'MSG_', '', false);
				}
				
				if ($this->data['a_active'] || $user->is('founder')) {
					if ($this->auth['post']) {
						if ($this->auth['user']) {
							_style('post_box', array('REF' => $comments_ref));
						} else {
							_style('no_guest_posting', array(
								'LEGEND' => sprintf($user->lang['UB_NO_GUEST_POSTING'], $this->data['name'], s_link('my', 'register'))
							));
						}
					} else {
						_style('no_post_auth');
						
						if ($this->auth['post_until']) {
							_style('no_post_auth.until', array('UNTIL_DATETIME' => $user->format_date($this->auth['post_until'])));
						}
					}
				}
				*/
				$sql = 'SELECT COUNT(user_id) AS fan_count
					FROM _artists_fav
					WHERE ub = ?
					ORDER BY joined DESC';
				$fan_count = sql_field(sql_filter($sql, $this->data['ub']), 'fan_count', 0);
				
				//
				// Make fans
				//
				if (!$this->auth['mod'] && !$this->auth['smod']) {
					_style('make_fans', array(
						'FAV_URL' => s_link('a', array($this->data['subdomain'], 15)),
						'FAV_LANG' => ($this->auth['fav']) ? '' : $user->lang['UB_FAV_ADD']) //$user->lang['UB_FAV_DEL']
					);
				}
				
				//
				// Set template
				//
				v_style(array(
					'INACTIVE' => !$this->data['a_active'],
					'UNAME' => $this->data['name'],
					'GENRE' => $this->data['genre'],
					'POSTS' => number_format($this->data['posts']),
					'VOTES' => number_format($this->data['votes']),
					'FANS' => $fan_count,
					'L_FANS' => ($fan_count == 1) ? $user->lang['FAN'] : $user->lang['FANS'], 
					
					'S_CONTROLPANEL' => $user->is('artist') ? s_link('control', '_' . $this->data['subdomain']) : '',
					'LOCATION' => ($this->data['local']) ? (($this->data['location'] != '') ? $this->data['location'] . ', ' : '') . 'Guatemala' : $this->data['location'])
				);
				
				$template->set_filenames(array(
					'a_body' => 'artists.' . $this->data['template'] . '.htm')
				);
				$template->assign_var_from_handle('UB_BODY', 'a_body');
				break;
		}
		
		return;
	}
	
	public function latest() {
		return true;
	}
	
	public function a_sidebar() {
		global $config;
		
		$sql = 'SELECT *
			FROM _artists
			ORDER BY RAND()';
		if ($row = sql_fieldrow($sql)) {
			$sql = 'SELECT *
				FROM _artists_images
				WHERE ub = ?
				ORDER BY RAND()';
			if ($row2 = sql_fieldrow(sql_filter($sql, $row['ub']))) {
				$row['rand_image'] = $row2['image'];
			}
			
			_style('random_a', array(
				'NAME' => $row['name'],
				'IMAGE' => $config['artists_url'] . ((isset($row['rand_image'])) ? $row['ub'] . '/thumbnails/' . $row['rand_image'] . '.jpg' : 'default/shadow.gif'),
				'URL' => s_link('a', $row['subdomain']),
				'LOCATION' => ($row['local']) ? 'Guatemala' : $row['location'],
				'GENRE' => $row['genre'])
			);
		}
		
		return;
	}

}

?>