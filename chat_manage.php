<?php
// -------------------------------------------------------------
// $Id: chat_manage.php,v 1.1 2006/03/22 23:58:21 Psychopsia Exp $
//
// STARTED   : Thr Dec 01, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');
require('./interfase/chat.php');

$user->init();
$user->setup('chat');

if (!$user->data['is_member'])
{
	do_login('LOGIN_TO_CHAT');
}

$mode = request_var('mode', '');
$submit = (isset($_POST['submit'])) ? TRUE : FALSE;
$error = array();

switch ($mode)
{
	case 'create':
		$template_vars = array();
		
		$ch_auth = 0;
		$ch_type = 1;
		$ch_cat = 2;
		
		if ($submit)
		{
			$ch_name = request_var('ch_name', '');
			$ch_desc = request_var('ch_desc', '');
			$ch_auth = request_var('ch_auth', 0);
			
			if ($user->data['is_founder'])
			{
				$ch_type = request_var('ch_type', 0);
				$ch_cat = request_var('ch_cat', 1);
			}
			
			if (empty($ch_name))
			{
				$error[] = 'CHAT_CREATE_EMPTY';
			}
			else if (!preg_match('#^([a-z0-9\-]+)$#is', $ch_name))
			{
				$error[] = 'CHAT_CREATE_INVALID_NAME';
			}
			
			$ch_int_name = strtolower($ch_name);
			
			if (!sizeof($error))
			{
				$sql = "SELECT *
					FROM _chat_ch
					WHERE ch_int_name = '" . $db->sql_escape($ch_int_name) . "'
						OR ch_name = '" . $db->sql_escape($ch_name) . "'";
				$result = $db->sql_query($sql);
				
				if ($row = $db->sql_fetchrow($result))
				{
					$error[] = 'CHAT_ALREADY_CREATED';
				}
				$db->sql_freeresult($result);
			}
			
			if (empty($ch_desc))
			{
				$error[] = 'CHAT_CREATE_EMPTY_DESC';
			}
			
			if (!sizeof($error))
			{
				$insert_data = array(
					'cat_id' => (int) $ch_cat,
					'ch_int_name' => $ch_int_name,
					'ch_name' => $ch_name,
					'ch_desc' => $ch_desc,
					'ch_founder' => (int) $user->data['user_id'],
					'ch_type' => (int) $ch_type,
					'ch_auth' => (int) $ch_auth,
					'ch_users' => 0,
					'ch_def' => 0,
					'ch_ip' => $user->ip,
					'ch_locked' => 0
				);
				
				$db->sql_query('INSERT INTO _chat_ch' . $db->sql_build_array('INSERT', $insert_data));
				
				redirect(s_link('chat', $ch_int_name));
			}
			else
			{
				$template_vars += array(
					'CH_NAME' => $ch_name,
					'CH_DESC' => $ch_desc
				);
			}
		} // IF $submit
		
		if ($user->data['is_founder'])
		{
			$chat = new _chat();
			
			if ($cat = $chat->get_cats())
			{
				$cat_list = '';
				foreach ($cat as $cat_data)
				{
					$cat_list .= '<option value="' . $cat_data['cat_id'] . '"' . (($cat_data['cat_id'] == $ch_cat) ? ' selected' : '') . '>' . $cat_data['cat_name'] . '</option>';
				}
				
				$template->assign_block_vars('select_cat', array(
					'CHAT_SELECT_CAT' => $cat_list)
				);
			} // IF $cat
			
			$type_ary = array('CHAT_CH_OFFICIAL', 'CHAT_CH_ALL');
			$type_list = '';
			foreach ($type_ary as $i => $langkey)
			{
				$type_list .= '<option value="' . $i . '"' . (($i == $ch_type) ? ' selected' : '') . '>' . $user->lang[$langkey] . '</option>';
			}
			
			$template->assign_block_vars('select_type', array(
				'CHAT_SELECT_TYPE' => $type_list)
			);
		}
		
		$select_auth = '';
		$auth_ary = array('CHAT_CH_ALL', /*'CHAT_CH_PRIVATE', */'FRIENDS');
		foreach ($auth_ary as $i => $langkey)
		{
			$select_auth .= '<option value="' . $i . '"' . (($i == $ch_auth) ? ' selected' : '') . '>' . $user->lang[$langkey] . '</option>';
		}
		
		$template_vars += array(
			'CHAT_SELECT_AUTH' => $select_auth,
			'S_ACTION' => s_link('chat-create')
		);
		
		if (sizeof($error))
		{
			$template->assign_block_vars('error', array(
				'MESSAGE' => parse_error($error))
			);
		}
		
		page_layout('CHAT_CREATE', 'chat_create', $template_vars);
		break;
}

redirect(s_link('chat'));

?>