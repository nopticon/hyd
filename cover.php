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

_pre('here1', true);

$user->init();
$user->setup();

_pre('here2', true);

srand((double)microtime()*1000000);

require_once(ROOT . 'interfase/cover.php');
require_once(ROOT . 'interfase/artists.php');
require_once(ROOT . 'interfase/events.php');

$cover = new cover();
$artists = new _artists();
$events = new _events(true);

$cover->news();
$cover->banners();
$cover->board_general();
$cover->board_events();
$cover->poll();
$cover->twitter();

$artists->get_data();
$artists->thumbnails();

$events->_nextevent();
//$events->_lastevent();

page_layout('HOME', 'cover', false, false);

?>