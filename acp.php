<?php
// -------------------------------------------------------------
// $Id: acp.php,v 1.0 2008/01/07 02:21:00 Psychopsia Exp $
//
// STARTED   : Mon Jan 07, 2007
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup('control');

if (!$user->data['is_member'])
{
	if ($user->data['is_bot'])
	{
		redirect(s_link());
	}
	do_login();
}

if (!$user->_team_auth('all'))
{
	fatal_error();
}

$module = request_var('module', '');
if (empty($module) || !preg_match('#[a-z\_]+#i', $module))
{
	fatal_error();
}

$filepath = ROOT . 'interfase/acp/' . $module . '.php';
if (!@file_exists($filepath))
{
	fatal_error();
}

$submit = isset($_POST['submit']);
$u = s_link('acp', $module);

include($filepath);

// Functions
function _auth($a)
{
	global $user;
	
	if (!$user->_team_auth($a))
	{
		fatal_error();
	}
}

?>