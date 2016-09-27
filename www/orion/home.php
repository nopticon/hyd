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
define('IN_APP', true);
require_once('./interfase/common.php');
require_once(ROOT . 'objects/home.php');
require_once(ROOT . 'objects/artists.php');
require_once(ROOT . 'objects/events.php');

$user->init();
$user->setup();

srand((double)microtime()*1000000);

$home = new _home();
$artists = new _artists();
$events = new _events(true);

$home->news();
$home->board_general();
$home->board_events();
$home->poll();

$artists->get_data();
$artists->thumbnails();

$events->_nextevent();

page_layout('HOME', 'home', false, false);
