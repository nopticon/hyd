<?php
// -------------------------------------------------------------
// $Id: _mcc.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : © 2006 Rock Republik
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

$sql = 'SELECT *
	FROM _artists
	ORDER BY ub';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	a_mkdir($ftp->dfolder() . 'data/artists/' . $row['ub'], 'x1');
}
$db->sql_freeresult($result);

$ftp->ftp_quit();

_die('Done.');

function a_mkdir($path, $folder)
{
	global $ftp;
	
	$result = false;
	if (!empty($path))
	{
		$ftp->ftp_chdir($path);
	}
	
	if ($ftp->ftp_mkdir($folder))
	{
		if ($ftp->ftp_site('CHMOD 0777 ' . $folder))
		{
			$result = folder;
		}
	}
	else
	{
		_die('Can not create: ' . $folder);
	}
	
	return $result;
}

?>