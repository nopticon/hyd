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
if (!is_array($lang) || empty($lang))
{
	$lang = array();
}

$lang += array(
	//
	// Common
	//
	'CONTROL_ADD' => 'Agregar',
	'CONTROL_EDIT' => 'Editar',
	'CONTROL_DELETE' => 'Borrar',
	'CONTROL_UP' => 'Arriba',
	'CONTROL_DOWN' => 'Abajo',
	
	//
	// Artists
	//
	'CONTROL_A' => 'Artistas',
	'CONTROL_A_HOME' => 'Inicio',
	'CONTROL_A_NEWS' => 'Noticias',
	'CONTROL_A_APOSTS' => 'Mensajes',
	'CONTROL_A_LOG' => 'Registro',
	'CONTROL_A_AUTH' => 'Miembros Autorizados',
	'CONTROL_A_GALLERY' => 'Galer&iacute;a',
	'CONTROL_A_BIOGRAPHY' => 'Biograf&iacute;a',
	'CONTROL_A_LYRICS' => 'L&iacute;ricas',
	'CONTROL_A_STATS' => 'Estad&iacute;sticas',
	'CONTROL_A_VOTERS' => 'Votantes',
	'CONTROL_A_DOWNLOADS' => 'Descargas',
	'CONTROL_A_DPOSTS' => 'Mensajes a Descargas',
	'CONTROL_A_ART' => 'Fondos de Pantalla',
	'CONTROL_A_ARPOSTS' => 'Mensajes a Fondos de Pantalla',
	'CONTROL_A_VIDEO' => 'Videos',
	
	'CONTROL_A_NEWS_ADD' => 'Publicar',
	'CONTROL_A_NEWS_EDIT' => 'Editar',
	'CONTROL_A_NEWS_DELETE' => '&iquest;Est&aacute;s seguro que deseas borrar esta noticia?',
	
	'CONTROL_A_APOSTS_DELETE' => '&iquest;Est&aacute;s seguro que deseas borrar este mensaje?',
	'CONTROL_A_APOSTS_DELETE_FOREVER' => 'Borrar para siempre',
	
	'CONTROL_A_AUTH_ADD' => 'Agregar Miembro',
	'CONTROL_A_AUTH_ADD_LEGEND' => 'Puedes ingresar parte del nombre de usuario usando el caracter <strong>*</strong>, para seleccionar uno o varios miembros. Ejemplo: Si escribes <strong>rock*</strong>, buscar&aacute; todos los miembros que inician con <strong>rock</strong>.',
	'CONTROL_A_AUTH_ADD_LEGEND2' => 'Haz click sobre las casillas de los miembros que deseas autorizar.',
	'CONTROL_A_AUTH_ADD_NOMATCH' => 'No se encontraron miembros',
	'CONTROL_A_AUTH_ADD_TOOMUCH' => 'Se encontraron demasiados miembros, intenta realizar una b&uacute;squeda m&aacute;s espec&iacute;fica.',
	'CONTROL_A_AUTH_NOMEMBERS' => 'No hay miembros autorizados. Click en <strong>Agregar Miembro</strong>',
	'CONTROL_A_AUTH_DELETE' => '&iquest;Est&aacute;s seguro que deseas borrar los miembros de la banda <strong>%s</strong>?<br /><br />%s',
	'CONTROL_A_AUTH_DELETE2' => '&iquest;Est&aacute;s seguro que deseas borrar este miembro de la banda <strong>%s</strong>?<br /><br />%s',
	
	'CONTROL_A_LOG_ACTIONS' => 'acciones',
	'CONTROL_A_LOG_ACTION' => 'acci&oacute;n',
	'CONTROL_A_LOG_EMPTY' => 'El registro de actividad est&aacute; vac&iacute;o.',
	
	'CONTROL_A_BIOGRAPHY_UPDATED' => 'La biograf&iacute;a fue actualizada',
	
	'CONTROL_A_GALLERY_ADD' => 'Agregar imagen',
	'CONTROL_A_GALLERY_ADD_LEGEND' => 'Desde esta p&aacute;gina podr&aacute;s publicar nuevas fotograf&iacute;as para la galer&iacute;a.<br />Los archivos deben ser formato <strong>JPG</strong>, tama&ntilde;o de archivo <strong>500 kb</strong> m&aacute;ximo.',
	'CONTROL_A_GALLERY_ALLOW_DL' => 'Permitir que los miembros descarguen esta imagen',
	'CONTROL_A_GALLERY_ADD_NOIMAGE' => 'Debes selecccionar una imagen.',
	'CONTROL_A_GALLERY_EMPTY' => 'No hay im&aacute;genes disponibles. Click en <strong>Agregar imagen</strong>',
	'CONTROL_A_GALLERY_ERROR' => 'Ha ocurrido un error en el sistema de archivos, aunque es posible que si se borraran algunas im&aacute;genes seleccionadas.<br /><br />Click <a class="bold red" href="%s">AQUI</a> para regresar a la galer&iacute;a',
	
	'CONTROL_A_DOWNLOADS_ADD' => 'Agregar Descarga',
	'CONTROL_A_DOWNLOADS_EMPTY' => 'No hay descargas disponibles. Click en <strong>Agregar descarga</strong>',
	
	'CONTROL_A_STATS_LEGEND' => 'Este contador inici&oacute; antes de las estad&iacute;sticas por mes, hasta la fecha; as&iacute; que puede ser mayor que la suma de todas las gr&aacute;ficas',
	
	//
	// Comments
	//
	'CONTROL_COMMENTS' => 'Comentarios',
	'CONTROL_COMMENTS_EMOTICONS' => 'Emoticons',
	'CONTROL_COMMENTS_HELP' => 'Ayuda',
	'CONTROL_COMMENTS_QUESTION' => 'Pregunta',
	'CONTROL_COMMENTS_ANSWER' => 'Respuesta',
	'CONTROL_COMMENTS_HELP_MODULE' => 'M&oacute;dulo',
	'CONTROL_COMMENTS_HELP_EMPTY' => 'Debes completar toda la informaci&oacute;n.',
	'CONTROL_COMMENTS_HELP_NOCAT' => 'La categor&iacute;a seleccionada no existe.',
	'CONTROL_NEWS_ADD_IMAGE' => 'Desde esta p&aacute;gina podr&aacute;s publicar nuevas fotograf&iacute;as para la secci&oacute;n de noticias.<br /><br />Los archivos deben ser formato <strong>JPG</strong>, tama&ntilde;o de archivo <strong>500 kb</strong> m&aacute;ximo.<br />La imagen se ajustar&aacute; a las dimensiones requeridas de 100 x 75 p&iacute;xeles',
	
	// VIDEOS
	'CONTROL_A_VIDEO_ADD' => 'Agregar video',
	'CONTROL_A_VIDEO_ADD_LEGEND' => 'Ahora podr&aacute;s publicar videos por medio del servicio de <a href="http://www.youtube.com/">Youtube</a>.<br /><br />Ingresa la URL de Youtube o el c&oacute;digo donde se encuentra el video.<br />Ejemplo: http://www.youtube.com/watch?v=<strong>VXytaD5AKyM</strong>',
	
	//
	// Events
	//
	'CONTROL_EVENTS' => 'Eventos',
	'CONTROL_ART' => 'Arte',
	'CONTROL_CHAT' => 'Chat',
	'CONTROL_COMMENTS' => 'Comentarios',
	'CONTROL_CONFIG' => 'Configuraci&oacute;n',
	'CONTROL_FORUM' => 'Foro',
	'CONTROL_LINKS' => 'Enlaces',
	'CONTROL_MEMBERS' => 'Miembros',
	'CONTROL_NEWS' => 'Noticias',
	'CONTROL_POLL' => 'Encuestas',
	'CONTROL_REF' => 'Referencias',
	'CONTROL_TEAM' => 'Equipo Rock Republik',
	'CONTROL_TOPICS' => 'Temas de foro'
);

?>