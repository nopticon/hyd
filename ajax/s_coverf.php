<?php
// -------------------------------------------------------------
// $Id: s_coverf.php,v 1.1 2006/03/23 00:04:37 Psychopsia Exp $
//
// STARTED   : Mon Dec 20, 2004
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

define('IN_NUCLEO', true);
define('ROOT', './../');
require('./../interfase/common.php');
require('./../interfase/artists.php');

$user->init(false);
$user->setup();

//
//
$sql = 'SELECT t.topic_id, t.topic_title, t.forum_id, t.topic_replies, f.forum_name, p.post_id, p.post_username, p.post_time, u.user_id, u.username, u.username_base, u.user_color
	FROM _forums f, _forum_topics t, _forum_posts p, _members u
	WHERE t.forum_id NOT IN (' . $config['ub_fans_f'] . ')
		AND t.forum_id = f.forum_id
		AND p.post_id = t.topic_last_post_id
		AND p.poster_id = u.user_id
	ORDER BY p.post_id DESC
	LIMIT ' . $config['main_topics'];
$result = $db->sql_query($sql);

if ($row = $db->sql_fetchrow($result))
{
	$top_posts = sprintf($user->lang['TOP_FORUM'], $db->sql_numrows($result));
?>
<div class="head"><img src="/net/icons/forum.gif" width="18" height="18" alt="<?php echo $top_posts; ?>" title="<?php echo $top_posts; ?>" /><?php echo $top_posts; ?><div class="head-normal">&nbsp;| <?php echo $config['max_posts']; ?>m</div></div>
<div class="ie-widthfix">
<table width="100%" class="t-collapse" cellpadding="5">
<?php
	$i = 0;
	do
	{
		$username = ($row['user_id'] != GUEST) ? $row['username'] : (($row['post_username'] != '') ? $row['post_username'] : $user->lang['GUEST']);
		$u_topic = ($row['topic_replies']) ? s_link('post', $row['post_id']) . '#' . $row['post_id'] : s_link('topic', $row['topic_id']);
?>
	<tr<?php echo (($i % 2) ? ' class="dark-color"' : ''); ?>>
		<td valign="top"><a href="<?php echo $u_topic; ?>" class="red bold"><?php echo $row['topic_title']; ?></a> <span class="soft">|</span> <?php echo $row['topic_replies']; ?>m<br /><?php echo $user->lang['IN']; ?> <a class="bold" href="<?php echo s_link('forum', $row['forum_id']); ?>"><?php echo $row['forum_name']; ?></a></td>
		<td width="15%" align="right" nowrap><?php if ($row['user_id'] != 1) { echo '<a style="color:#' . $row['user_color'] . '; font-weight: bold" href="' . s_link('m', $row['username_base']) . '">' . $username . '</a>'; } else { echo '<span style="color:#' . $row['user_color'] . '; font-weight: bold">*' . $username . '</span>'; } ?><br /><?php echo $user->format_date($row['post_time']); ?></td>
	</tr>
<?php
		$i++;
	}
	while ($row = $db->sql_fetchrow($result));
?>
</table>
</div>
<?php
}
$db->sql_freeresult($result);

?>