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

_auth('all');

if ($submit)
{
	$orig = request_var('orig', '');
	$repl = request_var('repl', '');
	$total_1 = $total_2 = $total_3 = 0;
	
	$sql = "SELECT *
		FROM _forum_posts
		WHERE post_text LIKE '%" . $db->sql_escape($orig) . "%'
		ORDER BY post_id";
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result))
	{
		$row['post_text'] = str_replace($orig, $repl, $row['post_text']);
		
		$sql = "UPDATE _forum_posts
			SET post_text = '" . $db->sql_escape($row['post_text']) . "'
			WHERE post_id = " . (int) $row['post_id'];
		$db->sql_query($sql);
		//echo $sql . '<br />';
		
		$total_1++;
	}
	$db->sql_freeresult($result);
	
	//
	
	$sql = "SELECT *
		FROM _artists_posts
		WHERE post_text LIKE '%" . $db->sql_escape($orig) . "%'
		ORDER BY post_id";
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result))
	{
		$row['post_text'] = str_replace($orig, $repl, $row['post_text']);
		
		$sql = "UPDATE _artists_posts
			SET post_text = '" . $db->sql_escape($row['post_text']) . "'
			WHERE post_id = " . (int) $row['post_id'];
		$db->sql_query($sql);
		//echo $sql . '<br />';
		
		$total_2++;
	}
	$db->sql_freeresult($result);
	
	//
	
	$sql = "SELECT *
		FROM _members_posts
		WHERE post_text LIKE '%" . $db->sql_escape($orig) . "%'
		ORDER BY post_id";
	$result = $db->sql_query($sql);
	
	while ($row = $db->sql_fetchrow($result))
	{
		$row['post_text'] = str_replace($orig, $repl, $row['post_text']);
		
		$sql = "UPDATE _members_posts
			SET post_text = '" . $db->sql_escape($row['post_text']) . "'
			WHERE post_id = " . (int) $row['post_id'];
		$db->sql_query($sql);
		//echo $sql . '<br />';
		
		$total_3++;
	}
	$db->sql_freeresult($result);

	_die('La frase <strong>' . $orig . '</strong> fue reemplazada por <strong>' . $repl . '</strong> en ' . $total_1 . ' f, ' . $total_2 . ' a, ' . $total_3 . ' m.');
}

?>

<form action="<?php echo $u; ?>" method="post">
Original<br /><input type="text" name="orig" value="" /><br /><br />
Reemplazo<br /><input type="text" name="repl" value="" /><br /><br />
<input type="submit" name="submit" value="Reemplazar frase" />
</form>