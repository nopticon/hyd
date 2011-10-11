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
$user->setup('control');

if (!$user->data['is_member']) {
	if ($user->data['is_bot']) {
		redirect(s_link());
	}
	do_login();
}

if (!$user->_team_auth('all')) {
	fatal_error();
}

$module = request_var('module', '');
if (empty($module) || !preg_match('#[a-z\_]+#i', $module)) {
	fatal_error();
}

$filepath = ROOT . 'interfase/acp/' . $module . '.php';
if (!@file_exists($filepath)) {
	fatal_error();
}

$submit = isset($_POST['submit']);
$u = s_link('acp', $module);

include($filepath);

// Functions
function _auth($a) {
	global $user;
	
	if (!$user->_team_auth($a)) {
		fatal_error();
	}
}

?>