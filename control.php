<?php
// -------------------------------------------------------------
// $Id: control.php,v 1.8 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Sat Dec 17, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();

//
// Check if member is logged in
//
if (!$user->data['is_member'])
{
	if ($user->data['is_bot'])
	{
		redirect(s_link());
	}
	do_login();
}

if (!$user->data['user_auth_control'])
{
	fatal_error();
}

//
// Start control
//
require('./interfase/control.php');
$control = new control(request_var('module', ''));

$user->setup('control');

if (!empty($control->module_path))
{
	require('./control/common.php');
	require($control->module_path);
	
	kernel_function('c', $control->module);
	
	$control->set_nav(false, $control->module);
	
	$module = new $control->module();
	$module->import_control();
	
	$module->mode = $control->get_var('mode', '');
	$module->manage = $control->get_var('manage', '');
	
	$module->check_manage();
	$module->check_method();
	
	if (!($module->auth_access($user->data)))
	{
		redirect(s_link('control'));
	}
	
	kernel_function('m', $module, $module->mode);
	
	$module->auth = $auth;
	$module->{$module->mode}();
	$module->export_control();
	
	$control->htmlfile = $control->module;
	
	$template_vars = array(
		'MODE' => $module->mode,
		'MANAGE' => $module->manage,
	);
}
else
{
	$control->panel();
}

//
// Output template
//
$page_title = $user->lang['CONTROL_PANEL'];
if ($control->module != '')
{
	$page_title .= ' | ' . $user->lang['CONTROL_' . strtoupper($control->module)];
}

if (!isset($template_vars))
{
	$template_vars = array();
}
$template_vars += array(
	'TOP_NAV' => $control->display_nav()
);

page_layout($page_title, 'control/' . $control->htmlfile, $template_vars);

?>