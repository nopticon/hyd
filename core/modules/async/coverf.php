<?php namespace App;

$user->setup();

$sql = 'SELECT t.topic_id, t.topic_title, t.forum_id, t.topic_replies,
        f.forum_name, p.post_id, p.post_username, p.post_time, u.user_id, u.username, u.username_base
    FROM _forums f, _forum_topics t, _forum_posts p, _members u
    WHERE t.forum_id NOT IN (??)
        AND t.forum_id = f.forum_id
        AND p.post_id = t.topic_last_post_id
        AND p.poster_id = u.user_id
    ORDER BY p.post_id DESC
    LIMIT ??';
if (!$result = sql_rowset(sql_filter($sql, config('ub_fans_f'), config('main_topics')))) {
    exit;
}

$top = sprintf(lang('top_forum'), count($result));

?>

<div class="head">
    <img src="/net/icons/forum.gif" width="18" height="18" alt="<?php echo $top; ?>" title="<?php echo $top; ?>" />
    <?php echo $top; ?>
</div>

<div class="ie-widthfix">
    <table width="100%" class="t-collapse" cellpadding="5">
        <?php

        $i = 0;

        foreach ($result as $row) {
            $username = ($row['user_id'] != GUEST) ? $row['username'] : guestUsername($row['post_username']);

            if ($row['user_id'] != 1) {
                $user = '<a href="' . s_link('m', $row['username_base']) . '">' . $username . '</a>' : 0;
            } else {
                $user = '<span>*' . $username . '</span>';
            }

            $u_topic = $row['topic_replies'] ? s_link('post', $row['post_id']) . '#' . $row['post_id'] : 0;
            $u_topic = $u_topic ?: s_link('topic', $row['topic_id']);

            $link = s_link('forum', $row['forum_id']);

        ?>
        <tr<?php echo (($i % 2) ? ' class="dark-color"' : ''); ?>>
            <td valign="top">
                <a href="<?php echo $u_topic; ?>"><?php echo $row['topic_title']; ?></a>
                <span class="soft">|</span> <?php echo $row['topic_replies']; ?>m<br />
                <?php echo lang('in'); ?>
                <a class="bold" href="<?php echo $link; ?>"><?php echo $row['forum_name']; ?></a>
            </td>
            <td width="15%" align="right" nowrap>
                <?php $user ?><br />
                <?php echo $user->format_date($row['post_time']); ?>
            </td>
        </tr>
        <?php

            $i++;
        }
        ?>
    </table>
</div>
