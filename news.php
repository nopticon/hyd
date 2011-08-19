<?php
// -------------------------------------------------------------
// $Id: news.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Aug 01, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

require('./interfase/news.php');
$news = new _news();

if ($news->_setup())
{
	$news->_view();
	
	$pagehtml = 'news_read';
	$page_title = $user->lang['NEWS'] . ' | ' . $news->data['post_subject'];
}
else
{
	$news->_main();
	
	$pagehtml = 'news_body';
	$page_title = 'NEWS';
}

page_layout($page_title, $pagehtml);

?>