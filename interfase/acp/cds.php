<?php
// -------------------------------------------------------------
// $Id: merge.php,v 1.7 2006/08/24 02:34:54 Psychopsia Exp $
//
// STARTED   : Sat Nov 19, 2005
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

require('./interfase/ftp.php');
$ftp = new ftp();

if (!$ftp->ftp_connect('209.51.162.170'))
{
	_die('Can not connect');
}

if (!$ftp->ftp_login('WURJ357411801', 'h29kE5fQ'))
{
	$ftp->ftp_quit();
	_die('Can not login');
}

$cds_file = './interfase/cds/schedule_playlist.txt';

// Submit
if ($submit)
{
	$hours = request_var('hours', array('' => ''));
	
	$build = '';
	foreach ($hours as $hour => $play)
	{
		$build .= ((!empty($build)) ? "\r\n" : '') . trim($hour) . ':' . trim($play);
	}
	
	$fp = @fopen($cds_file, 'w');
	if ($fp)
	{
		@flock($fp, LOCK_EX);
		fputs($fp, $build);
		@flock($fp, LOCK_UN);
		fclose($fp);
		
		@chmod($cds_file, 0777);
		
		if ($ftp->ftp_put('/Schedule/schedule_playlist.txt', $cds_file))
		{
			echo '<h1>El archivo fue procesado correctamente.</h1>';
		}
		else
		{
			echo '<h1>Error al procesar, intenta nuevamente.</h1>';
		}
	}
	else
	{
		echo 'Error de escritura en archivo local.';
	}
	
	echo '<br />';
}

if (!@file_exists($cds_file))
{
	fatal_error();
}

$cds = @file($cds_file);

?><html>
<head>
<title>CDS</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">

<?php

$filelist = $ftp->ftp_nlist('/Schedule');
echo '<pre>';
print_r($filelist);
echo '</pre>';

foreach ($cds as $item)
{
	$e_item = array_map('trim', explode(':', $item));
	if (!empty($e_item[0]))
	{
		echo sumhour($e_item[0]) . ' <input type="text" name="hours[' . $e_item[0] . ']" value="' . $e_item[1] . '" size="100"' . ((oclock($e_item[0])) ? ' style="background-color: #CCCCCC;"' : '') . ' /><br />' . "\n";
	}
}

?>

<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html><?php

$ftp->ftp_quit();

// Functions

function sumhour($a)
{
	$h = substr($a, 0, 2);
	$m = substr($a, 2, 2);
	$mk = mktime($h - 6, $m);
	
	return date('Hi', $mk);
}

function oclock($a)
{
	$h = substr($a, 0, 2);
	$m = substr($a, 2, 2);
	
	return ($m === '00');
}

?>