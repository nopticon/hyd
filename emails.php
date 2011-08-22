<?php
/*
<Orion, a web development framework for RK.>
Copyright (C) <2011>  <Orion>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
define('IN_NUCLEO', true);
require('./interfase/common.php');

$user->init();
$user->setup();

$forum_id = 22;

$sql = 'SELECT *
	FROM _forum_topics
	WHERE forum_id = ' . (int) $forum_id;
$result = $db->sql_query($sql);

$a_topics = array();
while ($row = $db->sql_fetchrow($result)) {
	$topic_id = $row['topic_id'];
	
	echo '<strong>' . $row['topic_title'] . '</strong><br /><blockquote>';
	
	$sql = 'SELECT vd.vote_id, vd.vote_text, vd.vote_start, vd.vote_length, vr.vote_option_id, vr.vote_option_text, vr.vote_result
		FROM _poll_options vd, _poll_results vr
		WHERE vd.topic_id = ' . (int) $topic_id . '
			AND vr.vote_id = vd.vote_id
		ORDER BY vr.vote_option_order, vr.vote_option_id ASC';
	$result2 = $db->sql_query($sql);
	
	if ($vote_info = $db->sql_fetchrowset($result2)) {
		$vote_options = sizeof($vote_info);
		
		for ($i = 0; $i < $vote_options; $i++) {
			$subdomain = get_username_base($vote_info[$i]['vote_option_text']);
			
			echo '<h1>' . ucwords($subdomain) . '</h1><br /><blockquote>';
			
			$sql = "SELECT *
				FROM _artists
				WHERE subdomain = '" . $db->sql_escape($subdomain) . "'";
			$result3 = $db->sql_query($sql);
			
			$row3 = $db->sql_fetchrow($result3);
			$db->sql_freeresult($result3);
			
			$sql = 'SELECT m.username, m.user_email
				FROM _artists_auth a, _members m
				WHERE a.ub = ' . (int) $row3['ub'] . '
					AND a.user_id = m.user_id
				ORDER BY username';
			$result4 = $db->sql_query($sql);
			
			$ii = 0;
			while ($row4 = $db->sql_fetchrow($result4)) {
				echo (($ii) ? ', ' : '') . $row4['username'] . ' &lt;' . $row4['user_email'] . '&gt;';
				$ii++;
			}
			$db->sql_freeresult($result4);
			
			echo '</blockquote>';
		}
	}
	$db->sql_freeresult($result2);
	
	echo '</blockquote>';
}
$db->sql_freeresult($result);

die();

//
//
//

/*foreach ($topics as $topic_id => $row)
{
	$result = $db->sql_query($sql);

	if ($vote_info = $db->sql_fetchrowset($result))
	{
		$db->sql_freeresult($result);
		$vote_options = sizeof($vote_info);
		
		$sql = 'SELECT vote_id
			FROM _poll_voters
			WHERE vote_id = ' . (int) $vote_info[0]['vote_id'] . '
				AND vote_user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);

		$user_voted = ( $row = $db->sql_fetchrow($result) ) ? TRUE : 0;
		$db->sql_freeresult($result);

		$template->assign_block_vars('poll', array(
			'POLL_TITLE' => $vote_info[0]['vote_text'])
		);
		
		if ($user_voted)
		{
			$template->assign_block_vars('poll.results', array());
		}
		else
		{
			$template->assign_block_vars('poll.options', array(
				'S_VOTE_ACTION' => $topic_url)
			);

			
		}
	}
}*/

?>