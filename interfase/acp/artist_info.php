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

_auth('founder');

if ($submit)
{
	$artista = request_var('artista', '');
	if (empty($artista))
	{
		fatal_error();
	}
	else
	{
		$artista = get_subdomain($artista);
		
		$sql = "SELECT *
			FROM _artists
			WHERE subdomain = '" . $db->sql_escape($artista) . "'";
	}
	
	$result = $db->sql_query($sql);
	
	if (!$userdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	foreach ($userdata as $k => $void)
	{
		if (preg_match('#\d+#is', $k))
		{
			unset($userdata[$k]);
		}
	}
	
	echo '<pre>';
	print_r($userdata);
	echo '</pre>';
}

?>
<html>
<head>
<title>Query users</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Nombre de artista: <input type="text" name="artista" size="100" /><br />
<input type="submit" name="submit" value="Consultar" />
</form>
</body>
</html>