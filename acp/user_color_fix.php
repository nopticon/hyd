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
if (!defined('IN_NUCLEO')) exit;

require_once(ROOT . './interfase/comments.php');

class __user_color_fix extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		if (!$this->submit) {
			return false;
		}
		
		$username = request_var('username', '');
		$username = get_username_base($username);
		
		$sql = 'SELECT user_id, username
			FROM _members
			WHERE username_base = ?';
		if (!$userdata = sql_fieldrow(sql_filter($sql, $username))) {
			fatal_error();
		}
		
		$sql = 'UPDATE _members SET user_color = ?
			WHERE user_id = ?';
		sql_query(sql_filter($sql, '4D5358', $userdata['user_id']));
		
		$comments = new _comments();
		
		$_conv = "Saludos %s,
		
		Tu color de usuario ha sido cambiado porque es muy claro para el color de fondo de la p&aacute;gina.
		Deber&aacute;s escoger uno m&aacute;s oscuro que sea legible.
		
		M&aacute;s informaci&oacute;n en: http://www.rockrepublik.net/help/57/
		
		Muchas gracias por tu comprensi&oacute;n.";
		$_conv = sprintf($_conv, $userdata['username']);
		
		$dc_id = $comments->store_dc('start', $userdata, $user->d(), 'Rock Republik: Cambio de color de usuario', $_conv);
		
		return _pre('El color de ' . $userdata['username'] . ' ha sido cambiado y fue notificado.', true);
	}
}

?>