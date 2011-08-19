<?php
// -------------------------------------------------------------
// $Id: radio_add.php,v 1.0 2008/03/10 20:48:00 Psychopsia Exp $
//
// STARTED   : Mon Mar 10, 2008
// COPYRIGHT : © 2008 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
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
	
	$sql = "SELECT show_id
		FROM _radio
		WHERE show_base = '" . $db->sql_escape($v['base']) . "'";
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		//_die('El programa ya existe');
	}
	$db->sql_freeresult($result);
	
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
	
	$sql = 'INSERT INTO _radio' . $db->sql_build_array('INSERT', $v);
	$db->sql_query($sql);
	
	$show_id = $db->sql_nextid();
	
	$e_dj = explode("\n", $dj_list);
	foreach ($e_dj as $rowu)
	{
		$rowu = get_username_base($rowu);
		
		$sql = "SELECT *
			FROM _members
			WHERE username = '" . $db->sql_escape($rowu) . "'";
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$sql = 'INSERT INTO _radio_dj (dj_show, dj_uid)
				VALUES (' . (int) $show_id . ', ' . (int) $row['user_id'] . ')';
			$db->sql_query($sql);
			
			$sql = 'SELECT *
				FROM _team_members
				WHERE team_id = 4
					AND member_id = ' . (int) $row['user_id'];
			$result2 = $db->sql_query($sql);
			
			if (!$row2 = $db->sql_fetchrow($result2))
			{
				$sql = "INSERT INTO _team_members (team_id, member_id, real_name, member_mod)
					VALUES (4, " . (int) $row['user_id'] . ", '', 0)";
				$db->sql_query($sql);
			}
			$db->sql_freeresult($result2);
		}
		$db->sql_freeresult($result);
	}
	
	$cache->delete('team_members');
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
Géneros: <input type="text" name="genre" size="100" /><br />
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