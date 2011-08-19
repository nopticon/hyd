<?php
// -------------------------------------------------------------
// $Id: _acp.bot.php,v 1.0 2006/09/06 23:43:00 Psychopsia Exp $
//
// STARTED   : Wed Sep 06, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$bot_name = request_var('bot_name', '');
	$bot_agent = request_var('bot_agent', '');
	$bot_ip = request_var('bot_ip', '');
	$bot_base = get_username_base($bot_name);
	
	$sql = "SELECT *
		FROM _bots
		WHERE bot_name = '" . $db->sql_escape($bot_name) . "'";
	$result = $db->sql_query($sql);
	
	$insert = true;
	if ($row = $db->sql_fetchrow($result))
	{
		$insert = false;
		
		if ($row['bot_ip'] != $bot_ip)
		{
			$sql = "UPDATE _bots SET bot_ip = '" . $row['bot_ip'] . "," . $bot_ip . "'
				WHERE bot_id = " . (int) $row['bot_id'];
			$db->sql_query($sql);
		}
	}
	$db->sql_freeresult($result);
	
	if ($insert)
	{
		$insert_member = array(
			'user_type' => 2,
			'user_active' => 1,
			'username' => $bot_name,
			'username_base' => $bot_base,
			'user_color' => '9E8DA7',
			'user_timezone' => -6.00,
			'user_lang' => 'spanish'
		);
		$sql = 'INSERT INTO _members' . $db->sql_build_array('INSERT', $insert_member);
		$db->sql_query($sql);
		
		$bot_id = $db->sql_nextid();
		
		$insert_bot = array(
			'bot_active' => 1,
			'bot_name' => $bot_name,
			'user_id' => $bot_id,
			'bot_agent' => $bot_agent,
			'bot_ip' => $bot_ip,
		);
		$sql = 'INSERT INTO _bots' . $db->sql_build_array('INSERT', $insert_bot);
		$db->sql_query($sql);
	}
	
	$sql = "DELETE FROM _sessions
		WHERE session_browser LIKE '%" . $db->sql_escape($bot_name) . "%'";
	$db->sql_query($sql);
	
	$cache->delete('bots');
}

?>
<html>
<head>
<title>Add bots</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Nombre: <input type="text" name="bot_name" size="100" /><br />
Agente: <input type="text" name="bot_agent" size="100" /><br />
IP: <input type="text" name="bot_ip" size="100" /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>