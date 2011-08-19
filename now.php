<?php

$scl = array(
	'host' => 'statistics.streamsolutions.co.uk',
	'host_port' => '80',
	
	'ip' => '216.66.69.4',
	'port' => '9594',
	'value' => 'Server is currently down.',
	'down' => 'La radio no est&aacute; disponible en este momento.',
	'data' => array()
);
$errno = 0;
$errstr = '';

$stats_get_line = 'GET http://' . $scl['host'] . '/shoutcast/text/index.php?';
$stats_get_line.= 'server=' . $scl['ip'] . '&port=' . $scl['port'] . " HTTP/1.0\r\n";
$stats_get_line.= "User-Agent: StreamSolutions  (Mozilla Compatible)\r\n\r\n";

// Open Connection
$fp = fsockopen($scl['host'] , $scl['host_port'], $errno, $errstr, 30);
if (!$fp)
{
	die($scl['down']);
}

$data = '';
fputs($fp, $stats_get_line);
while (!feof($fp))
{
	$data .= fgets($fp, 1000);
}
fclose($fp);

$lines = array_slice(split("\n", trim($data)), 8);
foreach ($lines as $line)
{
	$e = explode('<SSTAG>', $line);
	$scl['data'][$e[0]] = $e[1];
}

if ($scl['data']['server_status'] == $scl['value'])
{
	die($scl['down']);
}

// Parse song
$song = array_map('trim', explode('-', $scl['data']['current_song']));
$song[1] = array_pop($song);

if (!empty($scl['data']['stream_title']) && $scl['data']['stream_title'] != 'Rock Republik Radio')
{
	echo '<div class="live">Al Aire</div><div class="livetitle"><span>' . $scl['data']['stream_title'] . '</span></div>';
}

foreach ($song as $row)
{
	echo '<div>' . $row . '</div>';
}

die();

?>