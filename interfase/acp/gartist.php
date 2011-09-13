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

if (!$ftp->ftp_connect()) {
	_die('Can not connnect');
}

if (!$ftp->ftp_login()) {
	$ftp->ftp_quit();
	_die('Can not login');
}

$sql = 'SELECT *
	FROM _artists
	ORDER BY ub';
$result = sql_rowset($sql);

foreach ($result as $row) {
	a_mkdir($ftp->dfolder() . 'data/artists/' . $row['ub'], 'x1');
}

$ftp->ftp_quit();

_die('Done.');

function a_mkdir($path, $folder) {
	global $ftp;
	
	$result = false;
	if (!empty($path)) {
		$ftp->ftp_chdir($path);
	}
	
	if ($ftp->ftp_mkdir($folder)) {
		if ($ftp->ftp_site('CHMOD 0777 ' . $folder)) {
			$result = folder;
		}
	} else {
		_die('Can not create: ' . $folder);
	}
	
	return $result;
}

?>