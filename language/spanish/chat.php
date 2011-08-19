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
	'CHAT_NOW' => 'Chatear Ahora!',
	'CHAT_CREATE' => 'Crear Canal',
	'CHAT_CHANNEL_LISTING' => 'Lista de Canales',
	'CHAT_MANAGE_CHANNEL' => 'Administrar Canal',
	'CHAT_NOCH' => 'No hay canales',
	'RETURN_CHAT' => 'Regresar a la lista',
	'CHAT_CREATE_NAME' => 'Nombre del canal',
	'CHAT_CREATE_DESC' => 'Descripci�n',
	'CHAT_CREATE_CAT' => 'Categor�a',
	'CHAT_CREATE_AUTH' => 'Permitir acceso',
	'CHAT_CREATE_TYPE' => 'Tipo de canal',
	'CHAT_CREATE_SUBMIT' => 'Crear!',
	'CHAT_CREATE_EMPTY' => 'Debes completar el nombre del canal.',
	'CHAT_CREATE_INVALID_NAME' => 'El nombre del canal �nicamente puede contener letras, n�meros y <strong>-</strong>',
	'CHAT_CREATE_EMPTY_DESC' => 'Debes completar la descripci�n del canal.',
	'CHAT_ALREADY_CREATED' => 'El nombre del canal ya existe, escoge otro.',
	
	'CHAT_MEMBER_ENTERED' => '<div class="pad3 color"><strong>%s</strong> entr� al canal</div>',
	'CHAT_MEMBER_LOGOUT' => '<div class="pad3 color"><strong>%s</strong> sali� del canal</div>',
	'CHAT_MEMBER_TIMEOUT' => '<div class="pad3 color"><strong>%s</strong> sali� del canal por inactividad</div>',
	'CHAT_LOGOUT' => 'Salir del chat'
);

?>