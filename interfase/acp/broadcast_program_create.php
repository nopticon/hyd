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

class broadcast_program_create extends mac {
	public function __construct() {
		parent::__construct();
		
		$this->auth('founder');
	}
	
	public function home() {
		if ($this->submit)
		{
			$v = array(
				'name' => '',
				'base' => '',
				'genre' => '',
				'start' => 0,
				'end' => 0,
				'day' => 0,
				'dj' => ''
			);
			foreach ($v as $vv => $d)
			{
				$v[$vv] = request_var($vv, $d);
			}
			
			$sql = 'SELECT show_id
				FROM _radio
				WHERE show_base = ?';
			if ($row = sql_fieldrow(sql_filter($sql, $v['base']))) {
				//_die('El programa ya existe');
			}
			
			$time_start = mktime($v['start'] - $user->data['user_timezone'], 0, 0, 0, 0, 0);
			$time_end = mktime($v['end'] - $user->data['user_timezone'], 0, 0, 0, 0, 0);
			
			$v['start'] = date('H', $time_start);
			$v['end'] = date('H', $time_end);
			
			$dj_list = $v['dj'];
			unset($v['dj']);
			
			foreach ($v as $vv => $d)
			{
				$v['show_' . $vv] = $d;
				unset($v[$vv]);
			}
			
			$sql = 'INSERT INTO _radio' . sql_build('INSERT', $v);
			$show_id = sql_query_nextid();
			
			$e_dj = explode("\n", $dj_list);
			foreach ($e_dj as $rowu) {
				$rowu = get_username_base($rowu);
				
				$sql = 'SELECT *
					FROM _members
					WHERE username = ?';
				if ($row = sql_fieldrow(sql_filter($sql, $rowu))) {
					$sql_insert = array(
						'dj_show' => $show_id,
						'dj_uid' => $row['user_id']
					);
					$sql = 'INSERT INTO _radio_dj' . sql_build('INSERT', $sql_insert);
					sql_query($sql);
					
					$sql = 'SELECT *
						FROM _team_members
						WHERE team_id = 4
							AND member_id = ?';
					if (!$row2 = sql_fieldrow(sql_filter($sql, $row['user_id']))) {
						$sql_insert = array(
							'team_id' => 4,
							'member_id' =>  $row['user_id'],
							'real_name' => '',
							'member_mod' => 0
						);
						$sql = 'INSERT INTO _team_members' . sql_build('INSERT', $sql_insert);
						sql_query($sql);
					}
				}
			}
			
			$cache->delete('team_members');
		}
		
		return;
	}
}

?>
<html>
<head>
<title>Add Radio Show</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Nombre: <input type="text" name="name" size="100" /><br />
Nombre base: <input type="text" name="base" size="100" /><br />
G�neros: <input type="text" name="genre" size="100" /><br />
Inicio UTC -6: <input type="text" name="start" size="100" /><br />
Final UTC -6: <input type="text" name="end" size="100" /><br />
D&iacute;a: <select name="day">
<option value="1">Lunes</option>
<option value="2">Martes</option>
<option value="3">Miercoles</option>
<option value="4">Jueves</option>
<option value="5">Viernes</option>
<option value="6">Sabado</option>
<option value="7">Domingo </option>
</select><br />
Locutores: <textarea name="dj" rows="5" cols="15"></textarea><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>