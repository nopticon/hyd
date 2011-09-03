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
define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$filename = request_var('filename', '');
if (empty($filename) || !preg_match('#([a-z0-9\.])#is', $filename)) {
	fatal_error();
}

$filepath = '../home/downloads/' . $filename;
if (!@file_exists($filepath)) {
	fatal_error();
}

$sql = 'UPDATE _downloads
	SET download_count = download_count + 1
	WHERE download_filename = ?';
sql_query(sql_filter($sql, $filename));

if (!sql_affectedrows()) {
	$insert = array(
		'download_filename' => $filename,
		'download_count' => 1
	);
	$sql = 'INSERT INTO _downloads' . sql_build('INSERT', $insert);
	sql_query($sql);
}

//
require('./interfase/downloads.php');
$downloads = new downloads();

$downloads->filename = $filename;
$downloads->filepath = substr($filepath, 3);
$downloads->dl_file();

?>