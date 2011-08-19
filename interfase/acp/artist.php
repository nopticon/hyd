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

_auth('founder');

if ($submit)
{
	require('./interfase/ftp.php');
	$ftp = new ftp();
	
	if (!$ftp->ftp_connect())
	{
		_die('Can not connnect');
	}
	
	if (!$ftp->ftp_login())
	{
		$ftp->ftp_quit();
		_die('Can not login');
	}
	
	$v = array('name' => '', 'local' => 0, 'location' => '', 'genre' => '', 'email' => '', 'www' => '', 'mods' => '');
	foreach ($v as $k => $vv)
	{
		${$k} = request_var($k, $vv);
	}
	
	$subdomain = get_subdomain($name);
	
	$insert = array(
		'a_active' => 1,
		'subdomain' => $subdomain,
		'name' => $name,
		'local' => (int) $local,
		'datetime' => time(),
		'location' => $location,
		'genre' => $genre,
		'email' => $email,
		'www' => str_replace('http://', '', $www)
	);
	$sql = 'INSERT INTO _artists' . $db->sql_build_array('INSERT', $insert);
	$db->sql_query($sql);
	
	$artist_id = $db->sql_nextid();
	
	// Cache
	$cache->delete('ub_list', 'a_records', 'ai_records', 'a_recent');
	set_config('max_artists', $config['max_artists'] + 1);
	
	// FTP
	a_mkdir('/artists/', $artist_id);
	
	a_mkdir('/artists/' . $artist_id, 'gallery');
	a_mkdir('/artists/' . $artist_id, 'media');
	a_mkdir('/artists/' . $artist_id, 'thumbnails');
	a_mkdir('/artists/' . $artist_id, 'x1');
	$ftp->ftp_quit();
	
	// Mods
	if (!empty($mods))
	{
		$usernames = array();
		
		$a_mods = explode("\n", $mods);
		foreach ($a_mods as $each)
		{
			$username_base = get_username_base($each);
			
			$sql = "SELECT *
				FROM _members
				WHERE username_base = '" . $db->sql_escape($username_base) . "'
					AND user_type NOT IN (" . USER_IGNORE . ", " . USER_INACTIVE . ")
					AND user_id <> 1";
			$result = $db->sql_query($sql);
			
			if (!$userdata = $db->sql_fetchrow($result))
			{
				continue;
			}
			$db->sql_freeresult($result);
			
			$db->sql_query('INSERT INTO _artists_auth (ub, user_id) VALUES (' . $artist_id . ', ' . $userdata['user_id'] .')');
			
			//
			$update = array('user_type' => USER_ARTIST, 'user_auth_control' => 1);
			
			if ($userdata['user_color'] == '4D5358')
			{
				$update['user_color'] = '492064';
			}
			
			if (!$userdata['user_rank'])
			{
				$update['user_rank'] = (int) $config['default_a_rank'];
			}
			
			$sql = 'UPDATE _members SET ' . $db->sql_build_array('UPDATE', $update) . '
				WHERE user_id = ' . (int) $userdata['user_id'] . '
					AND user_type NOT IN (' . USER_INACTIVE . ', ' . USER_IGNORE . ', ' . USER_FOUNDER . ')';
			$db->sql_query($sql);
		}
		// Loop end
	}
	
	// Alice notify
	$sql = 'SELECT *
		FROM _forum_posts
		WHERE post_id = 82553';
	$result = $db->sql_query($sql);
	
	if ($row = $db->sql_fetchrow($result))
	{
		$a_intro = 'En esta secci&oacute;n encontrar&aacute;s la actualizaci&oacute;n de las &uacute;ltimas bandas y artistas que tienen su espacio en Rock Republik.' . "\n\n";
		$a_format = "[sb] <strong> %s </strong>\n%s\n%s\n\nhttp://www.rockrepublik.net" . SDATA . "artists/%d/gallery/1.jpg [/sb]";
		$a_location = ($local) ? ((($location != '') ? $location . ', ' : '') . 'Guatemala') : $location;
		$a_data = sprintf($a_format, $name, $genre, $a_location, $artist_id);
		
		$row['post_text'] = str_replace("\r", '', $row['post_text']);
		$a_post = $a_intro . $a_data . str_replace($a_intro, '', $row['post_text']);
		
		$sql = "UPDATE _forum_posts
			SET post_text = '" . $db->sql_escape($a_post) . "', post_time = " . time() . "
			WHERE post_id = " . (int) $row['post_id'];
		$db->sql_query($sql);
		
		$sql = 'UPDATE _forum_topics
			SET topic_time = ' . time() . '
			WHERE topic_id = ' . (int) $row['topic_id'];
		$db->sql_query($sql);
	}
	$db->sql_freeresult($result);
	
	$user->save_unread(UH_T, $row['topic_id']);
	
	redirect(s_link('a', $subdomain));
}

function a_mkdir($path, $folder)
{
	global $ftp;
	
	$result = false;
	if (!empty($path))
	{
		$path = $ftp->dfolder() . 'data' . $path;
		$ftp->ftp_chdir($path);
	}
	
	if ($ftp->ftp_mkdir($folder))
	{
		if ($ftp->ftp_site('CHMOD 0777 ' . $folder))
		{
			$result = folder;
		}
	}
	else
	{
		_die('Can not create: ' . $folder);
	}
	
	return $result;
}

?>

<form action="<?php echo $u; ?>" method="post">
Nombre: <input type="text" name="name" value="" /><br />
Ubicacion: <input type="text" name="location" value="" /><br />
Local: <input type="checkbox" name="local" value="1" /><br />
Genero: <input type="text" name="genre" value="" /><br />
Email: <input type="text" name="email" value="" /><br />
Sitio web: <input type="text" name="www" value="" /><br />
Autorizados: <textarea name="mods" value=""></textarea>
<input type="submit" name="submit" value="Crear artista" />
</form>