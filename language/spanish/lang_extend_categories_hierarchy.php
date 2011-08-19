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
if ( !defined('IN_NUCLEO') )
{
	die('Rock Republik &copy; 2006');
}

// admin part
if ( $lang_extend_admin )
{
	$lang['Lang_extend_categories_hierarchy']		= 'Categories Hierarchy';

	$lang['Category_attachment'] = 'Adjunto a';
	$lang['Category_desc'] = 'Descripci�n';
	$lang['Category_config_error_fixed'] = 'Un error en la configuraci�n de categor�as ha sido reparado';
	$lang['Attach_forum_wrong']	= 'No puedes adjuntar un foro a otro foro';
	$lang['Attach_root_wrong'] = 'No puedes adjuntar un foro a la p�gina principal del foro';
	$lang['Forum_name_missing'] = 'No puedes crear un foro sin nombre';
	$lang['Category_name_missing'] = 'No puedes crear una categor�a sin nombre';
	$lang['Only_forum_for_topics'] = 'Los temas s�lo pueden estar ubicados en foros';
	$lang['Delete_forum_with_attachment_denied'] = 'No puedes borrar foros que contengan sub-niveles';
	$lang['Category_delete'] = 'Borrar Categor�a';
	$lang['Category_delete_explain'] = 'El formulario debajo te permitir� borrar una categor�a y decidir donde deseas colocar todos los foros y categor�as que conten�a.';

	// forum links type
	$lang['Forum_link_url']	= 'Enlace URL';
	$lang['Forum_link_url_explain']	= 'Puede colocar aqu� un enlace a un programa phpBB (dentro del mismo servidor del foro), o un enlace URL a un servidor externo';
	$lang['Forum_link_internal'] = 'Programa phpBB';
	$lang['Forum_link_internal_explain'] = 'Elije "s�" si deseas invocar un programa que se encuentra dentro de los directorios del phpBB';
	$lang['Forum_link_hit_count'] = 'Contador de Clicks';
	$lang['Forum_link_hit_count_explain'] = 'Elige "s�" si deseas que el foro cuente y muestre el n�mero de clicks sobre este enlace';
	$lang['Forum_link_with_attachment_deny'] = 'No puedes establecer un foro como enlace si ya tiene sub-niveles';
	$lang['Forum_link_with_topics_deny'] = 'No puedes establecer un foro como enlace si ya tiene temas en �l';
	$lang['Forum_attached_to_link_denied'] = 'No puedes adjuntar un foro o categor�a a un foro-enlace';

	$lang['Manage_extend'] = 'Management +';
	$lang['No_subforums']	= 'No sub-foros';
	$lang['Forum_type'] = 'Escoge el tipo de foro que deseas';
	$lang['Presets'] = 'Presets';
	$lang['Refresh'] = 'Refrescar';
	$lang['Position_after'] = 'Posicionar este foro despu�s';
	$lang['Link_missing'] = 'El enlace es inv�lido';
	$lang['Category_with_topics_deny'] = 'A�n hay temas en este foro. No puedes cambiarlo a Categor�a.';
	$lang['Recursive_attachment'] = 'You can\'t attach a forum to a lowest level of its own branch (recursive attachment)';
	$lang['Forum_with_attachment_denied'] = 'You can\'t change a category with forums attached to into a forum';
	$lang['icon'] = 'Icono';
	$lang['icon_explain'] = 'This icon will be displayed in front of the forum title. You can set here a direct URI or a $image[] key entry (see <i>your_template</i>/<i>your_template</i>.cfg).';
}

$lang['Hierarchy_setting'] = 'Jerarquizaci�n de Categor�as';
$lang['Use_sub_forum'] = 'Compresi�n Principal';
$lang['Index_packing_explain'] = 'Elige el nivel de compresi�n que deseas para la p�gina principal';
$lang['Medium'] = 'Mediano';
$lang['Full'] = 'Completo';
$lang['Split_categories'] = 'Separar Categor�as en el Principal';
$lang['Use_last_topic_title'] = 'Mostrar los t�tulos de los �ltimos temas en el Principal';
$lang['Last_topic_title_length'] = 'Longitud de los t�tulos';
$lang['Sub_level_links'] = 'Enlaces a Sub-niveles en el Principal';
$lang['Sub_level_links_explain'] = 'A�ade los enlaces a los sub-niveles en la descripci�n del foro o categor�a';
$lang['With_pics'] = 'Con �conos';
$lang['Display_viewonline'] = 'Mostrar la informaci�n de "Qui�n est� online" en el Principal';
$lang['Never'] = 'Nunca';
$lang['Root_index_only'] = 'S�lo en la p�gina principal';
$lang['Always'] = 'Siempre';

$lang['Forum_link'] = 'Redirecci�n del enlace';
$lang['Category_locked'] = 'Esta categor�a est� cerrada: no puedes publicar, responder o editar temas.';
$lang['Forum_link_visited'] = 'El enlace ha sido visitado %d vez/veces';
$lang['Redirect'] = 'Redireccionar';
$lang['Redirect_to'] = 'Si su navegador no soporta redirecci�n meta por favor haga click %sAQU�% para ser redireccionado';
$lang['Subforums'] = 'Subforos';

?>