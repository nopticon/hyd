<?php
// -------------------------------------------------------------
// $Id: _acp.del_event.php,v 1.0 2006/12/05 15:43:00 Psychopsia Exp $
//
// STARTED   : Tue Dec 05, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('colab_admin');

if ($submit)
{
	$event = request_var('event', 0);
	$username = get_username_base($username);
	
	$sql = 'SELECT *
		FROM _events
		WHERE id = ' . (int) $event;
	$result = $db->sql_query($sql);
	
	$eventdata = array();
	if (!$eventdata = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$sql = 'DELETE FROM _events
		WHERE id = ' . (int) $event;
	$db->sql_query($sql);
	
	echo 'El evento <strong>' . $eventdata['title'] . '</strong> ha sido borrado.';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="event" value="" />
<input type="submit" name="submit" value="Borrar evento" />
</form>