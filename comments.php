<?php
// -------------------------------------------------------------
// $Id: comments.php,v 1.5 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Aug 15, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');

if ($config['request_method'] != 'post' || !isset($_POST))
{
	redirect(s_link());
}

// Init member
$user->init();

if (!$user->data['is_member'])
{
	if ($user->data['is_bot'])
	{
		redirect(s_link());
	}
	do_login();
}

require('./interfase/comments.php');
$comments = new _comments;

$comments->ref = (isset($_POST['ref']) && !empty($_POST['ref'])) ? request_var('ref', '', true) : $user->data['session_page'];

if (!preg_match('#\/\/www\.rockrepublik\.net(.*?)#is', $comments->ref) && preg_match('#\/\/(.*?)\.rockrepublik\.net(.*?)#is', $comments->ref, $preg_a))
{
	$comments->ref = 'http://www.rockrepublik.net/a/' . $preg_a[1] . '/' . $preg_a[2];
}

$comments->store();

redirect($comments->ref);

?>