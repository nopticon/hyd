<?php
// -------------------------------------------------------------
// $Id: board.php,v 1.4 2006/02/16 04:58:14 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
require('./interfase/common.php');
require('./interfase/comments.php');
require('./interfase/board.php');

$user->init();

$board = new board();
$cat = $board->categories();
$forums = $board->forums();

if (!$cat || !$forums)
{
	fatal_error();
}

$user->setup();

//
// Build forum
//
$board->msg = new _comments();

$board->index();
$board->birthdays();

page_layout('FORUM_INDEX', 'board_body');

?>