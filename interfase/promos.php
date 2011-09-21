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

class promos {
	public $data = array();
	
	public function __construct() {
		return;
	}
	
	function submit_data() {
		global $userdata, $template, $user_ip, $lang;
		
		if (isset($_POST['submit'])) {
			$error_msg = '';
			$message = '';
			
			$fields = preg_match_all('#\[field\](.*?)\[/field\]#si', $this->data['extended'], $field);
			$data = '';
			
			for ($i = 0; $i < $fields; $i++) {
				$no_field_error = true;
				
				preg_match('/\[text\](.*?)\[\/text\]/si', $field[1][$i], $text);
				preg_match('/\[name\](.*?)\[\/name\]/si', $field[1][$i], $name);
				preg_match('/\[default\](.*?)\[\/default\]/si', $field[1][$i], $default);
				$req = strpos($field[1][$i], '[req]');
				
				$datafield = $_POST[$name[1]];
				
				if ((($datafield == '') || ($datafield == $default[1])) && $req) {
					$error_msg .= (($error_msg != '') ? '<br />' : '') . $lang['PROMO_FIELD_EMPTY'] . '<b>' . ucwords($text[1]) . '</b>';
					$no_field_error = false;
				} else {
					if ($req && ($datafield != '')) {
						switch ($name[1]) {
							case 'email':
								if (!preg_match("#(^|[\n ])([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)#ie", $datafield)) {
									$error_msg .= (($error_msg != '') ? '<br />' : '') . $lang['Not_Email'];
									$no_field_error = false;
								}
								break;
						}
					}
					
					if (strlen($datafield) > 5000) {
						$error_msg .= (($error_msg != '') ? '<br />' : '') . $lang['CHAT_MSG_TOO_LONG'];
						$no_field_error = false;
					}
					
					if ($no_field_error) {
						$data .= '[field][name]' . $text[1] . '[/name][value]' . $datafield . '[/value][/field]';
					}
				}
			}
			
			if (($userdata['user_id'] == GUEST) && !$userdata['session_logged_in']) {
				$sql = 'SELECT MAX(datetime) AS last_datetime
					FROM _promos
					WHERE user_ip = ?';
				if ($last_datetime = sql_field(sql_filter($sql, $user_ip), 'last_datetime', 0)) {
					if (intval($last_datetime) > 0 && (time() - intval($last_datetime)) < 60) {
						$error_msg .= (!empty($error_msg) ? '<br />' : '') . $lang['PROMO_FLOOD'];
					}
				}
			}
			
			if ($error_msg != '') {
				die($error_msg);
			}
			
			$sql_insert = array(
				'promo' => $this->data['id'],
				'user_id' => $userdata['user_id'],
				'user_ip' => $user_ip,
				'datetime' => time(),
				'data' => $data
			);
			$sql = 'INSERT INTO _promos_users' . sql_build('INSERT', $sql_insert);
			sql_query($sql);
			
			$url = s_link('cover');
			meta_refresh(3, $url);
			
			trigger_error(sprintf($lang['PROMO_THANKS'], '<a href="' . $url . '">', '</a>'));
			
			return;
		}
		
		redirect(s_link('cover'));
		
		return;
	}
	
	public function show_promo() {
		global $userdata, $template, $lang;
		
		if (($userdata['user_id'] != GUEST) && $userdata['session_logged_in']) {
			$sql = 'SELECT *
				FROM _promos_users
				WHERE promo = ?
					AND user_id = ?
				ORDER BY user_id';
			if ($row = sql_fieldrow(sql_filter($sql, $this->data['id'], $userdata['user_id']))) {
				$url = s_link('cover');
				meta_refresh(3, $url);
				
				trigger_error(sprintf($lang['PROMO_CANT_VIEW'], '<a href="' . $url . '">', '</a>'));
			}
		}
		
		if (isset($_POST['submit'])) {
			$this->submit_data();
		}
		
		$fields = preg_match_all('#\[field\](.*?)\[/field\]#si', $this->data['extended'], $field);
		
		if ($fields) {
			$all_req = true;
			
			for ($i = 0; $i < $fields; $i++) {
				preg_match('/\[type=(text|area)\]/si', $field[1][$i], $type);
				preg_match('/\[length=(.*?)\]/si', $field[1][$i], $length);
				preg_match('/\[name\](.*?)\[\/name\]/si', $field[1][$i], $name);
				preg_match('/\[default\](.*?)\[\/default\]/si', $field[1][$i], $default);
				preg_match('/\[text\](.*?)\[\/text\]/si', $field[1][$i], $text);
				preg_match('/\[description\](.*?)\[\/description\]/si', $field[1][$i], $description);
				$req = strpos($field[1][$i], '[req]');
				
				if (!$req && $all_req) {
					$all_req = false;
				}
				
				switch ($type[1]) {
					case 'area':
						$default = (isset($_POST[$name[1]]) ? $_POST[$name[1]] : (($default[1] != '') ? $default[1] : ''));
						$input = '<textarea name="' . $name[1] . '" rows="10" style="width:100%">' . $default .'</textarea>';
						break;
					case 'text':
					default:
						$default = (isset($_POST[$name[1]]) ? $_POST[$name[1]] : (($default[1] != '') ? $default[1] : ''));
						$input = '<input type="text" name="' . $name[1] . '"' . (($default != '') ? ' value="' . $default . '"' : '') . ' maxlength="' . (($length[1] != '') ? $length[1] : 255) . '" style="width:100%">';
						break;
				}
				
				$template->assign_block_vars('field', array(
					'TEXT' => $text[1] . ': ' . (($req) ? '*' : ''),
					'FIELD' => $input
				));
				
				if ($description[1] != '') {
					$template->assign_block_vars('field.description', array(
						'TEXT' => $description[1])
					);
				}
			}
		}
		
		$template->assign_vars(array(
			'PROMO_TITLE' => $this->data['title'],
			'PROMO_DESC' => nl2br($this->data['text']),
			'IMAGE' => 'data/images/promo/' . $this->data['image'],
			
			'S_HIDDEN' => $s_hidden,
			
			'L_FILL_FORM' => ($all_req) ? $lang['PROMO_AREQD_FIELDS'] : $lang['PROMO_REQD_FIELDS'])
		);
	}
}

?>