<?php
// -------------------------------------------------------------
// $Id: _acp.ban.php,v 1.0 2007/06/26 08:22:00 Psychopsia Exp $
//
// STARTED   : Tue Jun 22, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

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