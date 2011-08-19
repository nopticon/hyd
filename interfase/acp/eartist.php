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
	$event = request_var('event', 0);
	$artist = request_var('artist', '', true);
	if (!$event || empty($artist))
	{
		_die();
	}
	
	$sql = 'SELECT *
		FROM _events
		WHERE id = ' . (int) $event;
	$result = $db->sql_query($sql);
	
	if (!$row = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	$e_artist = explode("\n", $artist);
	foreach ($e_artist as $row)
	{
		$subdomain = get_subdomain($row);
		
		$sql = "SELECT *
			FROM _artists
			WHERE subdomain = '" . $db->sql_escape($subdomain) . "'";
		$result = $db->sql_query($sql);
		
		if ($a_row = $db->sql_fetchrow($result))
		{
			$sql = 'INSERT INTO _artists_events (a_artist, a_event)
				VALUES (' . (int) $a_row['ub'] . ', ' . (int) $event . ')';
			$db->sql_query($sql);
		}
		$db->sql_freeresult($result);
	}
	
	echo 'Actualizado.<br /><br />';
}

?>

<form action="<?php echo $u; ?>" method="post">

<select name="event">
<?php

$sql = 'SELECT *
	FROM _events
	WHERE date > ' . time() . '
	ORDER BY date DESC';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['id'] . '">' . $row['title'] . ' - ' . $user->format_date($row['date']) . '</option>';
}
$db->sql_freeresult($result);

?>
</select>

<br />
<textarea name="artist" cols="50" rows="15"></textarea><br />
<input type="submit" name="submit" value="Siguiente" />
</form>