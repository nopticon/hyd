<?php
// -------------------------------------------------------------
// $Id: forumalias.php,v 1.0 2007/06/26 08:22:00 Psychopsia Exp $
//
// STARTED   : Tue Jun 22, 2007
// COPYRIGHT : � 2007 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$forum_id = request_var('fid', 0);
	$forum_alias = request_var('falias', '');
	
	$sql = "UPDATE _forums
		SET forum_alias = '" . $forum_alias . "'
		WHERE forum_id = " . (int) $forum_id;
	$db->sql_query($sql);
	
	echo $forum_id . ' > ' . $forum_alias . '<br />';
}

?>
<html>
<head>
<title>Forum Alias</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
<select name="fid">
<?php

$sql = 'SELECT forum_id, forum_name
	FROM _forums
	ORDER BY forum_order';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['forum_id'] . '">' . $row['forum_name'] . '</option>';
}
$db->sql_freeresult($result);

?></select><br />


Alias: <input type="text" name="falias" size="100" /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>