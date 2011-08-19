<?php
// -------------------------------------------------------------
// $Id: m_comments.php,v 1.2 2006/02/06 07:56:33 Psychopsia Exp $
//
// FILENAME  : m_.php
// STARTED   : Sat Dec 18, 2005
// COPYRIGHT : © 2006 Rock Republik
// -------------------------------------------------------------
if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy; 2006');
}

class comments extends common
{
	var $methods = array(
		'emoticons' => array('add', 'edit', 'delete'),
		'help' => array('add', 'edit', 'delete')
	);
	
	function comments()
	{
		return;
	}
	
	function _emoticons_setup()
	{
		
	}
	
	function _help_setup()
	{
		
	}
	
	function nav()
	{
		global $user;
		
		$this->control->set_nav(array('mode' => $this->mode), $user->lang['CONTROL_COMMENTS_' . strtoupper($this->mode)]);
	}
	
	function home()
	{
		global $user, $template;
		
		$template->assign_block_vars('menu', array());
		foreach ($this->methods as $module => $void)
		{
			$template->assign_block_vars('menu.item', array(
				'URL' => s_link_control('comments', array('mode' => $module)),
				'TITLE' => $user->lang['CONTROL_COMMENTS_' . strtoupper($module)])
			);
		}
		
		return;
	}
	
	//
	// Emoticons
	//
	function emoticons()
	{
		$this->call_method();
	}
	
	function _emoticons_home()
	{
		die('_emoticons_home');
	}
	
	function _emoticons_add()
	{
		die('_emoticons_add');
	}
	
	function _emoticons_edit()
	{
		die('_emoticons_edit');
	}
	
	function emoticons_delete()
	{
		die('_emoticons_delete');
	}
	
	//
	// Help
	//
	function help()
	{
		$this->call_method();
	}
	
	function _help_home()
	{
		global $db, $user, $template;
		
		include('./interfase/comments.php');
		$comments = new _comments();
		
		$ha = $this->auth->query('comments');
		
		if ($ha)
		{
			$ha_add = $this->auth->option(array('help', 'add'));
			$ha_edit = $this->auth->option(array('help', 'edit'));
			$ha_delete = $this->auth->option(array('help', 'delete'));
		}
		
		$sql = 'SELECT c.*, m.*
			FROM _help_cat c, _help_modules m
			WHERE c.help_module = m.module_id
			ORDER BY c.help_order';
		$result = $db->sql_query($sql);
		
		$cat = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$cat[$row['help_id']] = $row;
		}
		$db->sql_freeresult($result);
		
		$sql = 'SELECT *
			FROM _help_faq';
		$result = $db->sql_query($sql);
		
		$faq = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$faq[$row['faq_id']] = $row;
		}
		$db->sql_freeresult($result);
		
		//
		// Loop
		//
		foreach ($cat as $help_id => $cdata)
		{
			$template->assign_block_vars('cat', array(
				'HELP_ES' => $cdata['help_es'],
				'HELP_EN' => $cdata['help_en'],
				
				'HELP_EDIT' => s_link_control('comments', array('mode' => $this->mode)),
				'HELP_UP' => s_link_control('comments', array('mode' => $this->mode)),
				'HELP_DOWN' => s_link_control('comments', array('mode' => $this->mode)))
			);
			
			if ($ha_edit)
			{
				$template->assign_block_vars('cat.edit', array(
					'URL' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'cat', 'id' => $help_id)),
					'UP' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'cat', 'id' => $help_id, 'order' => '_15')),
					'DOWN' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'cat', 'id' => $help_id, 'order' => '15')))
				);
			}
			
			if ($ha_delete)
			{
				$template->assign_block_vars('cat.delete', array(
					'URL' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'delete', 'sub' => 'cat', 'id' => $help_id)))
				);
			}
			
			foreach ($faq as $faq_id => $fdata)
			{
				if ($help_id != $fdata['help_id'])
				{
					continue;
				}
				
				$template->assign_block_vars('cat.faq', array(
					'QUESTION_ES' => $fdata['faq_question_es'],
					'ANSWER_ES' => $comments->parse_message($fdata['faq_answer_es']))
				);
				
				if ($ha_edit)
				{
					$template->assign_block_vars('cat.faq.edit', array(
						'URL' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'edit', 'sub' => 'faq', 'id' => $fdata['faq_id'])))
					);
				}
				
				if ($ha_delete)
				{
					$template->assign_block_vars('cat.faq.delete', array(
						'URL' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'delete', 'sub' => 'faq', 'id' => $fdata['faq_id'])))
					);
				}
			}
		}
		
		if ($ha_add)
		{
			$template->assign_block_vars('add', array(
				'URL' => s_link_control('comments', array('mode' => $this->mode, 'manage' => 'add')))
			);
		}
		
		$this->nav();
		
		return;
	}
	
	function _help_add()
	{
		global $db, $user, $cache, $template;
		
		$error = array();
		$sub = $this->control->get_var('sub', '');
		$submit = (isset($_POST['submit'])) ? TRUE : FALSE;
		
		$menu = array('module' => 'CONTROL_COMMENTS_HELP_MODULE', 'cat' => 'CATEGORY', 'faq' => 'FAQ');
		
		switch ($sub)
		{
			case 'cat':
				$module_id = 0;
				$help_es = '';
				$help_en = '';
				break;
			case 'faq':
				$help_id = 0;
				$question_es = '';
				$question_en = '';
				$answer_es = '';
				$answer_en = '';
				break;
			case 'module':
				$module_name = '';
				break;
			default:
				$template->assign_block_vars('menu', array());
				
				foreach ($menu as $url => $name)
				{
					$template->assign_block_vars('menu.item', array(
						'URL' => s_link_control('comments', array('mode' => $this->mode, 'manage' => $this->manage, 'sub' => $url)),
						'TITLE' => (isset($user->lang[$name])) ? $user->lang[$name] : $name)
					);
				}
				break;
		}
		
		if ($submit)
		{
			switch ($sub)
			{
				case 'cat':
					$module_id = $this->control->get_var('module_id', 0);
					$help_es = $this->control->get_var('help_es', '');
					$help_en = $this->control->get_var('help_en', '');
					
					if (empty($help_es) || empty($help_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					// Insert
					if (!sizeof($error))
					{
						$sql_insert = array(
							'help_module' => (int) $module_id,
							'help_es' => $help_es,
							'help_en' => $help_en
						);
						
						$sql = 'INSERT INTO _help_cat' . $db->sql_build_array('INSERT', $sql_insert);
					}
					break;
				case 'faq':
					$help_id = $this->control->get_var('help_id', 0);
					$question_es = $this->control->get_var('question_es', '');
					$question_en = $this->control->get_var('question_en', '');
					$answer_es = $this->control->get_var('answer_es', '');
					$answer_en = $this->control->get_var('answer_en', '');
					
					if (empty($question_es) || empty($question_en) || empty($answer_es) || empty($answer_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					if (!sizeof($error))
					{
						$sql_insert = array(
							'help_id' => $help_id,
							'faq_question_es' => $question_es,
							'faq_question_en' => $question_en,
							'faq_answer_es' => $answer_es,
							'faq_answer_en' => $answer_en
						);
						$sql = 'INSERT INTO _help_faq' . $db->sql_build_array('INSERT', $sql_insert);
					}
					break;
				case 'module':
					$module_name = $this->control->get_var('module_name', '');
					
					if (empty($module_name))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					if (!sizeof($error))
					{
						$sql_insert = array(
							'module_name' => $module_name
						);
						$sql = 'INSERT INTO _help_modules' . $db->sql_build_array('INSERT', $sql_insert);
					}
					break;
			}
			
			if (!sizeof($error))
			{
				$db->sql_query($sql);
				
				$cache->delete('help_cat', 'help_faq', 'help_modules');
				
				redirect(s_link_control('comments', array('mode' => $this->mode)));
			}
			else
			{
				$template->assign_block_vars('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->nav();
		$this->control->set_nav(array('mode' => $this->mode, 'manage' => $this->manage), 'CONTROL_ADD');
		$this->control->set_nav(array('mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub), (isset($user->lang[$menu[$sub]])) ? $user->lang[$menu[$sub]] : $menu[$sub]);
		
		$template_vars = array(
			'SUB' => $sub,
			'S_HIDDEN' => s_hidden(array('module' => $this->control->module, 'mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub))
		);
		
		switch ($sub)
		{
			case 'cat':
				$sql = 'SELECT *
					FROM _help_modules
					ORDER BY module_id';
				$result = $db->sql_query($sql);
				
				$select_mod = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['module_id'] == $module_id);
					$select_mod .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['module_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['module_name'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$template_vars += array(
					'MODULE' => $select_mod,
					'HELP_ES' => $help_es,
					'HELP_EN' => $help_en
				);
				break;
			case 'faq':
				$sql = 'SELECT *
					FROM _help_cat
					ORDER BY help_id';
				$result = $db->sql_query($sql);
				
				$select_cat = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['help_id'] == $help_id);
					$select_cat .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['help_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['help_es'] . ' | ' . $row['help_en'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$template_vars += array(
					'CATEGORY' => $select_cat,
					'QUESTION_ES' => $question_es,
					'QUESTION_EN' => $question_en,
					'ANSWER_ES' => $answer_es,
					'ANSWER_EN' => $answer_en
				);
				break;
			case 'module':
				$template_vars += array(
					'MODULE_NAME' => $module_name
				);
				break;
		}
		
		$template->assign_vars($template_vars);
	}
	
	function _help_edit_move()
	{
		global $db;
		
		$sql = 'SELECT *
			FROM _help_cat
			ORDER BY help_order';
		$result = $db->sql_query($sql);
		
		$i = 10;
		while ($row = $db->sql_fetchrow($result))
		{
			$sql = 'UPDATE _help_cat
				SET help_order = ' . (int) $i . '
				WHERE help_id = ' . (int) $row['help_id'];
			$db->sql_query($sql);
			
			$i += 10;
		}
		$db->sql_freeresult($result);
	}
	
	function _help_edit()
	{
		global $db, $user, $cache, $template;
		
		$error = array();
		$sub = $this->control->get_var('sub', '');
		$id = $this->control->get_var('id', 0);
		$submit = (isset($_POST['submit'])) ? TRUE : FALSE;
		
		switch ($sub)
		{
			case 'cat':
				$sql = 'SELECT c.*, m.*
					FROM _help_cat c, _help_modules m
					WHERE c.help_id = ' . (int) $id . '
						AND c.help_module = m.module_id';
				$result = $db->sql_query($sql);
				
				if (!$cat_data = $db->sql_fetchrow($result))
				{
					fatal_error();
				}
				$db->sql_freeresult($result);
				
				$order = $this->control->get_var('order', '');
				if (!empty($order))
				{
					if (preg_match('/_([0-9]+)/', $order))
					{
						$sig = '-';
						$order = str_replace('_', '', $order);
					}
					else
					{
						$sig = '+';
					}
					
					$sql = 'UPDATE _help_cat
						SET help_order = help_order ' . $sig . ' ' . (int) $order . '
						WHERE help_id = ' . (int) $id;
					$db->sql_query($sql);
					
					$this->_help_edit_move();
					
					$cache->delete('help_cat');
					
					redirect(s_link_control('comments', array('mode' => $this->mode)));
				} // IF order
				
				$module_id = $cat_data['help_module'];
				$help_es = $cat_data['help_es'];
				$help_en = $cat_data['help_en'];
				break;
			case 'faq':
				$sql = 'SELECT *
					FROM _help_faq
					WHERE faq_id = ' . (int) $id;
				$result = $db->sql_query($sql);
				
				if (!$faq_data = $db->sql_fetchrow($result))
				{
					fatal_error();
				}
				$db->sql_freeresult($result);
				
				$question_es = $faq_data['faq_question_es'];
				$question_en = $faq_data['faq_question_en'];
				$answer_es = $faq_data['faq_answer_es'];
				$answer_en = $faq_data['faq_answer_en'];
				$help_id = $faq_data['help_id'];
				break;
			default:
				redirect(s_link_control('comments', array('mode' => $this->mode)));
				break;
		}
		
		// IF submit
		if ($submit)
		{
			switch ($sub)
			{
				case 'cat':
					$module_id = $this->control->get_var('module_id', 0);
					$help_es = $this->control->get_var('help_es', '');
					$help_en = $this->control->get_var('help_en', '');
					
					if (empty($help_es) || empty($help_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					// Update
					if (!sizeof($error))
					{
						$sql_update = array(
							'help_es' => $help_es,
							'help_en' => $help_en,
							'help_module' => (int) $module_id
						);
						
						$sql = 'UPDATE _help_cat
							SET ' . $db->sql_build_array('UPDATE', $sql_update) . '
							WHERE help_id = ' . (int) $id;
						$db->sql_query($sql);
						
						$cache->delete('help_cat');
						
						redirect(s_link_control('comments', array('mode' => $this->mode)));
					}
					break;
				case 'faq':
					$question_es = $this->control->get_var('question_es', '');
					$question_en = $this->control->get_var('question_en', '');
					$answer_es = $this->control->get_var('answer_es', '');
					$answer_en = $this->control->get_var('answer_en', '');
					$help_id = $this->control->get_var('help_id', 0);
					
					if (empty($question_es) || empty($question_en) || empty($answer_es) || empty($answer_en))
					{
						$error[] = 'CONTROL_COMMENTS_HELP_EMPTY';
					}
					
					if (!sizeof($error))
					{
						$sql = 'SELECT *
							FROM _help_cat
							WHERE help_id = ' . (int) $help_id;
						$result = $db->sql_query($sql);
						
						if (!$cat_data = $db->sql_fetchrow($result))
						{
							$error[] = 'CONTROL_COMMENTS_HELP_NOCAT';
						}
					}
					
					// Update
					if (!sizeof($error))
					{
						$sql_update = array(
							'help_id' => (int) $help_id,
							'faq_question_es' => $question_es,
							'faq_question_en' => $question_en,
							'faq_answer_es' => $answer_es,
							'faq_answer_en' => $answer_en
						);
						
						$sql = 'UPDATE _help_faq
							SET ' . $db->sql_build_array('UPDATE', $sql_update) . '
							WHERE faq_id = ' . (int) $id;
						$db->sql_query($sql);
						
						$cache->delete('help_faq');
						
						redirect(s_link_control('comments', array('mode' => $this->mode)));
					}
					break;
			} // switch
			
			if (sizeof($error))
			{
				$template->assign_block_vars('error', array(
					'MESSAGE' => parse_error($error))
				);
			}
		}
		
		$this->nav();
		$this->control->set_nav(array('mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub, 'id' => $id), 'CONTROL_EDIT');
		
		$template_vars = array(
			'SUB' => $sub,
			'S_HIDDEN' => s_hidden(array('module' => $this->control->module, 'mode' => $this->mode, 'manage' => $this->manage, 'sub' => $sub, 'id' => $id))
		);
		
		switch ($sub)
		{
			case 'cat':
				$sql = 'SELECT *
					FROM _help_modules
					ORDER BY module_id';
				$result = $db->sql_query($sql);
				
				$select_mod = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['module_id'] == $module_id);
					$select_mod .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['module_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['module_name'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$template_vars += array(
					'MODULE' => $select_mod,
					'HELP_ES' => $help_es,
					'HELP_EN' => $help_en
				);
				break;
			case 'faq':
				$sql = 'SELECT *
					FROM _help_cat
					ORDER BY help_id';
				$result = $db->sql_query($sql);
				
				$select_cat = '';
				while ($row = $db->sql_fetchrow($result))
				{
					$selected = ($row['help_id'] == $help_id);
					$select_cat .= '<option' . (($selected) ? ' class="bold"' : '') . ' value="' . $row['help_id'] . '"' . (($selected) ? ' selected' : '') . '>' . $row['help_es'] . ' | ' . $row['help_en'] . '</option>';
				}
				$db->sql_freeresult($result);
				
				$template_vars += array(
					'CATEGORY' => $select_cat,
					'QUESTION_ES' => $question_es,
					'QUESTION_EN' => $question_en,
					'ANSWER_ES' => $answer_es,
					'ANSWER_EN' => $answer_en
				);
				break;
		}
		
		$template->assign_vars($template_vars);
		
		return;
	}
	
	function _help_delete()
	{
		
	}
}

?>