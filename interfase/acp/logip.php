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
if (!defined('IN_NUCLEO')) exit;

_auth('founder');

$username = request_var('username', '');
$ip = request_var('ip', '');

if ($submit && ($username || $ip))
{
	if ($username)
	{
		$username_base = get_username_base($username);
		
		$sql = 'SELECT m.username, l.*
			FROM _members m, _members_iplog l
			WHERE m.user_id = l.log_user_id
				AND m.username_base = ?
			ORDER BY l.log_time DESC';
		$sql = sql_filter($sql, $username_base);
	}
	else if ($ip)
	{
		$sql = 'SELECT m.username, l.*
			FROM _members m, _members_iplog l
			WHERE m.user_id = l.log_user_id
				AND l.log_ip = ?
			ORDER BY l.log_time DESC';
		$sql = sql_filter($sql, $ip);
	}
	$result = sql_rowset($sql);
	
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
	
	foreach ($result as $row) {
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