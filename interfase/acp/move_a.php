<?php
// -------------------------------------------------------------
// $Id: move_a.php,v 1.0 2010/11/11 11:00:00 Master Exp $
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
	$t = request_var('news_id', 0);
	$f = request_var('cat_id', 0);
	
	if (!$f || !$t)
	{
		_die();
	}
	
	//
	$sql = 'SELECT *
		FROM _news
		WHERE news_id = ' . (int) $t;
	$result = $db->sql_query($sql);
	
	if (!$tdata = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'SELECT *
		FROM _news_cat
		WHERE cat_id = ' . (int) $f;
	$result = $db->sql_query($sql);
	
	if (!$fdata = $db->sql_fetchrow($result))
	{
		_die();
	}
	$db->sql_freeresult($result);
	
	//
	$sql = 'UPDATE _news
		SET cat_id = ' . (int) $f . '
		WHERE news_id = ' . $t;
	$db->sql_query($sql);
		
	
	redirect(s_link('news', $t));
}


?>

<html>
<head>
<title>Move News</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
# Noticia: <input type="text" name="news_id" /><br /><br />
Categoria: <select name="cat_id">
<?php

$sql = 'SELECT cat_id, cat_name
	FROM _news_cat
	ORDER BY cat_id';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	echo '<option value="' . $row['cat_id'] . '">' . $row['cat_name'] . '</option>';
}
$db->sql_freeresult($result);

?>
</select>

<br /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>