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
if (!defined('IN_APP')) exit;

// admin part
if ($lang_extend_admin) {
	$lang['Lang_extend_merge'] = 'Simply Merge Threads';
}

$lang['Refresh'] = 'Actualizar'; 
$lang['Merge_topics'] = 'Fusionar con...'; 
$lang['Merge_title'] = 'Nuevo t�tulo del tema'; 
$lang['Merge_title_explain'] = 'Escriba el t�tulo que habr� de tener el nuevo tema final. D�jelo en blanco si quiere que el sistema utilice el t�tulo del tema de destino'; 
$lang['Merge_topic_from'] = 'Tema a fusionar'; 
$lang['Merge_topic_from_explain'] = 'Los mensajes de este tema pasar�n al tema siguiente. Puede indicar el n�mero del tema, el enlace del tema, o el enlace de uno de los mensajes de este tema'; 
$lang['Merge_topic_to'] = 'Tema de destino'; 
$lang['Merge_topic_to_explain'] = 'Este tema recibir� todos los mensajes del tema anterior. Puede indicar el n�mero del tema, el enlace del tema, o el enlace de uno de los mensajes de este tema'; 
$lang['Merge_from_not_found'] = 'No se ha encontrado el tema a fusionar'; 
$lang['Merge_to_not_found'] = 'No se ha encontrado el tema de destino'; 
$lang['Merge_topics_equals'] = 'No se puede fusionar un tema consigo mismo'; 
$lang['Merge_from_not_authorized'] = 'Usted no est� autorizado para moderar temas procedentes del foro al que pertenece el tema a fusionar'; 
$lang['Merge_to_not_authorized'] =  'Usted no est� autorizado para moderar temas procedentes del foro al que pertenece el tema de destino'; 
$lang['Merge_poll_from'] = 'Hay una encuesta en el tema a fusionar. Se copiar� al tema de destino'; 
$lang['Merge_poll_from_and_to'] = 'El tema de destino ya tiene una encuesta. Se borrar� la encuesta del tema a fusionar'; 
$lang['Merge_confirm_process'] = '�Est� seguro de que desea fusionar <br />"<b>%s</b>"<br />con<br />"<b>%s</b>"'; 
$lang['Merge_topic_done'] = 'Los temas se han fusionado correctamente.';

?>