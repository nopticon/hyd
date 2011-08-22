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

require('./interfase/news.php');
$news = new _news();

if ($news->_setup()) {
	$news->_view();
	
	$pagehtml = 'news_read';
	$page_title = $user->lang['NEWS'] . ' | ' . $news->data['post_subject'];
} else {
	$news->_main();
	
	$pagehtml = 'news_body';
	$page_title = 'NEWS';
}

page_layout($page_title, $pagehtml);

?>