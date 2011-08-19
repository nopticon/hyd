<?php
// -------------------------------------------------------------
// $Id: subscribe.php,v 1.3 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Wed Nov 17, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

if ($user->data['is_member'])
{
	redirect(s_link('my', 'profile'));
}
else if ($user->data['is_bot'])
{
	redirect(s_link());
}

//
$mode = request_var('mode', '');
$code_invite = request_var('invite', '');
if ($mode == 'created')
{
	trigger_error('MEMBERSHIP_ADDED');
}

//
// Set vars
//
$v_fields = array();
$fields = array(
	'username' => '',
	'email' => '',
	'key' => '',
	'key_confirm' => '',
	'gender' => 0,
	'birthday_month' => 0,
	'birthday_day' => 0,
	'birthday_year' => 0,
	'country' => 0,
	'tos' => 0,
	'refop' => 0,
	'refby' => ''
);
foreach ($fields as $k => $v)
{
	$v_fields[$k] = $v;
}

if (!empty($code_invite))
{
	$sql = "SELECT i.invite_email, m.user_email
		FROM _members_ref_invite i, _members m
		WHERE i.invite_code = '" . $db->sql_escape($code_invite) . "'
			AND i.invite_uid = m.user_id";
	$result = $db->sql_query($sql);
	
	if (!$code_invite_row = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$v_fields['refop'] = 1;
	$v_fields['refby'] = $code_invite_row['user_email'];
	$v_fields['email'] = $code_invite_row['invite_email'];
	unset($code_invite_row);
}

//
// If the user submitted the form
//
$error = array();

if (isset($_POST['submit']))
{
	include('./interfase/functions_validate.php');
	
	foreach ($fields as $k => $v)
	{
		$v_fields[$k] = request_var($k, $v);
	}
	
	//
	if (empty($v_fields['username']))
	{
		$error['username'] = 'EMPTY_USERNAME';
	}
	else 
	{
		$len_username = strlen($v_fields['username']);
		if (($len_username < 2) || ($len_username > 20) || !get_username_base($v_fields['username'], true))
		{
			$error['username'] = 'USERNAME_INVALID';
		}
		
		if (!sizeof($error))
		{
			$result = validate_username($v_fields['username']);
			if ($result['error'])
			{
				$error['username'] = $result['error_msg'];
			}
		}
		
		if (!sizeof($error))
		{
			$v_fields['username_base'] = get_username_base($v_fields['username']);
			
			$sql = "SELECT *
				FROM _members
				WHERE username_base = '" . $db->sql_escape($v_fields['username_base']) . "'";
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$error['username'] = 'USERNAME_TAKEN';
			}
			$db->sql_freeresult($result);
		}
		
		if (!sizeof($error))
		{
			$sql = "SELECT *
				FROM _artists
				WHERE subdomain = '" . $db->sql_escape($v_fields['username_base']) . "'";
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$error['username'] = 'USERNAME_TAKEN';
			}
			$db->sql_freeresult($result);
		}
	}
	
	if (!empty($v_fields['email']))
	{
		$result = validate_email($v_fields['email']);
		if ($result['error'])
		{
			$error['email'] = $result['error_msg'];
		}
	}
	else
	{
		$error['email'] = 'EMPTY_EMAIL';
	}
	
	if (!empty($v_fields['key']) && !empty($v_fields['key_confirm']))
	{
		if ($v_fields['key'] != $v_fields['key_confirm'])
		{
			$error['key'] = 'PASSWORD_MISMATCH';
		}
		else if (strlen($v_fields['key']) > 32)
		{
			$error['key'] = 'PASSWORD_LONG';
		}
	}
	else
	{
		if (empty($v_fields['key']))
		{
			$error['key'] = 'EMPTY_PASSWORD';
		}
		elseif (empty($v_fields['key_confirm']))
		{
			$error['key_confirm'] = 'EMPTY_PASSWORD_CONFIRM';
		}
	}
	
	if (!$v_fields['birthday_month'] || !$v_fields['birthday_day'] || !$v_fields['birthday_year'])
	{
		$error['birthday'] = 'EMPTY_BIRTH_MONTH';
	}
	
	if (!$v_fields['tos'])
	{
		$error['tos'] = 'AGREETOS_ERROR';
	}
	
	if (!sizeof($error))
	{
		$v_fields['birthday'] = leading_zero($v_fields['birthday_year']) . leading_zero($v_fields['birthday_month']) . leading_zero($v_fields['birthday_day']);
		
		$member_data = array(
			'user_type' => USER_INACTIVE,
			'user_active' => 1,
			'username' => $v_fields['username'],
			'username_base' => $v_fields['username_base'],
			'user_password' => user_password($v_fields['key']),
			'user_regip' => $user->ip,
			'user_session_time' => 0,
			'user_lastpage' => '',
			'user_lastvisit' => $user->time,
			'user_regdate' => $user->time,
			'user_level' => 0,
			'user_posts' => 0,
			'userpage_posts' => 0,
			'user_points' => 0,
			'user_color' => '4D5358',
			'user_timezone' => $config['board_timezone'],
			'user_dst' => $config['board_dst'],
			'user_lang' => $config['default_lang'],
			'user_dateformat' => $config['default_dateformat'],
			'user_country' => (int) $v_fields['country'],
			'user_rank' => 0,
			'user_avatar' => '',
			'user_avatar_type' => 0,
			'user_email' => $v_fields['email'],
			'user_lastlogon' => 0,
			'user_totaltime' => 0,
			'user_totallogon' => 0,
			'user_totalpages' => 0,
			'user_gender' => $v_fields['gender'],
			'user_birthday' => (string) $v_fields['birthday'],
			'user_mark_items' => 0,
			'user_topic_order' => 0,
			'user_email_dc' => 1
		);
		$db->sql_query('INSERT INTO _members' . $db->sql_build_array('INSERT', $member_data));
		$user_id = $db->sql_nextid();
		
		set_config('max_users', $config['max_users'] + 1);
		
		// Confirmation code
		$verification_code = md5(unique_id());
		
		$insert = array(
			'crypt_userid' => $user_id,
			'crypt_code' => $verification_code,
			'crypt_time' => $user->time
		);
		$sql = 'INSERT INTO _crypt_confirm' . $db->sql_build_array('INSERT', $insert);
		$db->sql_query($sql);
		
		// Emailer
		require('./interfase/emailer.php');
		$emailer = new emailer();
		
		// Pending points
		if ($v_fields['refop'] == 1 && !empty($v_fields['refby']) && !preg_match('/^[a-z0-9&\'\.\-_\+]+@[a-z0-9\-]+\.([a-z0-9\-]+\.)*?[a-z]+$/is', $v_fields['refby']))
		{
			$v_fields['refby'] = '';
		}
		
		if ($v_fields['refop'] == 1 && !empty($v_fields['refby']))
		{
			$sql = "SELECT *
				FROM _members
				WHERE user_email = '" . $db->sql_escape($v_fields['refby']) . "'";
			$result = $db->sql_query($sql);
			
			$send_invite = true;
			if ($ref_friend = $db->sql_fetchrow($result))
			{
				$send_invite = false;
				
				$sql = 'INSERT INTO _members_ref_assoc (ref_uid, ref_orig)
					VALUES (' . (int) $user_id . ', ' . (int) $ref_friend['user_id'] . ')';
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result);
			
			if ($send_invite)
			{
				$invite_user = explode('@', $v_fields['refby']);
				$invite_code = substr(md5(unique_id()), 0, 6);
				
				$sql = "INSERT INTO _members_ref_invite (invite_code, invite_email, invite_uid)
					VALUES ('" . $db->sql_escape($invite_code) . "', '" . $db->sql_escape($v_fields['refby']) . "', " . (int) $user_id . ")";
				$db->sql_query($sql);
				
				$emailer->from('info@rockrepublik.net');
				$emailer->use_template('user_invite');
				$emailer->email_address($v_fields['refby']);
				
				$emailer->assign_vars(array(
					'INVITED' => $invite_user[0],
					'USERNAME' => $v_fields['username'],
					'U_REGISTER' => s_link('my', array('register', 'a', $invite_code)))
				);
				$emailer->send();
				$emailer->reset();
			}
		}
		
		// Update ref
		$sql = "UPDATE _members
			SET user_refop = " . (int) $v_fields['refop'] . ", user_refby = '" . $db->sql_escape($v_fields['refby']) . "'
			WHERE user_id = " . (int) $user_id;
		$db->sql_query($sql);
		
		// Send confirm email
		$emailer->from('info@rockrepublik.net');
		$emailer->use_template('user_welcome');
		$emailer->email_address($v_fields['email']);
		
		$emailer->assign_vars(array(
			'USERNAME' => $v_fields['username'],
			'U_ACTIVATE' => s_link('my', array('confirm', $verification_code)))
		);
		$emailer->send();
		$emailer->reset();
		
		redirect(s_link('my', array('register', 'created')));
	}
}

//
// Form
//
if (!$members_refop = $cache->get('members_refop'))
{
	$sql = 'SELECT *
		FROM _members_ref_options
		ORDER BY option_order';
	$result = $db->sql_query($sql);
	
	$members_refop = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$members_refop[$row['option_id']] = $row;
	}
	$db->sql_freeresult($result);
	
	$cache->save('members_refop', $members_refop);
}

if (!$country = $cache->get('country'))
{
	$sql = 'SELECT *
		FROM _countries
		ORDER BY country_name';
	$result = $db->sql_query($sql);
	
	$country = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$country[$row['country_id']] = $row;
	}
	$db->sql_freeresult($result);
	
	$cache->save('country', $country);
}

//
// GeoIP
//
include('./interfase/geoip.php');
$gi = geoip_open("./interfase/GeoIP.dat", GEOIP_STANDARD);

$geoip_code = strtolower(geoip_country_code_by_addr($gi, $user->ip));

$country_codes = array();
foreach ($country as $item)
{
	$country_codes[$item['country_short']] = $item['country_id'];
}

$v_fields['country'] = ($v_fields['country']) ? $v_fields['country'] : ((isset($country_codes[$geoip_code])) ? $country_codes[$geoip_code] : $country_codes['gt']);
foreach ($country as $item)
{
	$template->assign_block_vars('country', array(
		'OPTION_ID' => $item['country_id'],
		'OPTION_NAME' => $item['country_name'],
		'OPTION_S' => ($v_fields['country'] == $item['country_id']))
	);
}

$v_fields['refop'] = ($v_fields['refop']) ? $v_fields['refop'] : 1;
foreach ($members_refop as $item)
{
	$template->assign_block_vars('refop', array(
		'OPTION_ID' => $item['option_id'],
		'OPTION_NAME' => $item['option_name'],
		'OPTION_S' => ($v_fields['refop'] == $item['option_id']))
	);
}

if (sizeof($error))
{
	$template->assign_block_vars('error', array(
		'MESSAGE' => parse_error($error))
	);
}

foreach ($user->lang['MEMBERSHIP_BENEFITS2'] as $item)
{
	$template->assign_block_vars('list_benefits', array(
		'ITEM' => $item)
	);
}

$s_genres_select = '';
$genres = array(1 => 'MALE', 2 => 'FEMALE');
foreach ($genres as $id => $value)
{
	$s_genres_select .= '<option value="' . $id . '"' . (($v_fields['gender'] == $id) ? ' selected="true"' : '') . '>' . $user->lang[$value] . '</option>';
}

$s_bday_select = '<option value="">&nbsp;</option>';
for ($i = 1; $i < 32; $i++)
{
	$s_bday_select .= '<option value="' . $i . '"' . (($v_fields['birthday_day'] == $i) ? 'selected="true"' : '') . '>' . $i . '</option>';
}

$s_bmonth_select = '<option value="">&nbsp;</option>';
$months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
foreach ($months as $id => $value)
{
	$s_bmonth_select .= '<option value="' . $id . '"' . (($v_fields['birthday_month'] == $id) ? ' selected="true"' : '') . '>' . $user->lang['datetime'][$value] . '</option>';
}

$s_byear_select = '<option value="">&nbsp;</option>';
for ($i = 2005; $i > 1899; $i--)
{
	$s_byear_select .= '<option value="' . $i . '"' . (($v_fields['birthday_year'] == $i) ? ' selected="true"' : '') . '>' . $i . '</option>';
}

$tv = array(
	'U_ACTION' => s_link('my', 'register'),
	
	'V_USERNAME' => $v_fields['username'],
	'V_KEY' => $v_fields['key'],
	'V_KEY_CONFIRM' => $v_fields['key_confirm'],
	'V_EMAIL' => $v_fields['email'],
	'V_REFBY' => $v_fields['refby'],
	'V_GENDER' => $s_genres_select,
	'V_BIRTHDAY_DAY' => $s_bday_select,
	'V_BIRTHDAY_MONTH' => $s_bmonth_select,
	'V_BIRTHDAY_YEAR' => $s_byear_select,
	'V_TOS' => ($v_fields['tos']) ? ' checked="true"' : ''
);

if (isset($error['birthday']))
{
	$fields['birthday'] = true;
}

foreach ($fields as $k => $v)
{
	$tv['E_' . strtoupper($k)] = (isset($error[$k])) ? true : false;
}

page_layout('NEW_ACCOUNT_SUBJECT', 'subscribe_body', $tv);

//
// FUNCTIONS
//
function leading_zero ($number)
{
	return (($number < 10) ? '0' : '') . $number;
}

?>