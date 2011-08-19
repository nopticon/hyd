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
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$name = request_var('name', '');
	
	$sql = "SELECT *
		FROM _artists
		WHERE name = '" . $db->sql_escape($name) . "'";
	$result = $db->sql_query($sql);
	
	if (!$a_data = $db->sql_fetchrow($result))
	{
		fatal_error();
	}
	$db->sql_freeresult($result);
	
	$emails = array();
	if (!empty($a_data['email']))
	{
		$emails[] = $a_data['email'];
	}
	
	$sql = 'SELECT m.user_id, m.user_email
		FROM _artists_auth a, _members m
		WHERE a.ub = ' . (int) $a_data['ub'] . '
			AND a.user_id = m.user_id';
	$result = $db->sql_query($sql);
	
	$mods = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$emails[] = $row['user_email'];
		$mods[] = $row['user_id'];
	}
	$db->sql_freeresult($result);
	
	if (count($mods))
	{
		foreach ($mods as $i => $each)
		{
			$sql = 'SELECT user_id
				FROM _artists_auth
				WHERE user_id = ' . $each;
			$result = $db->sql_query($sql);
			
			if ($db->sql_numrows($result) > 1)
			{
				unset($mods[$i]);
			}
			$db->sql_freeresult($result);
		}
	}
	
	if (count($mods))
	{
		$d_sql[] = 'UPDATE _members
			SET user_auth_control = 0
			WHERE user_id IN (' . implode(',', $mods) . ')';
	}
	
	$d_sql = array();
	
	$d_sql[] = 'DELETE FROM _artists_auth
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_fav
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_images
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_log
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_lyrics
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_posts
		WHERE post_ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_stats
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_viewers
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_voters
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _artists_votes
		WHERE ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _forum_topics
		WHERE topic_ub = ' . (int) $a_data['ub'];
	$d_sql[] = 'DELETE FROM _dl
		WHERE ub = ' . (int) $a_data['ub'];
	
	$sql = 'SELECT topic_id
		FROM _forum_topics
		WHERE topic_ub = ' . (int) $a_data['ub'];
	$result = $db->sql_query($sql);
	
	$topics = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$topics[] = $row['topic_id'];
	}
	$db->sql_freeresult($result);
	
	if (count($topics))
	{
		$d_sql[] = 'DELETE FROM _forum_posts
			WHERE topic_id IN (' . implode(',', $topics) . ')';
	}
	
	$sql = 'SELECT id
		FROM _dl
		WHERE ub = ' . (int) $a_data['ub'];
	$result = $db->sql_query($sql);
	
	$downloads = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$downloads[] = $row['id'];
	}
	$db->sql_freeresult($result);
	
	if (count($downloads))
	{
		$s_downloads = implode(',', $downloads);
		
		$d_sql[] = 'DELETE FROM _dl_fav
			WHERE dl_id IN (' . $s_downloads . ')';
		$d_sql[] = 'DELETE FROM _dl_posts
			WHERE download_id IN (' . $s_downloads . ')';
		$d_sql[] = 'DELETE FROM _dl_vote
			WHERE ud IN (' . $s_downloads . ')';
		$d_sql[] = 'DELETE FROM _dl_voters
			WHERE ud IN (' . $s_downloads . ')';
	}
	
	$d_sql[] = 'DELETE FROM _artists
		WHERE ub = ' . (int) $a_data['ub'];
	
	if (!s_dir('../data/artists/' . $a_data['ub']))
	{
		echo 'error en carpetas';
		return;
	}
	
	$db->sql_query($d_sql);
	
	//
	// Send email
	//
	if (count($emails))
	{
		require('./interfase/emailer.php');
		$emailer = new emailer();
		
		//
		$a_emails = array_unique($emails);
		
		$emailer->from('info@rockrepublik.net');
		$emailer->use_template('artist_deleted');
		$emailer->email_address($a_emails[0]);
		$emailer->bcc('info@rockrepublik.net');
		
		$cc_emails = array_splice($a_emails, 1);
		foreach ($cc_emails as $each_email)
		{
			$emailer->cc($each_email);
		}
		
		$emailer->assign_vars(array(
			'ARTIST' => $a_data['name'])
		);
		$emailer->send();
		$emailer->reset();
	}
	
	// Cache
	$cache->delete('ub_list', 'a_last_images');
	
	echo 'La banda ha sido eliminada y notificada.';
	
	echo '<pre>';
	print_r($a_emails);
	echo '</pre>';
	
	die();
}

function s_dir($path)
{
	if (!@file_exists($path))
	{
		echo 'No folder ' . $path;
		return false;
	}
	
	$fp = @opendir($path);
	while ($file = @readdir($fp))
	{
		if ($file == '.' || $file == '..')
		{
			continue;
		}
		
		$current_full_path = $path . '/' . $file;
		
		if (is_dir($current_full_path))
		{
			s_dir($current_full_path);
			continue;
		}
		
		if (!unlink($current_full_path))
		{
			return false;
		}
	}
	@closedir($fp);
	
	if (!rmdir($path))
	{
		return false;
	}
	
	return true;
	
}

?>

<form action="<?php echo $u; ?>" method="post">
<input type="text" name="name" value="" />
<input type="submit" name="submit" value="Eliminar artista" />
</form>