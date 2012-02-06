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

require_once(ROOT . 'interfase/comments.php');

class community {
	private $comments;
	
	public function __construct() {
		$this->comments = new _comments();
		
		return;
	}
	
	public function run() {
		global $config, $user;
		
		$this->founders();
		$this->team();
		$this->recent_members();
		$this->birthdays();
		
		v_style(array(
			'MEMBERS_COUNT' => number_format($config['max_users']))
		);
		
		//
		// Online
		//
		$sql = 'SELECT u.user_id, u.username, u.username_base, u.user_type, u.user_hideuser, u.user_color, s.session_ip
			FROM _members u, _sessions s
			WHERE s.session_time >= ?
				AND u.user_id = s.session_user_id
			ORDER BY u.username ASC, s.session_ip ASC';
		$this->online(sql_filter($sql, ($user->time - (5 * 60))), 'online', 'MEMBERS_ONLINE');
		
		//
		// Today Online
		//
		$minutes = date('is', time());
		$timetoday = (time() - (60 * intval($minutes[0].$minutes[1])) - intval($minutes[2].$minutes[3])) - (3600 * $user->format_date(time(), 'H'));
		
		$sql = 'SELECT user_id, username, username_base, user_color, user_hideuser, user_type
			FROM _members
			WHERE user_type NOT IN (' . USER_IGNORE . ', ' . USER_INACTIVE . ')
				AND user_lastvisit >= ?
				AND user_lastvisit < ? 
			ORDER BY username';
		$this->online(sql_filter($sql, $timetoday, ($timetoday + 86399)), 'online', 'MEMBERS_TODAY', 'MEMBERS_VISIBLE');
		
		return true;
	}
	
	public function founders() {
		global $cache, $user, $template;
		
		if (!$founders = $cache->get('founders')) {
			$sql = 'SELECT user_id, username, username_base, user_avatar
				FROM _members
				WHERE user_type = ?
				ORDER BY user_id';
			$result = sql_rowset(sql_filter($sql, USER_FOUNDER));
			
			$founders = array();
			foreach ($result as $row) {
				if ($row['username_base'] == 'rockrepublik') continue;
				
				$founders[$row['user_id']] = $this->comments->user_profile($row);
			}
			
			$cache->save('founders', $founders);
		}
		
		foreach ($founders as $user_id => $data) {
			_style('founders', array(
				'REALNAME' => $data['username'],
				'USERNAME' => $data['username'],
				'AVATAR' => $data['user_avatar'],
				'PROFILE' => $data['profile'])
			);
		}
	}
	
	public function team() {
		global $cache, $template;
		
		if (!$team = $cache->get('team')) {
			$sql = 'SELECT *
				FROM _team
				ORDER BY team_order';
			if ($team = sql_rowset($sql)) {
				$cache->save('team', $team);
			}
		}
		
		if (!$team_members = $cache->get('team_members')) {
			$sql = 'SELECT t.*
				FROM _team_members t, _members m
				WHERE t.member_id = m.user_id
				ORDER BY m.username';
			if ($team_members = sql_rowset($sql)) {
				$cache->save('team_members', $team_members);
			}
		}
		
		if (!sizeof($team) || !sizeof($team_members)) {
			return;
		}
		
		$sql_members = array();
		foreach ($team_members as $data) {
			$sql_members[] = $data['member_id'];
		}
		
		//
		$sql = 'SELECT user_id, username, username_base, user_color, user_avatar
			FROM _members
			WHERE user_id IN (??)
			ORDER BY user_id';
		$members_data = sql_rowset(sql_filter($sql, implode(',', $sql_members)), 'user_id');
		
		foreach ($team as $t_data) {
			if (!$t_data['team_show']) {
				continue;
			}
			
			_style('team', array(
				'TEAM_NAME' => $t_data['team_name'])
			);
			
			$tcol = 0;
			foreach ($team_members as $tm_data) {
				if ($t_data['team_id'] != $tm_data['team_id']) continue;
				
				if (!$tcol) _style('team.row');
				
				$up = $this->comments->user_profile($members_data[$tm_data['member_id']]);
				
				_style('team.row.member', array(
					'MOD' => ($tm_data['member_id'] == $t_data['team_mod']),
					'USERNAME' => $up['username'],
					'REALNAME' => $tm_data['real_name'],
					'PROFILE' => $up['profile'],
					'COLOR' => $up['user_color'],
					'AVATAR' => $up['user_avatar'])
				);
				
				$tcol = ($tcol == 2) ? 0 : $tcol + 1;
			}
		}
		
		return;
	}
	
	public function online($sql, $block, $block_title, $unset_legend = false) {
		global $user, $template;
		static $user_bots;
		
		if (!isset($user_bots)) {
			$bots = array();
			obtain_bots($bots);
			foreach ($bots as $row) {
				$user_bots[$row['user_id']] = true;
			}
		}
		
		foreach (array('last_user_id' => 0, 'users_visible' => 0, 'users_hidden' => 0, 'users_guests' => 0, 'users_bots' => 0, 'last_ip' => '', 'users_online' => 0) as $k => $v) {
			$$k = $v;
		}
		
		_style($block, array('L_TITLE' => $user->lang[$block_title]));
		_style($block . '.members', array());
		
		$is_founder = $user->is('founder');
		$result = sql_rowset($sql);
		
		foreach ($result as $row) {
			if ($row['user_id'] != GUEST) {
				if ($row['user_id'] != $last_user_id) {
					$is_bot = isset($user_bots[$row['user_id']]);
					
					if (!$row['user_hideuser']) {
						$username = $row['username'];
						
						if ($is_bot) {
							$users_bots++;
						} else {
							$users_visible++;
						}
					} else {
						$username = '*' . $row['username'];
						$users_hidden++;
					}
					
					if (((!$row['user_hideuser'] || $is_founder) && !$is_bot) || ($is_bot && $is_founder)) {
						_style($block . '.members.item', array(
							'USERNAME' => $username,
							'PROFILE' => s_link('m', $row['username_base']),
							'USER_COLOR' =>  $row['user_color'])
						);
					}
				}
				
				$last_user_id = $row['user_id'];
			} else {
				if ($row['session_ip'] != $last_ip) {
					$users_guests++;
				}
				
				$last_ip = $row['session_ip'];
			}
		}
		
		$users_total = (int) $users_visible + $users_hidden + $users_guests + $users_bots;
		
		if (!($users_visible + $users_hidden) || (!$users_visible && $users_hidden)) {
			_style($block . '.members.none');
		}
		
		/*if (!$users_visible) {
			_style($block . '.members.none', array());
		}*/
		
		_style($block . '.legend');
		
		$online_ary = array(
			'MEMBERS_TOTAL' => $users_total,
			'MEMBERS_VISIBLE' => $users_visible,
			'MEMBERS_GUESTS' => $users_guests,
			'MEMBERS_HIDDEN' => $users_hidden,
			'MEMBERS_BOT' => $users_bots
		);
		
		if ($unset_legend !== false) {
			unset($online_ary[$unset_legend]);
		}
		
		foreach ($online_ary as $lk => $vk) {
			if (!$vk && $lk != 'MEMBERS_TOTAL') {
				continue;
			}
			
			_style($block . '.legend.item', array(
				'L_MEMBERS' => $user->lang[$lk . (($vk != 1) ? '2' : '')],
				'ONLINE_VALUE' => $vk)
			);
		}
	}
	
	public function birthdays() {
		global $template;
		
		//$last_year = time() - (31536000 * 5);
		
		$sql = "SELECT user_id, username, username_base, user_color, user_avatar
			FROM _members
			WHERE user_birthday LIKE ?
				AND user_type NOT IN (??, ??)
			ORDER BY user_posts DESC, username";
		//if (!$result = sql_rowset(sql_filter($sql, '%' . date('md'), USER_INACTIVE, USER_IGNORE), $last_year)) {
		if (!$result = sql_rowset(sql_filter($sql, '%' . date('md'), USER_INACTIVE, USER_IGNORE))) {
			return false;
		}
		
		foreach ($result as $i => $row) {
			if (!$i) _style('birthday');
			
			$profile = $this->comments->user_profile($row);
			
			_style('birthday.row', array(
				'USERNAME' => $profile['username'],
				'PROFILE' => $profile['profile'],
				'COLOR' => $profile['user_color'],
				'AVATAR' => $profile['user_avatar'])
			);
		}

		return true;
	}
	
	public function recent_members() {
		global $user, $template;
		
		$sql = 'SELECT username, username_base, user_color
			FROM _members
			WHERE user_type NOT IN (??, ??)
			ORDER BY user_regdate DESC
			LIMIT 10';
		$result = sql_rowset(sql_filter($sql, USER_INACTIVE, USER_IGNORE));
		
		foreach ($result as $i => $row) {
			if (!$i) _style('recent_members');
			
			_style('recent_members.item', array(
				'USERNAME' => $row['username'],
				'USER_COLOR' => $row['user_color'],
				'PROFILE' => s_link('m', $row['username_base']))
			);
		}
		
		return true;
	}
}

?>