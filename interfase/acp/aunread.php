<?php
// -------------------------------------------------------------
// $Id: hidden_eu.php,v 1.0 2006/05/24 00:00:00 Psychopsia Exp $
//
// STARTED   : Sat May 22, 2004
// COPYRIGHT : � 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

$sql = 'SELECT u.item, t.topic_ub
	FROM _members_unread u, _forum_topics t
	WHERE u.element = ' . UH_N . '
		AND u.item = t.topic_id
	GROUP BY item';
$result = $db->sql_query($sql);

while ($row = $db->sql_fetchrow($result))
{
	$sql = 'DELETE FROM _members_unread
		WHERE element = ' . UH_N . '
			AND item = ' . (int) $row['item'] . '
			AND user_id NOT IN (SELECT user_id FROM _artists_fav WHERE ub = ' . $row['topic_ub'] . ')';
	$db->sql_query($sql);
	
	$a = $db->sql_affectedrows();
	$total += $a;
	
	flush();
	echo $sql . ' * ' . $a . '<br />';
	flush();
}
$db->sql_freeresult($result);

echo '<br /><br /><br />Total: ' . $total;
die();

/*
$sql = 'SELECT *
	FROM _members
	WHERE user_type NOT IN (' . USER_FOUNDER . ', ' . USER_IGNORE . ', ' . USER_INACTIVE . ')
	ORDER BY user_id';
$result = $db->sql_query($sql);

$total = 0;
while ($row = $db->sql_fetchrow($result))
{
	$sql = 'SELECT u.item, u.user_id, t.topic_ub
		FROM _members_unread u, _forum_topics t
		WHERE u.element = ' . UH_N . '
			AND u.item = t.topic_id
			AND u.user_id = ' . (int) $row['user_id'];
	$result2 = $db->sql_query($sql);
	
	while ($row2 = $db->sql_fetchrow($result2))
	{
		$sql = 'DELETE FROM _members_unread
			WHERE element = ' . UH_N . '
				AND item = ' . (int) $row2['item'] . '
				AND user_id NOT IN (SELECT user_id FROM _artists_fav WHERE ub = ' . $row2['topic_ub'] . ')';
		$db->sql_query($sql);
		
		$total++;
		
		flush();
		echo $sql . ' * ' . $db->sql_affectedrows() . '<br />';
		flush();
		
		$sql = 'SELECT user_id
			FROM _artists_fav
			WHERE user_id = ' . (int) $row2['user_id'] . '
				AND ub = ' . (int) $row2['topic_ub'];
		$result3 = $db->sql_query($sql);
		
		if (!$row3 = $db->sql_fetchrow($result3))
		{
			$sql = 'DELETE FROM _members_unread
				WHERE element = ' . UH_N . '
					AND item = ' . (int) $row2['item'] . '
					AND user_id = ' . (int) $row['user_id'];
			$db->sql_query($sql);
			$total++;
			
			flush();
			echo $sql . ' * ' . $db->sql_affectedrows() . '<br />';
			flush();
		}
		$db->sql_freeresult($result3);
		
	}
	$db->sql_freeresult($result2);
}
$db->sql_freeresult($result);

echo '<br /><br /><br />Total: ' . $total;
*/

?>