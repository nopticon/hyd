<?php
// -------------------------------------------------------------
// $Id: themusic.php,v 1.0 2008/03/23 18:58:00 Psychopsia Exp $
//
// STARTED   : Sun Mar 23, 2008
// COPYRIGHT : © 2008 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

require('./interfase/ftp.php');
$ftp = new ftp();

if (!$ftp->ftp_connect())
{
	_die('Can not connnect');
}

if (!$ftp->ftp_login())
{
	$ftp->ftp_quit();
	_die('Can not login');
}

/*

$filelist = $ftp->ftp_nlist('/Schedule');
$ftp->ftp_quit();

$ftp->ftp_put('/Schedule/schedule_playlist.txt', $cds_file)

*/

$d_orig = '..' . SDATA . 'artists/';
$d_dest = '../themusic/';

$count = 1;
$fp = @opendir($d_orig);
while ($row = @readdir($fp))
{
	if (!preg_match('#^[0-9]+$#i', $row))
	{
		continue;
	}
	
	$d_this = $d_orig . $row . '/media/';
	$fp2 = @opendir($d_this);
	while ($row2 = @readdir($fp2))
	{
		if (preg_match('#^[0-9]+\.wma#is', $row2))
		{
			if (@file_exists($d_dest . $row2))
			{
				continue;
			}
			$scp = $ftp->ftp_put($ftp->dfolder() . 'themusic/' . $row2,  $d_this . $row2);
			
			if (!$scp)
			{
				echo '***** ';
			}
			
			echo $count . ' - ' . $d_dest . $row2 . ' >> ' . $d_this . $row2 . '<br />';
			flush();
			$count++;
		}
	}
	@closedir($fp2);
}
@closedir($fp);

$ftp->ftp_quit();

?>