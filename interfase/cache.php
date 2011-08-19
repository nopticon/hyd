<?php
// -------------------------------------------------------------
// $Id: cache.php,v 1.3 2006/02/14 01:44:49 Psychopsia Exp $
//
// FILENAME  : cache.php
// STARTED   : Wed Jan 26, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

class cache
{
	var $cache = array();
	var $use = true;
	
	function cache()
	{
		if (!defined('USE_CACHE'))
		{
			$this->use = false;
		}
	}
	
	function config()
	{
		global $db;
		
		$sql = 'SELECT *
			FROM _config';
		$result = $db->sql_query($sql);
		
		$config = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$config[$row['config_name']] = $row['config_value'];
		}
		$db->sql_freeresult($result);
		
		$config['request_method'] = strtolower($_SERVER['REQUEST_METHOD']);
		//$config['server_name'] .= '/orion';
		
		return $config;
	}
	
	function get($var)
	{
		if (!$this->use)
		{
			return false;
		}
		
		$filename = './cache/' . /*md5*/($var) . '.php';
		
		if (@file_exists($filename))
		{
			if (!@include($filename))
			{
				$this->delete($var);
				return;
			}
			
			if (!empty($this->cache[$var]))
			{
				return $this->cache[$var];
			}
			
			return true;
		}
		
		return;
	}
	
	function save($var, &$data)
	{
		if (!$this->use)
		{
			return;
		}
		
		$filename = './cache/' . /*md5*/($var) . '.php';
		
		$fp = @fopen($filename, 'w');
		if ($fp)
		{
			$file_buffer = '<?php $' . 'this->cache[\'' . $var . '\'] = ' . ((is_array($data)) ? $this->format($data) : "'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $data)) . "'") . '; ?>';
			
			@flock($fp, LOCK_EX);
			fputs($fp, $file_buffer);
			@flock($fp, LOCK_UN);
			fclose($fp);
			
			@chmod($filename, 0777);
		}
		
		return;
	}
	
	function delete()
	{
		if (!$this->use)
		{
			return;
		}
		
		foreach (func_get_args() as $var)
		{
			$cache_filename = './cache/' . /*md5*/($var) . '.php';
			if (file_exists($cache_filename))
			{
				@unlink($cache_filename);
			}
		}
		
		return;
	}
	
	//
	// Borrowed from phpBB 2.2 : acm_file.php
	//
	function format($data)
	{
		$lines = array();
		foreach ($data as $k => $v)
		{
			if (is_array($v))
			{
				$lines[] = "'$k'=>" . $this->format($v);
			}
			elseif (is_int($v))
			{
				$lines[] = "'$k'=>$v";
			}
			elseif (is_bool($v))
			{
				$lines[] = "'$k'=>" . (($v) ? 'TRUE' : 'FALSE');
			}
			else
			{
				$lines[] = "'$k'=>'" . str_replace("'", "\\'", str_replace('\\', '\\\\', $v)) . "'";
			}
		}
		return 'array(' . implode(',', $lines) . ')';
	}
}

?>