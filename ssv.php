<?php
// -------------------------------------------------------------
// $Id: ssv.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Thu Jun 08, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$lang = array(
	'SERVER_UPTIME' => 'Server Uptime: %s day(s) %s hour(s) %s minute(s)',
	'SERVER_LOAD' => 'Average Load: %s'
);

$uptime = @exec('uptime');
if ( strstr($uptime, 'day') )
{
	if ( strstr($uptime, 'min') )
	{
		preg_match("/up\s+(\d+)\s+(days,|days|day,|day)\s+(\d{1,2})\s+min/", $uptime, $times);
		$days = $times[1];
		$hours = 0;
		$mins = $times[3];
	}
	else
	{
		preg_match("/up\s+(\d+)\s+(days,|days|day,|day)\s+(\d{1,2}):(\d{1,2}),/", $uptime, $times);
		$days = $times[1];
		$hours = $times[3];
		$mins = $times[4];
	}
}
else
{
	if ( strstr($uptime, 'min') )
	{
		preg_match("/up\s+(\d{1,2})\s+min/", $uptime, $times);
		$days = 0;
		$hours = 0;
		$mins = $times[1];
	}
	else
	{
		preg_match("/up\s+(\d+):(\d+),/", $uptime, $times);
		$days = 0;
		$hours = $times[1];
		$mins = $times[2];
	}
}
preg_match("/averages?: ([0-9\.]+),[\s]+([0-9\.]+),[\s]+([0-9\.]+)/", $uptime, $avgs);
$load = $avgs[1].", ".$avgs[2].", ".$avgs[3]."";

$template_vars = array(
	'SERVER_UPTIME' => sprintf($lang['SERVER_UPTIME'], $days, $hours, $mins),
	'SERVER_LOAD' => sprintf($lang['SERVER_LOAD'], $load)
);

page_layout('HOME', 'ssv_body', $template_vars, false);

?>