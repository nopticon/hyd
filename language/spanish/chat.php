<?php
// -------------------------------------------------------------
// $Id: chat.php,v 1.1 2006/02/06 08:06:31 Psychopsia Exp $
//
// STARTED   : Mon Jan 06, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

if (!is_array($lang) || empty($lang))
{
	$lang = array();
}

$lang += array(
	'CHAT_NOW' => 'Chatear Ahora!',
	'CHAT_CREATE' => 'Crear Canal',
	'CHAT_CHANNEL_LISTING' => 'Lista de Canales',
	'CHAT_MANAGE_CHANNEL' => 'Administrar Canal',
	'CHAT_NOCH' => 'No hay canales',
	'RETURN_CHAT' => 'Regresar a la lista',
	'CHAT_CREATE_NAME' => 'Nombre del canal',
	'CHAT_CREATE_DESC' => 'Descripción',
	'CHAT_CREATE_CAT' => 'Categoría',
	'CHAT_CREATE_AUTH' => 'Permitir acceso',
	'CHAT_CREATE_TYPE' => 'Tipo de canal',
	'CHAT_CREATE_SUBMIT' => 'Crear!',
	'CHAT_CREATE_EMPTY' => 'Debes completar el nombre del canal.',
	'CHAT_CREATE_INVALID_NAME' => 'El nombre del canal únicamente puede contener letras, números y <strong>-</strong>',
	'CHAT_CREATE_EMPTY_DESC' => 'Debes completar la descripción del canal.',
	'CHAT_ALREADY_CREATED' => 'El nombre del canal ya existe, escoge otro.',
	
	'CHAT_MEMBER_ENTERED' => '<div class="pad3 color"><strong>%s</strong> entró al canal</div>',
	'CHAT_MEMBER_LOGOUT' => '<div class="pad3 color"><strong>%s</strong> salió del canal</div>',
	'CHAT_MEMBER_TIMEOUT' => '<div class="pad3 color"><strong>%s</strong> salió del canal por inactividad</div>',
	'CHAT_LOGOUT' => 'Salir del chat'
);

?>