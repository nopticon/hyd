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
	$name = request_var('name', '');
	
	$sql = "SELECT *
		FROM _artists
		WHERE name = '" . $db->sql_escape($name) . "'";
	$result = $db->sql_query($sql);
	
	if (!$a_data = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = 'SELECT v.*, u.user_id, u.username, u.username_base, u.user_color
		FROM _artists_fav v, _members u
		WHERE v.ub = ' . (int) $a_data['ub'] . '
			AND v.user_id = u.user_id
		ORDER BY u.username';
	$result = $db->sql_query($sql);
	
	echo '<ul type="1">';
	while ($row = $db->sql_fetchrow($result))
	{
		echo '<li>' . $row['username'] . '</li>';
	}
	$db->sql_freeresult($result);
	
	echo '</ul>';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="name" value="" />
<input type="submit" name="submit" value="Consultar artista" />
</form>