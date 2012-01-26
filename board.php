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
require_once(ROOT . 'interfase/comments.php');
require_once(ROOT . 'objects/board.php');

$user->init();
$user->setup();

$board = new board();
$cat = $board->categories();
$forums = $board->forums();

if (!$cat || !$forums) {
	fatal_error();
}

$board->msg = new _comments();

$board->index();
$board->popular();

page_layout('FORUM_INDEX', 'board');

?>