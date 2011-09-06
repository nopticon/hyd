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
	die('Rock Republik &copy; 2006');
}

if (class_exists('downloads'))
{
	return;
}

class downloads
{
	var $ud = array();
	var $ud_song = array();
	var $dl_data = array();
	var $filename = '';
	var $filepath = '';
	
	function dl_sql ($ub = '', $order = '')
	{
		global $db;
		
		$sql_ub = ($ub != '') ? ' WHERE ub = ' . (int) $ub . ' ' : '';
		$sql_order = ($order != '') ? ' ORDER BY ' . $order : '';
		
		$sql = 'SELECT *
			FROM _dl' . 
			$sql_ub . $sql_order;
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$this->ud_song[$row['ud']][] = $row;
		}
		$db->sql_freeresult($result);
		
		return;
	}
	
	function dl_type ($ud)
	{
		global $user;
		
		$type = 0;
		switch ($ud)
		{
			case E_UD_AUDIO:
				$type = array('lang' => $user->lang['AUDIO'], 'extension' => 'mp3', 'av' => 'Audio');
				break;
			case E_UD_VIDEO:
				$type = array('lang' => $user->lang['VIDEO'], 'extension' => 'wmv', 'av' => 'Video');
				break;
		}
		return $type;
	}
	
	function dl_setup ()
	{
		$download_id = intval(request_var('download_id', 0));
		if (!$download_id)
		{
			fatal_error();
		}
		
		global $db;
		
		$sql = 'SELECT d.*
			FROM _dl d
			LEFT JOIN _artists a ON d.ub = a.ub 
			WHERE d.id = ' . (int) $download_id . '
				AND d.ub = ' . (int) $this->data['ub'];
		$result = $db->sql_query($sql);
		
		if (!$this->dl_data = $db->sql_fetchrow($result))
		{
			fatal_error();
		}
		$db->sql_freeresult($result);
		
		$this->dl_data += $this->dl_type($this->dl_data['ud']);
		return;
	}
	
	function dl_view ()
	{
		global $user, $config, $template;
		
		if (!$this->auth['adm'] && !$this->auth['mod'])
		{
			$db->sql_query('UPDATE _dl SET views = views + 1 WHERE id = ' . (int) $this->dl_data['id']);
		}
		
		$stats_text = '';
		foreach (array('views' => 'VIEW', 'downloads' => 'DL') as $item => $stats_lang)
		{
			$stats_text .= (($stats_text != '') ? ', ' : '') . '<strong>' . $this->dl_data[$item] . '</strong> ' . $user->lang[$stats_lang] . (($this->dl_data[$item] > 1) ? 's' : '');
		}
		
		$template->assign_vars(array(
			'S_DOWNLOAD_ACTION' => s_link('a', array($this->data['subdomain'], 9, $this->dl_data['id'], 'save')),
			
			'DL_ID' => $this->dl_data['id'],
			'DL_A' => $this->data['ub'],
			'DL_TITLE' => $this->dl_data['title'],
			'DL_FORMAT' => $this->dl_data['av'],
			'DL_DURATION' => $this->dl_data['duration'],
			'DL_ALBUM' => $this->dl_data['album'],
			'DL_YEAR' => $this->dl_data['year'],
			'DL_POSTS' => $this->dl_data['posts'],
			'DL_VOTES' => $this->dl_data['votes'],
			'DL_FILESIZE' => $this->format_filesize($this->dl_data['filesize']),
			'DL_STATS' => $stats_text)
		);
		
		//
		// FAV
		//
		$is_fav = FALSE;
		$sql = 'SELECT dl_id
			FROM _dl_fav
			WHERE dl_id = ' . $this->dl_data['id'] . '
				AND user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$is_fav = TRUE;
		}
		$db->sql_freeresult($result);
		
		if (!$is_fav)
		{
			$template->assign_block_vars('dl_fav', array(
				'URL' => s_link('a', array($this->data['subdomain'], 9, $this->dl_data['id'], 'fav')))
			);
		}
		
		//
		// UD POLL
		//
		$user_voted = FALSE;
		if ($this->dl_data['votes'] && $this->auth['user'] && !$this->auth['adm'] && !$this->auth['mod'])
		{
			$sql = 'SELECT *
				FROM _dl_voters
				WHERE ud = ' . (int) $this->dl_data['id'] . '
					AND user_id = ' . (int) $user->data['user_id'];
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$user_voted = TRUE;
			}
			$db->sql_freeresult($result);
		}
		
		$template->assign_block_vars('ud_poll', array());
		
		if ($this->auth['adm'] || $this->auth['mod'] || !$this->auth['user'] || $user_voted)
		{
			$sql = 'SELECT option_id, vote_result
				FROM _dl_vote
				WHERE ud = ' . (int) $this->dl_data['id'] . '
				ORDER BY option_id';
			$result = $db->sql_query($sql);
			
			$results = array();
			while ($row = $db->sql_fetchrow($result))
			{
				$results[$row['option_id']] = $row['vote_result'];
			}
			$db->sql_freeresult($result);
			
			$template->assign_block_vars('ud_poll.results', array());
			
			for ($i = 0, $end = sizeof($this->voting['ud']); $i < $end; $i++)
			{
				$vote_result = (isset($results[$this->voting['ub'][$i]])) ? (int) $results[$this->voting['ub'][$i]] : 0;
				$vote_percent = ($this->dl_data['votes'] > 0) ? $vote_result / $this->dl_data['votes'] : 0;

				$template->assign_block_vars('ud_poll.results.item', array(
					'CAPTION' => $user->lang['UB_UDV' . $this->voting['ud'][$i]],
					'RESULT' => $vote_result,
					'PERCENT' => sprintf("%.1d", ($vote_percent * 100)))
				);
			}
		}
		else
		{
			$template->assign_block_vars('ud_poll.options', array(
				'S_VOTE_ACTION' => s_link('a', array($this->data['subdomain'], 9, $this->dl_data['id'], 'vote')))
			);
			
			for ($i = 0, $end = sizeof($this->voting['ud']); $i < $end; $i++)
			{
				$template->assign_block_vars('ud_poll.options.item', array(
					'ID' => $this->voting['ud'][$i],
					'CAPTION' => $user->lang['UB_UDV' . $this->voting['ud'][$i]])
				);
			}
		}
		
		//
		// UD MESSAGES
		//
		$comments_ref = s_link('a', array($this->data['subdomain'], 9, $this->dl_data['id']));
		
		if ($this->dl_data['posts'])
		{
			$start = intval(request_var('dps', 0));
			$this->msg->ref = $comments_ref;
			$this->msg->auth = $this->auth;
			
			$this->msg->data = array(
				'A_LINKS_CLASS' => 'bold orange',
				'SQL' => 'SELECT p.*, u.user_id, u.username, u.username_base, u.user_color, u.user_avatar
					FROM _dl d, _dl_posts p, _artists a, _members u
					WHERE d.id = ' . (int) $this->dl_data['id'] . '
						AND d.ub = ' . (int) $this->data['ub'] . '
						AND d.id = p.download_id 
						AND d.ub = a.ub 
						AND p.post_active = 1 
						AND p.poster_id = u.user_id 
					ORDER BY p.post_time DESC 
					LIMIT ' . (int) $start . ', ' . $config['s_posts']
			);
			
			if ($this->auth['user'])
			{
				$this->msg->data['CONTROL']['reply'] = array(
					'REPLY' => array(
						'URL' => s_link('a', array($this->data['subdomain'], 12, '%d', 'reply')),
						'ID' => 'post_id'
					)
				);
			}
			
			if ($this->auth['user'] && !$this->auth['adm'] && !$this->auth['mod'])
			{
				$this->msg->data['CONTROL']['report'] = array(
					'REPORT' => array(
						'URL' => s_link('a', array($this->data['subdomain'], 12, '%d', 'report')),
						'ID' => 'post_id'
					)
				);
			}
			
			if ($this->auth['adm'] || $this->auth['mod'])
			{
				$this->msg->data['CONTROL']['auth'] = array();
				
				if ($this->auth['adm'] && $user->data['is_founder'])
				{
					$this->msg->data['CONTROL']['auth']['EDIT'] = array(
						'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'dposts', 'manage' => 'edit', 'id' => '%d')),
						'ID' => 'post_id'
					);
				}
				
				$this->msg->data['CONTROL']['auth']['DELETE'] = array(
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => 'dposts', 'manage' => 'delete', 'id' => '%d')),
					'ID' => 'post_id'
				);
			}
			
			//
			$this->msg->view($start, 'dps', $this->dl_data['posts'], $config['s_posts'], 'ud_posts', 'DMSG_', 'TOPIC_', FALSE);
		}
		
		if ($this->auth['post'])
		{
			if ($this->auth['user'])
			{
				$template->assign_block_vars('dl_post_box', array(
					'REF' => $comments_ref,
					'NL' => (int) !$this->auth['user'])
				);
			}
			else
			{
				$template->assign_block_vars('dl_no_guest_posting', array(
					'LEGEND' => sprintf($user->lang['UB_NO_GUEST_POSTING'], $this->data['name'], s_link('my', 'register')))
				);
			}
		}
		else
		{
			$template->assign_block_vars('dl_no_post_auth', array());
			
			if ($this->auth['post_until'])
			{
				$template->assign_block_vars('dl_no_post_auth.until', array(
					'UNTIL_DATETIME' => $user->format_date($this->auth['post_until']))
				);
			}
		}
		
		return;
	}
	
	function dl_save ()
	{
		global $db;
		
		$db->sql_query('UPDATE _dl SET downloads = downloads + 1 WHERE id = ' . (int) $this->dl_data['id']);
		
		$orig = array('�', '�', '.');
		$repl = array('n', 'N', '');
		
		$this->filename = str_replace($orig, $repl, $this->data['name']) . '_' . str_replace($orig, $repl, $this->dl_data['title']) . '.' . $this->dl_data['extension'];
		$this->filepath = 'data/artists/' . $this->data['ub'] . '/media/' . $this->dl_data['id'] . '.' . $this->dl_data['extension'];
		$this->dl_file();
		
		return;
	}
	
	function dl_vote ()
	{
		if (!$this->auth['user'])
		{
			do_login();
		}
		
		global $user;
		
		$option_id = intval(request_var('vote_id', 0));
		$url = s_link('a', array($this->data['subdomain'], 9, $this->dl_data['id']));
		
		if ($this->auth['adm'] || $this->auth['mod'] || !in_array($option_id, $this->voting['ud']))
		{
			redirect($url);
		}
		
		$user_voted = FALSE;
		$sql = 'SELECT *
			FROM _dl_voters
			WHERE ud = ' . (int) $this->dl_data['id'] . '
				AND user_id = ' . (int) $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$user_voted = TRUE;
			
			$db->sql_freeresult($result);
		}
		
		if ($user_voted)
		{
			redirect($url);
		}
		
		$sql = 'UPDATE _dl_vote
			SET vote_result = vote_result + 1
			WHERE ud = ' . (int) $this->dl_data['id'] . '
				AND option_id = ' . (int) $option_id;
		$db->sql_query($sql);
		
		if (!$db->sql_affectedrows())
		{
			$db->sql_query('INSERT INTO _dl_vote (ud, option_id, vote_result) VALUES (' . (int) $this->dl_data['id'] . ', ' . (int) $option_id . ', 1)');
		}
		
		$db->sql_query('INSERT INTO _dl_voters (ud, user_id, user_option) VALUES (' . (int) $this->dl_data['id'] . ', ' . $user->data['user_id'] . ', ' . (int) $option_id . ')');
		$db->sql_query('UPDATE _dl SET votes = votes + 1 WHERE id = ' . (int) $this->dl_data['id']);
		
		redirect($url);
	}
	
	function dl_fav ()
	{
		if (!$this->auth['user'])
		{
			do_login();
		}
		
		global $user;
		
		$is_fav = FALSE;
		$sql = 'SELECT dl_id
			FROM _dl_fav
			WHERE dl_id = ' . $this->dl_data['id'] . '
				AND user_id = ' . $user->data['user_id'];
		$result = $db->sql_query($sql);
		
		if ($row = $db->sql_fetchrow($result))
		{
			$is_fav = TRUE;
		}
		$db->sql_freeresult($result);
		
		$url = s_link('a', array($this->data['subdomain'], 9, $this->dl_data['id']));
		
		if ($is_fav)
		{
			redirect($url);
		}
		
		$db->sql_query('INSERT INTO _dl_fav (dl_id, user_id, favtime) VALUES (' . (int) $this->dl_data['id'] . ', ' . $user->data['user_id'] . ', ' . time() . ')');
		$db->sql_query('UPDATE _members SET user_dl_favs = user_dl_favs + 1 WHERE user_id = ' . (int) $user->data['user_id']);
		
		redirect($url);
		
		return;
	}
	
	function dl_file ($name = '', $path = '', $data = '', $content_type = 'application/octet-stream', $disposition = 'attachment')
	{
		global $db;
		
		if (isset($db))
		{
			$db->sql_close();
		}
		
		$bad_chars = array("'", "\\", ' ', '/', ':', '*', '?', '"', '<', '>', '|');
		
		$this->filename = ($name != '') ? $name : $this->filename;
		$this->filepath = ($path != '') ? $path : $this->filepath;
		
		$this->filename = rawurlencode(str_replace($bad_chars, '_', $this->filename));
		$this->filename = 'RockRepublik__' . preg_replace("/%(\w{2})/", '_', $this->filename);
		
		// Headers
		header('Content-Type: ' . $content_type . '; name="' . $this->filename . '"');
		header('Content-Disposition: ' . $disposition . '; filename="' . $this->filename . '"');
		header('Accept-Ranges: bytes');
		header('Pragma: no-cache');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-transfer-encoding: binary');
		
		if ($data == '')
		{
			$this->filepath = '../' . $this->filepath;
			
			header('Content-length: ' . @filesize($this->filepath));
			@readfile($this->filepath);
		}
		else
		{
			print($data);
		}
		
		flush();
		exit();
	}
	
	function format_filesize ($filesize)
	{
		$mb = ($filesize >= 1048576) ? TRUE : FALSE;
		$div = ($mb) ? 1048576 : 1024;
		return bcdiv($filesize, $div, 2) . (($mb) ? ' MB' : ' KB');
	}
}

?>