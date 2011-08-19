<?php
// -------------------------------------------------------------
// $Id: merge.php,v 1.7 2006/08/24 02:34:54 Psychopsia Exp $
//
// STARTED   : Sat Nov 19, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$username = request_var('username', '');
$ip = request_var('ip', '');

if ($submit && ($username || $ip))
{
	if ($username)
	{
		$username_base = get_username_base($username);
		
		$sql = "SELECT m.username, l.*
			FROM _members m, _members_iplog l
			WHERE m.user_id = l.log_user_id
				AND m.username_base = '" . $db->sql_escape($username_base) . "'
			ORDER BY l.log_time DESC";
	}
	else if ($ip)
	{
		$sql = "SELECT m.username, l.*
			FROM _members m, _members_iplog l
			WHERE m.user_id = l.log_user_id
				AND l.log_ip = '" . $db->sql_escape($ip) . "'
			ORDER BY l.log_time DESC";
	}
	
	$result = $db->sql_query($sql);
	
	echo '<table border="1">
	<tr>
		<td>uid</td>
		<td>Usuario</td>
		<td>Inicio</td>
		<td>Fin</td>
		<td>Tiempo</td>
		<td>IP</td>
		<td>Agent</td>
	</tr>';
	
	while ($row = $db->sql_fetchrow($result))
	{
		echo '<tr>
	<td>' . $row['log_user_id'] . '</td>
	<td>' . $row['username'] . '</td>
	<td>' . $user->format_date($row['log_time']) . '</td>
	<td>' . (($row['log_endtime']) ? $user->format_date($row['log_endtime']) : '&nbsp;') . '</td>
	<td>' . (($row['log_endtime']) ? implode(' ', timeDiff($row['log_endtime'], $row['log_time'], true, 1)) : '&nbsp;') . '</td>
	<td>' . $row['log_ip'] . '</td>
	<td>' . $row['log_agent'] . '</td>
</tr>';
	}
	$db->sql_freeresult($result);
	
	echo '</table><br /><br />';
}

function timeDiff($timestamp, $now = 0, $detailed = false, $n = 0)
{
	// If the difference is positive "ago" - negative "away"
	if (!$now)
	{
		$now = time();
	}
	
	$action = ($timestamp >= $now) ? 'away' : 'ago';
	$diff = ($action == 'away' ? $timestamp - $now : $now - $timestamp);
	
	// Set the periods of time
	$periods = array('s', 'm', 'h', 'd', 's', 'm', 'a');
	$lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560);
	
	// Go from decades backwards to seconds
	$result = array();
	
	$i = sizeof($lengths);
	$time = '';
	while ($i >= $n)
	{
		$item = $lengths[$i - 1];
		if ($diff < $item)
		{
			$i--;
			continue;
		}
		
		$val = floor($diff / $item);
		$diff -= ($val * $item);
		$result[] = $val . $periods[($i - 1)];
		
		if (!$detailed)
		{
			$i = 0;
		}
		$i--;
	}
	
	return (count($result)) ? $result : false;
}

?><html>
<head>
<title>Log IPs</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Usuario: <input type="text" name="username" /><br /><br />
IP: <input type="text" name="ip" /><br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>