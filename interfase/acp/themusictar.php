<?php
// -------------------------------------------------------------
// $Id: themusic.php,v 1.0 2008/03/23 18:58:00 Psychopsia Exp $
//
// STARTED   : Sun Mar 23, 2008
// COPYRIGHT : © 2008 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

$qqq = set_time_limit(0);

_auth('founder');

//Creating the file to compress:
echo 'Compressing...';

flush();
//Run the command and print out the result string
exec('tar cfvz /home/rknet/public_html/themusic.tar.gz /home/rknet/public_html/themusic/');
flush();

//Check out the compressed file:
echo '<br /><a href="/themusic.tar.gz">Descargar</a>';
flush();

set_time_limit($qqq);

?>