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
if ( !defined('IN_NUCLEO') )
{
	die('Rock Republik &copy; 2006');
}

class mm_promo
{
	var $data = array();
	
	function mm_promo ()
	{
		return;
	} // METHOD: mm_promo
	
	function submit_data ()
	{
		global $db, $userdata, $template, $user_ip, $lang;
		
		if (isset($_POST['submit']))
		{
			$error_msg = '';
			$message = '';
			
			$fields = preg_match_all('#\[field\](.*?)\[/field\]#si', $this->data['extended'], $field);
			$data = '';
			
			for ($i = 0; $i < $fields; $i++)
			{
				$no_field_error = TRUE;
				
				preg_match('/\[text\](.*?)\[\/text\]/si', $field[1][$i], $text);
				preg_match('/\[name\](.*?)\[\/name\]/si', $field[1][$i], $name);
				preg_match('/\[default\](.*?)\[\/default\]/si', $field[1][$i], $default);
				$req = strpos($field[1][$i], '[req]');
				
				$datafield = $_POST[$name[1]];
				
				if ((($datafield == '') || ($datafield == $default[1])) && $req)
				{
					$error_msg .= (($error_msg != '') ? '<br />' : '') . $lang['PROMO_FIELD_EMPTY'] . '<b>' . ucwords($text[1]) . '</b>';
					$no_field_error = FALSE;
				}
				else
				{
					if ($req && ($datafield != ''))
					{
						switch ($name[1])
						{
							case 'email':
								if (!preg_match("#(^|[\n ])([a-z0-9&\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)#ie", $datafield))
								{
									$error_msg .= (($error_msg != '') ? '<br />' : '') . $lang['Not_Email'];
									$no_field_error = FALSE;
								}
								break;
						}
					}
					
					if (strlen($datafield) > 5000)
					{
						$error_msg .= (($error_msg != '') ? '<br />' : '') . $lang['CHAT_MSG_TOO_LONG'];
						$no_field_error = FALSE;
					}
					
					if ($no_field_error)
					{
						$data .= '[field][name]' . $text[1] . '[/name][value]' . $datafield . '[/value][/field]';
					}
				} // IF
			} // FOR
			
			if (($userdata['user_id'] == GUEST) && !$userdata['session_logged_in'])
			{
				$result = $db->sql_query("SELECT MAX(datetime) AS last_datetime FROM " . MMT_UPR . " WHERE user_ip = '$user_ip'");
				
				if ($row = $db->sql_fetchrow($result))
				{
					if (intval($row['last_datetime']) > 0 && (time() - intval($row['last_datetime'])) < 60)
					{
						$error_msg .= (!empty($error_msg) ? '<br />' : '') . $lang['PROMO_FLOOD'];
					}
				}
				$db->sql_freeresult($result);
			}
			
			if ($error_msg != '')
			{
				die($error_msg);
			}
			else
			{
				$result = $db->sql_query("SELECT MAX(id) AS total FROM " . MMT_UPR);
				if ($row = $db->sql_fetchrow($result))
				{
					$post_id = $row['total'] + 1;
				}
				
				$db->sql_query("INSERT INTO " . MMT_UPR . " (id, promo, user_id, user_ip, datetime, data) VALUES ($post_id, " . $this->data['id'] . ", " . $userdata['user_id'] . ", '$user_ip', '" . time() . "', '" . $data . "')");
				
				$url = s_link('cover');
				
				meta_refresh(3, $url);
				trigger_error(sprintf($lang['PROMO_THANKS'], '<a href="' . $url . '">', '</a>'));
			}
			
			return;
		}
		
		redirect(s_link('cover'));
		
		return;
	} // METHOD: submit_data
	
	function show_promo ()
	{
		global $userdata, $db, $template, $lang;
		
		if (($userdata['user_id'] != GUEST) && $userdata['session_logged_in'])
		{
			$result = $db->sql_query("SELECT * FROM " . MMT_UPR . " WHERE promo = " . $this->data['id'] . " AND user_id = " . $userdata['user_id'] . " ORDER BY user_id");
			
			if ($row = $db->sql_fetchrow($result))
			{
				$url = s_link('cover');
				meta_refresh(3, $url);
				
				trigger_error(sprintf($lang['PROMO_CANT_VIEW'], '<a href="' . $url . '">', '</a>'));
			}
			$db->sql_freeresult($result);
		}
		
		if (isset($_POST['submit']))
		{
			$this->submit_data();
		}
		
		$fields = preg_match_all('#\[field\](.*?)\[/field\]#si', $this->data['extended'], $field);
		
		if ($fields)
		{
			$all_req = TRUE;
			
			for ($i = 0; $i < $fields; $i++)
			{
				preg_match('/\[type=(text|area)\]/si', $field[1][$i], $type);
				preg_match('/\[length=(.*?)\]/si', $field[1][$i], $length);
				preg_match('/\[name\](.*?)\[\/name\]/si', $field[1][$i], $name);
				preg_match('/\[default\](.*?)\[\/default\]/si', $field[1][$i], $default);
				preg_match('/\[text\](.*?)\[\/text\]/si', $field[1][$i], $text);
				preg_match('/\[description\](.*?)\[\/description\]/si', $field[1][$i], $description);
				$req = strpos($field[1][$i], '[req]');
				
				if (!$req && $all_req)
				{
					$all_req = FALSE;
				}
				
				switch ($type[1])
				{
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
				
				if ($description[1] != '')
				{
					$template->assign_block_vars('field.description', array(
						'TEXT' => $description[1]
					));
				}
			} // FOR
		} // IF
		
		$template->assign_vars(array(
			'PROMO_TITLE' => $this->data['title'],
			'PROMO_DESC' => nl2br($this->data['text']),
			'IMAGE' => 'data/images/promo/' . $this->data['image'],
			
			'S_HIDDEN' => $s_hidden,
			
			'L_FILL_FORM' => ($all_req) ? $lang['PROMO_AREQD_FIELDS'] : $lang['PROMO_REQD_FIELDS']
		));
	} // METHOD: show_promo
	
} // CLASS: mm_promo

?>