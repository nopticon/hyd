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