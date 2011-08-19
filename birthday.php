<?php
// -------------------------------------------------------------
// $Id: art.php,v 1.6 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
include('./interfase/common.php');

$user->init();
$user->setup();

$cm = (int) date('m');

$sql = 'SELECT username, user_birthday
	FROM _members
	ORDER BY username_base';
$result = $db->sql_query($sql);

$u = array();
while ($row = $db->sql_fetchrow($result))
{
	$p = array(
		(int) substr($row['user_birthday'], 4, 2),
		(int) substr($row['user_birthday'], 6, 2),
		(int) substr($row['user_birthday'], 0, 4)
	);
	
	if ($cm != $p[0])
	{
		continue;
	}
	
	$u[$row['username']] = $p;
}
$db->sql_freeresult($result);

$a = $b = array();
$i = 0;
foreach ($u as $n => $d)
{
	$a[$i] = $d[1];
	$b[$i] = $n;
	$i++;
}

asort($a);
foreach ($a as $i => $d)
{
	echo $d . ' > :i' . $b[$i] . ':<br />';
}

?>