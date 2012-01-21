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
require_once('./interfase/common.php');
require_once(ROOT . 'interfase/downloads.php');
require_once(ROOT . './interfase/unread.php');

$user->init();
$user->setup();

if (!$user->is('member')) {
	do_login();
}

$unread = new unread();

$element = request_var('element', 0);
$object = request_var('object', 0);

$select = request_var('select', array(0 => 0));
$select_all = request_var('select_all', 0);

if ($select_all) {
	$unread->clear_all();
}

if (count($select)) {
	foreach ($select as $select_element => $void) {
		if (isset($_POST['delete'][$select_element])) {
			$user->delete_unread($element, $select[$select_element]);
			break;
		}
	}
}

if (!$unread->run()) {
	$template->assign_block_vars('objects_empty', array());
}

$template->assign_vars(array(
	'S_UNREAD_ACTION' => s_link('new'))
);

page_layout('UNREAD_ITEMS', 'unread');

?>