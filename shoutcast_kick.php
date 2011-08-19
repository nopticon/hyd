<?php
// -------------------------------------------------------------
// $Id: _acp.shoutcast_kick.php,v 1.1 2008/01/05 05:35:00 Psychopsia Exp $
//
// STARTED   : Sun Dec 31, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------

if (!isset($config['kick_script']))
{
	define('IN_NUCLEO', true);
	require('./interfase/common.php');
	
	$user->init(true, true);
	$user->setup();
	
	redirect(s_link('forum', 'djs'));
	exit();
}

$scl = array(
	'host' => 'statistics.streamsolutions.co.uk',
	'host_port' => '80',
	'var' => 'server_status',
	'value' => 'Server is currently down.',
	'data' => array(),
	
	'ip' => '216.66.69.4',
	'port' => '9594',
	'passwd' => 'h1dLm0aC3'
);

$stats_get_line = 'GET http://' . $scl['host'] . '/shoutcast/text/index.php?';
$stats_get_line.= 'server=' . $scl['ip'] . '&port=' . $scl['port'] . '&variable=' . $scl['var'] . " HTTP/1.0\r\n";
$stats_get_line.= "User-Agent: StreamSolutions  (Mozilla Compatible)\r\n\r\n";

// Open Connection
$fp = fsockopen($scl['host'] , $scl['host_port'], &$errno, &$errstr, 30);
if ($fp)
{
	fputs($fp, $stats_get_line);
	
	$scl['data'][$scl['var']] = '';
	while (!feof($fp))
	{
		$scl['data'][$scl['var']] .= fgets($fp, 1000);
	}
	fclose($fp);
	
	$lines = split("\n", $scl['data'][$scl['var']]);
	if ($lines[8] != $scl['value'])
	{
		$fp = @fsockopen($scl['ip'] , $scl['port'], &$errno, &$errstr, 30);
		if ($fp)
		{
			$kick_request = 'GET /admin.cgi?pass=' . $scl['passwd'] . '&mode=kicksrc' . " HTTP/1.0\r\n";
			$kick_request.= "User-Agent: StreamSolutions  (Mozilla Compatible)\r\n\r\n";
			$data = '';
			
			fputs($fp, $kick_request);
			while (!feof($fp))
			{
				$data .= fgets($fp, 1000);
			}
			fclose($fp);
			
			$lines = split("\n", trim($data));
			if (!isset($lines[4]))
			{
				$lines[4] = '';
			}
			
			// If successful
			if (strstr($lines[4], 'redirect'))
			{
				$insert = array(
					'log_uid' => $user->data['user_id'],
					'log_time' => time()
				);
				$sql = 'INSERT INTO _radio_dj_log' . $db->sql_build_array('INSERT', $insert);
				$db->sql_query($sql);
			}
		}
	}
}

_die('Disconnected.');

?>