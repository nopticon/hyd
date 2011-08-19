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
	$topic_id = request_var('topic_id', '');
	if (empty($topic_id))
	{
		_die();
	}
	
	$sql = 'SELECT *
		FROM _poll_options
		WHERE topic_id = ' . (int) $topic_id;
	$result = $db->sql_query($sql);
	
	if (!$data_opt = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	$sql = 'SELECT v.*, m.username, r.vote_option_text
		FROM _poll_voters v, _members m, _poll_results r
		WHERE v.vote_id = ' . (int) $data_opt['vote_id'] . '
			AND v.vote_id = r.vote_id
			AND v.vote_user_id = m.user_id
			AND r.vote_option_id = v.vote_cast';
	$result = $db->sql_query($sql);
	
	echo '<table>';
	
	while ($row = $db->sql_fetchrow($result))
	{
		echo '<tr>
		<td>' . $row['username'] . '</td>
		<td>' . $row['vote_option_text'] . '</td>
		<td>' . $row['vote_user_ip'] . '</td>
		</tr>';
	}
	$db->sql_freeresult($result);
	
	echo '</table><br /><br /><br />';
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="topic_id" value="" size="20" /><br />
<input type="submit" name="submit" value="Cambiar" />
</form>