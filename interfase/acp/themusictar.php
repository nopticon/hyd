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