<?php
// -------------------------------------------------------------
// $Id: _mcc.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$request = array('ub' => 0, 'title' => '', 'author' => '', 'text' => '');
	foreach ($request as $k => $v)
	{
		$request[$k] = request_var($k, $v);
	}
	
	$sql = 'SELECT *
		FROM _artists
		WHERE ub = ' . (int) $request['ub'];
	$result = $db->sql_query($sql);
	
	if (!$ad = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = 'INSERT INTO _artists_lyrics' . $db->sql_build_array('INSERT', $request);
	$db->sql_query($sql);
	
	$sql = 'UPDATE _artists
		SET lirics = lirics + 1
		WHERE ub = ' . (int) $request['ub'];
	$db->sql_query($sql);
	
	redirect(s_link('a', $ad['subdomain']));
}

?>

<form action="<?php echo $u; ?>" method="post">
Banda: <select name="ub"><?php

$sql = 'SELECT ub, name
	FROM _artists
	ORDER BY name';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['ub'] . '">' . $row['name'] . '</option>';
}
$db->sql_freeresult($result);

?></select><br />
T&iacute;tulo: <input type="text" name="title" value="" /><br />
Autor: <input type="text" name="author" value="" /><br />
Letra: <textarea name="text" cols="50" rows="20"></textarea><br />
<input type="submit" name="submit" value="Agregar Letra" />
</form>