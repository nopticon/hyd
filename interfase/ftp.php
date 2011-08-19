<?php
// -------------------------------------------------------------
// $Id: ftp.php,v 1.2 2006/02/06 08:05:11 Psychopsia Exp $
//
// STARTED   : Tue Jul 12, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

class ftp
{
	var $conn_id;
	var $def = array();
	
	function ftp()
	{
		global $config;
		
		define('FTP_ASCII', 0);
		define('FTP_BINARY', 1);
		
		// Decode file
		if (@file_exists(ROOT . '.htfda') && $a = @file(ROOT . '.htfda'))
		{
			// server.user.pwd.folder
			$d = explode(',', _decode($a[0]));
			foreach (array('server', 'user', 'passwd', 'folder') as $i => $row)
			{
				$this->def[$row] = _decode($d[$i]);
			}
		}
		
		return;
	}
	
	function ftp_connect($host = false, $port = false, $timeout = 10)
	{
		$host = ($host !== false) ? $host : $this->def['server'];
		$port = ($port !== false) ? $port : 21;
		
		$this->conn_id = ftp_connect($host, $port, $timeout);
		return $this->conn_id;
	}
	
	function ftp_login($ftp_user = false, $ftp_pass = false)
	{
		$ftp_user = ($ftp_user !== false) ? $ftp_user : $this->def['user'];
		$ftp_pass = ($ftp_pass !== false) ? $ftp_pass : $this->def['passwd'];
		
		return @ftp_login($this->conn_id, $ftp_user, $ftp_pass);
	}
	
	function dfolder()
	{
		return $this->def['folder'];
	}
	
	function ftp_quit()
	{
		if ($this->conn_id)
		{
			@ftp_close($this->conn_id);
		}
		
		return;
	}
	
	function ftp_pwd()
	{
		return @ftp_pwd($this->conn_id);
	}
	
	function ftp_nlist($d = './')
	{
		return @ftp_nlist($this->conn_id, $d);
	}
	
	function ftp_chdir($ftp_dir)
	{
		return @ftp_chdir($this->conn_id, $ftp_dir);
	}
	
	function ftp_mkdir($ftp_dir)
	{
		return @ftp_mkdir($this->conn_id, $ftp_dir);
	}
	
	function ftp_site($cmd)
	{
		return @ftp_site($this->conn_id, $cmd);
	}
	
	function ftp_cdup()
	{
		return @ftp_cdup($this->conn_id);
	}
	
	function ftp_put($remote_file, $local_file)
	{
		if (!file_exists($local_file))
		{
			return false;
		}
		
		return @ftp_put($this->conn_id, $remote_file, $local_file, FTP_BINARY);
	}
	
	function ftp_rename($src, $dest)
	{
		return @ftp_rename($this->conn_id, $src, $dest);
	}
}

?>