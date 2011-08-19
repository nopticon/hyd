<?php
// -------------------------------------------------------------
// $Id: mass_email.php,v 1.12 2006/11/02 00:20:00 Psychopsia Exp $
//
// STARTED   : Mon Oct 23, 2006
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	exit();
}

_auth('founder');

if ($submit)
{
	$folder = request_var('folder', '');
	$list = request_var('list', '');
	
	//
	// Folder
	if (!empty($folder))
	{
		$real_path = '../net/smiles/' . $folder;
		
		$images = array();
		
		$fp = @opendir($real_path);
		while ($file = @readdir($fp))
		{
			if (preg_match('#([a-z0-9]+)\.(gif|png)#is', $file, $split))
			{
				$images[] = $split;
			}
		}
		@closedir($fp);
		
		$emots = array();
		$skip = array();
		$process = array();
		
		$sql = 'SELECT *
			FROM _smilies
			ORDER BY code';
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$emots[$row['code']] = $row;
		}
		$db->sql_freeresult($result);
		
		//
		foreach ($images as $each)
		{
			$code = ':' . $each[1] . ':';
			
			if (isset($emots[$code]))
			{
				$skip[] = $code;
				continue;
			}
			
			$path = $folder . '/' . $each[0];
			
			$insert = array(
				'code' => $code,
				'smile_url' => $path
			);
			$sql = 'INSERT INTO _smilies' . $db->sql_build_array('INSERT', $insert);
			$db->sql_query($sql);
			
			$process[] = $insert;
		}
		
		$cache->delete('smilies');
		
		echo '<pre>';
		print_r($process);
		echo '<br /><br />';
		print_r($skip);
		echo '</pre>';
		
		die();
	}
	
	//
	// List
	
}

?><html>
<head>
<title>Insert emots</title>
</head>

<body>
<form action="<?php echo $u; ?>" method="post">
Folder: <input type="text" name="folder" /><br />
<input type="submit" name="submit" value="Enviar" />
</form>
</body>
</html>