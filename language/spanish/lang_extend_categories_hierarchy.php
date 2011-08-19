<?php
/***************************************************************************
 *						lang_extend_categories_hierarchy.php [Spanish]
 *						------------------------------------
 *	begin				: 28/09/2003
 *	copyright			: Ptirhiik
 *	email				: ptirhiik@clanmckeen.com
 *
 *	version				: 1.0.0 - 28/09/2003
 *	translation Author	: Phantom (http://www.zona-zero.com/)
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

if ( !defined('IN_NUCLEO') )
{
	die('Rock Republik &copy; 2006');
}

// admin part
if ( $lang_extend_admin )
{
	$lang['Lang_extend_categories_hierarchy']		= 'Categories Hierarchy';

	$lang['Category_attachment'] = 'Adjunto a';
	$lang['Category_desc'] = 'Descripción';
	$lang['Category_config_error_fixed'] = 'Un error en la configuración de categorías ha sido reparado';
	$lang['Attach_forum_wrong']	= 'No puedes adjuntar un foro a otro foro';
	$lang['Attach_root_wrong'] = 'No puedes adjuntar un foro a la página principal del foro';
	$lang['Forum_name_missing'] = 'No puedes crear un foro sin nombre';
	$lang['Category_name_missing'] = 'No puedes crear una categoría sin nombre';
	$lang['Only_forum_for_topics'] = 'Los temas sólo pueden estar ubicados en foros';
	$lang['Delete_forum_with_attachment_denied'] = 'No puedes borrar foros que contengan sub-niveles';
	$lang['Category_delete'] = 'Borrar Categoría';
	$lang['Category_delete_explain'] = 'El formulario debajo te permitirá borrar una categoría y decidir donde deseas colocar todos los foros y categorías que contenía.';

	// forum links type
	$lang['Forum_link_url']	= 'Enlace URL';
	$lang['Forum_link_url_explain']	= 'Puede colocar aquí un enlace a un programa phpBB (dentro del mismo servidor del foro), o un enlace URL a un servidor externo';
	$lang['Forum_link_internal'] = 'Programa phpBB';
	$lang['Forum_link_internal_explain'] = 'Elije "sí" si deseas invocar un programa que se encuentra dentro de los directorios del phpBB';
	$lang['Forum_link_hit_count'] = 'Contador de Clicks';
	$lang['Forum_link_hit_count_explain'] = 'Elige "sí" si deseas que el foro cuente y muestre el número de clicks sobre este enlace';
	$lang['Forum_link_with_attachment_deny'] = 'No puedes establecer un foro como enlace si ya tiene sub-niveles';
	$lang['Forum_link_with_topics_deny'] = 'No puedes establecer un foro como enlace si ya tiene temas en él';
	$lang['Forum_attached_to_link_denied'] = 'No puedes adjuntar un foro o categoría a un foro-enlace';

	$lang['Manage_extend'] = 'Management +';
	$lang['No_subforums']	= 'No sub-foros';
	$lang['Forum_type'] = 'Escoge el tipo de foro que deseas';
	$lang['Presets'] = 'Presets';
	$lang['Refresh'] = 'Refrescar';
	$lang['Position_after'] = 'Posicionar este foro después';
	$lang['Link_missing'] = 'El enlace es inválido';
	$lang['Category_with_topics_deny'] = 'Aún hay temas en este foro. No puedes cambiarlo a Categoría.';
	$lang['Recursive_attachment'] = 'You can\'t attach a forum to a lowest level of its own branch (recursive attachment)';
	$lang['Forum_with_attachment_denied'] = 'You can\'t change a category with forums attached to into a forum';
	$lang['icon'] = 'Icono';
	$lang['icon_explain'] = 'This icon will be displayed in front of the forum title. You can set here a direct URI or a $image[] key entry (see <i>your_template</i>/<i>your_template</i>.cfg).';
}

$lang['Hierarchy_setting'] = 'Jerarquización de Categorías';
$lang['Use_sub_forum'] = 'Compresión Principal';
$lang['Index_packing_explain'] = 'Elige el nivel de compresión que deseas para la página principal';
$lang['Medium'] = 'Mediano';
$lang['Full'] = 'Completo';
$lang['Split_categories'] = 'Separar Categorías en el Principal';
$lang['Use_last_topic_title'] = 'Mostrar los títulos de los últimos temas en el Principal';
$lang['Last_topic_title_length'] = 'Longitud de los títulos';
$lang['Sub_level_links'] = 'Enlaces a Sub-niveles en el Principal';
$lang['Sub_level_links_explain'] = 'Añade los enlaces a los sub-niveles en la descripción del foro o categoría';
$lang['With_pics'] = 'Con íconos';
$lang['Display_viewonline'] = 'Mostrar la información de "Quién está online" en el Principal';
$lang['Never'] = 'Nunca';
$lang['Root_index_only'] = 'Sólo en la página principal';
$lang['Always'] = 'Siempre';

$lang['Forum_link'] = 'Redirección del enlace';
$lang['Category_locked'] = 'Esta categoría está cerrada: no puedes publicar, responder o editar temas.';
$lang['Forum_link_visited'] = 'El enlace ha sido visitado %d vez/veces';
$lang['Redirect'] = 'Redireccionar';
$lang['Redirect_to'] = 'Si su navegador no soporta redirección meta por favor haga click %sAQUÍ% para ser redireccionado';
$lang['Subforums'] = 'Subforos';

?>