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

class __event_delete extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function _home() {
		global $config, $user, $cache, $template;
		
		return;
	}
}

_auth('colab_admin');

if ($submit)
{
	$event = request_var('event', 0);
	$username = get_username_base($username);
	
	$sql = 'SELECT *
		FROM _events
		WHERE id = ?';
	if (!$eventdata = sql_fieldrow(sql_filter($sql, $event))) {
		fatal_error();
	}
	
	$sql = 'DELETE FROM _events
		WHERE id = ?';
	sql_query(sql_filter($sql, $event));
	
	echo 'El evento <strong>' . $eventdata['title'] . '</strong> ha sido borrado.';
}

?>

<form action="{MODULE_URL}" method="post">
<input type="text" name="event" value="" />
<input type="submit" name="submit" value="Borrar evento" />
</form>