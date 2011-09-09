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
if (!defined('IN_NUCLEO')) exit;

class control {
	var $vars = array();
	var $modules = array();
	var $data = array();
	var $_nav = array();
	
	var $module_path;
	var $module;
	
	var $htmlfile;
	
	//
	// Constructor
	//
	function control($module) {
		if ($module != '') {
			$this->module = $module;
			$this->module_path = './control/m_' . $this->module . '.php';
			
			if (!kernel_function('a', $this->module_path, false, false)) {
				redirect(s_link('control'));
			}
		}
		
		$this->parse_vars();
		
		return;
	}
	
	//
	// Get value of control var
	//
	function get_var($var_name, $default, $multibyte = false) {
		if (!isset($this->vars[$var_name]) || (is_array($this->vars[$var_name]) && !is_array($default)) || (is_array($default) && !is_array($this->vars[$var_name])))
		{
			return (is_array($default)) ? array() : $default;
		}
	
		$var = $this->vars[$var_name];
		if (!is_array($default))
		{
			$type = gettype($default);
		}
		else
		{
			list($key_type, $type) = each($default);
			$type = gettype($type);
			$key_type = gettype($key_type);
		}
	
		if (is_array($var))
		{
			$_var = $var;
			$var = array();
	
			foreach ($_var as $k => $v)
			{
				if (is_array($v))
				{
					foreach ($v as $_k => $_v)
					{
						set_var($k, $k, $key_type);
						set_var($_k, $_k, $key_type);
						set_var($var[$k][$_k], $_v, $type, $multibyte);
					}
				}
				else
				{
					set_var($k, $k, $key_type);
					set_var($var[$k], $v, $type, $multibyte);
				}
			}
		}
		else
		{
			set_var($var, $var, $type, $multibyte);
		}
		
		return $var;
	}
	
	function parse_vars()
	{
		$params = request_var('params', '');
		if ($params != '')
		{
			$params = explode('.', $params);
			
			foreach ($params as $input)
			{
				$data = explode('-', $input);
				if (isset($data[0]) && isset($data[1]) && !empty($data[0]))
				{
					$this->vars[$data[0]] = $data[1];
				}
			}
		}
		
		if (isset($_POST))
		{
			_utf8($_POST);
			$this->vars = array_merge($this->vars, $_POST);
		}
		
		return;
	}
	
	function _modules()
	{
		$mod_init = TRUE;
		$fp = @opendir('./control/');
		while ($file = @readdir($fp))
		{
			if (preg_match('#^m_([a-z]+)\.php$#', $file, $mod))
			{
				include_once($file);
//				if (isset($data))
				{
					//$this->modules[$mod[1]] = $data;
					$this->modules[$mod[1]] = '';
				}
			}
		}
		@closedir($fp);
		
		return;
	}
	
	function panel()
	{
		global $user, $template;
		
		$this->htmlfile = 'control_body';
		
		$this->_modules();
		
		if (!$user->data['is_founder'])
		{
			$this->modules = array(
				'a' => ''
			);
		}
		
		$template->assign_block_vars('list', array());
		
		foreach ($this->modules as $k => $v)
		{
			$template->assign_block_vars('list.item', array(
				'NAME' => $user->lang['CONTROL_' . strtoupper($k)],
				'URL' => s_link_control($k))
			);
		}
		
		return;
	}
	
	function set_nav($url, $name)
	{
		global $user;
		
		$name = (isset($user->lang[$name])) ? $user->lang[$name] : $name;
		
		$this->_nav[] = array('URL' => s_link_control($this->module, $url), 'NAME' => $name);
		
		return;
	}
	
	function display_nav()
	{
		global $user;
		
		$nav_legend = '';
		foreach ($this->_nav as $data)
		{
			if ($data['NAME'] == '')
			{
				continue;
			}
			
			$item_lang = (isset($user->lang['CONTROL_' . strtoupper($data['NAME'])])) ? $user->lang['CONTROL_' . strtoupper($data['NAME'])] : $data['NAME'];
			$nav_legend .= (($nav_legend != '') ? ' &raquo; ' : '') . '<a href="' . $data['URL'] . '">' . $item_lang . '</a>';
		}
		
		return $nav_legend;
	}
}

?>