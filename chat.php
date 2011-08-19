<?php
// -------------------------------------------------------------
// $Id: chat.php,v 1.6 2006/03/23 00:00:23 Psychopsia Exp $
//
// STARTED   : Thr Dec 01, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');
require('./interfase/chat.php');

$keepalive = true;

$user->init();
$chat = new _chat();

if ($chat->_setup())
{
	$mode = request_var('mode', '');
	$csid = request_var('csid', '');
	
	$s_process = in_array($mode, array('logout', 'send', 'get'));
	
	if ($config['request_method'] == 'post' && !$s_process)
	{
		redirect(s_link('chat', $chat->data['ch_int_name']));
	}
	
	if (!$user->data['is_member'])
	{
		do_login('LOGIN_TO_CHAT');
	}
	
	if (!$chat->auth())
	{
		trigger_error('CHAT_NO_ACCESS');
	}
	
	$user->setup('chat');
	
	if ($s_process && $mode == 'logout')
	{
		return $chat->process_data($csid, $mode);
	}
	
	$chat->session($csid);
	
	if ($s_process)
	{
		return $chat->process_data($csid, $mode);
	}
	
//	$chat->sys_clean();
	$chat->window();
	
	$keepalive = false;
	$htmlpage = 'chat_channel';
	$page_title = $user->lang['CHAT'] . ' | ' . $chat->data['ch_name'];
}
else
{
	$cat = $chat->get_cats();
	
	if (!sizeof($cat))
	{
		trigger_error('NO_CHAT_CATS');
	}
	
	$user->setup('chat');
	
	$chat->sys_clean();
	$chatters = $chat->get_ch_listing($cat);
	
	$template->assign_vars(array(
		'CHATTERS' => $chatters,
		'CREATE_CHAT' => s_link('chat-create'))
	);
	
	//
	// SET TEMPLATE
	$page_title = 'CHAT';
	$htmlpage = 'chat_body';
	
	//
	// Load sidebar
	sidebar('artists', 'events');
}

page_layout($page_title, $htmlpage, false, $keepalive);

?>