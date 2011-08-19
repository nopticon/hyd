<?php
// -------------------------------------------------------------
// $Id: common.php,v 1.3 2006/02/06 07:56:33 Psychopsia Exp $
//
// FILENAME  : common.php
// STARTED   : Sat Dec 18, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

class common
{
	var $mode;
	var $manage;
	var $control;
	var $auth;
	
	function import_control()
	{
		global $control;
		
		$this->control = $control;
		return;
	}
	
	function export_control()
	{
		global $control;
		
		$control = $this->control;
		return;
	}
	
	function auth_access($member)
	{
		global $db, $user;
		
		if ($user->data['user_type'] == USER_FOUNDER)
		{
			return true;
		}
		
		if ($user->data['user_type'] == USER_ARTIST && $this->control->module == 'a')
		{
			$sql = 'SELECT *
				FROM _artists_auth
				WHERE user_id = ' . (int) $user->data['user_id'];
			$result = $db->sql_query($sql);
			
			$access = false;
			if ($row = $db->sql_fetchrow($result))
			{
				$access = true;
			}
			$db->sql_freeresult($result);
			
			return $access;
		}
		
		return false;
	}
	
	function check_method()
	{
		if (!in_array($this->mode, array_keys($this->methods)))
		{
			$this->mode = 'home';
		}
	}
	
	function check_manage()
	{
		if (empty($this->methods[$this->mode]) || /*!method_exists($this, $this->manage) || */!in_array($this->manage, $this->methods[$this->mode]))
		{
			$this->manage = 'home';
		}
	}
	
	function call_method()
	{
		return $this->{'_' . $this->mode . '_' . $this->manage}();
	}
	
	function e($msg = '')
	{
		global $db, $user;
		
		// GZip
		if (!isset($this->config['ob_gz']))
		{
			if (strstr($user->browser, 'compatible') || strstr($user->browser, 'Gecko'))
			{
				ob_start('ob_gzhandler');
				$this->config['ob_gz'] = true;
			}
		}
		
		// Headers
		header('Cache-Control: private, no-cache="set-cookie", pre-check=0, post-check=0');
		header('Expires: 0');
		header('Pragma: no-cache');
		
		//
		if (isset($user->lang[$msg]))
		{
			$msg = $user->lang[$msg];
		}
		$db->sql_close();
		
		echo $msg;
		die();
	}
}

?>