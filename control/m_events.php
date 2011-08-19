<?php
// -------------------------------------------------------------
// $Id: m_events.php,v 1.2 2006/02/06 07:56:33 Psychopsia Exp $
//
// FILENAME  : m_.php
// STARTED   : Sat Dec 18, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

class events extends common
{
	var $data = array();
	var $methods = array(
		'manage' => array('add', 'edit', 'delete'),
		'images' => array('add', 'edit', 'delete'),
		'messages' => array('edit', 'delete')
	);
	
	function events()
	{
		return;
	}
	
	function setup()
	{
		global $db;
		
		$event_id = $this->control->get_var('id', 0);
		if ($event_id)
		{
			$sql = 'SELECT *
				FROM _events
				WHERE id = ' . (int) $event_id;
			$result = $db->sql_query($sql);
			
			if ($row = $db->sql_fetchrow($result))
			{
				$row['id'] = (int) $row['id'];
				$this->data = $row;
				
				$db->sql_freeresult($result);
				
				return true;
			}
		}
		
		return false;
	}
	
	function home()
	{
		global $db, $user, $template;
		
		if ($this->setup())
		{
			$template->assign_block_vars('menu', array());
			foreach ($this->methods as $module => $void)
			{
				$template->assign_block_vars('menu.item', array(
					'URL' => s_link_control('a', array('a' => $this->data['subdomain'], 'mode' => $module)),
					'NAME' => $user->lang['CONTROL_A_' . strtoupper($module)])
				);
			}
			
//			$this->nav();
		}
	}
}

?>