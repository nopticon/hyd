<?php
// -------------------------------------------------------------
// $Id: radio.php,v 1.0 2008/03/09 21:37:00 Psychopsia Exp $
//
// STARTED   : Sun Mar 09, 2008
// COPYRIGHT : © 2008 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
include('./interfase/common.php');

$user->init();
$user->setup();

$sql = 'SELECT *
	FROM _radio
	ORDER BY show_day, show_start';
$result = $db->sql_query($sql);

$radio = array();
while ($row = $db->sql_fetchrow($result))
{
	$row['show_start'] = mktime(($row['show_start'] + $user->data['user_timezone'] + $user->data['user_dst']), 0, 0, 0, 0, 0);
	$row['show_end'] = mktime(($row['show_end'] + $user->data['user_timezone'] + $user->data['user_dst']), 0, 0, 0, 0, 0);
	
	$row['show_start'] = date('G', $row['show_start']);
	$row['show_end'] = date('G', $row['show_end']);
	
	if ((int) $row['show_end'] === 0)
	{
		$row['show_end'] = 24;
	}
	
	$radio[$row['show_day']][] = $row;
}
$db->sql_freeresult($result);

echo '<pre>';
print_r($radio);
echo '</pre>';
die();

$days = array(1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday');
$hours = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24);

foreach ($radio as $d => $row_day)
{
	$template->assign_block_vars('day', array(
		'V_NAME' => $user->lang['datetime'][$days[$d]])
	);
	
	foreach ($hours as $h)
	{
		
	}
	
	foreach ($row_day as $d2 => $row_show)
	{
		$template->assign_block_vars('day.row', array(
			'V_NAME' => $row_show['show_name']
		));
	}
}

page_layout('RADIO_INDEX', 'radio_body');

?>