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
define('IN_NUCLEO', true);
require_once('./interfase/common.php');
require_once(ROOT . 'interfase/comments.php');

$user->init();
$user->setup();

$help_modules = array();
$help_cat = array();
$help_faq = array();

if (!$help_modules = $cache->get('help_modules')) {
	$sql = 'SELECT module_id, module_name
		FROM _help_modules
		ORDER BY module_name';
	if ($help_modules = sql_rowset($sql, 'module_name', 'module_id')) {
		$cache->save('help_modules', $help_modules);
	}
}

if (!$help_cat = $cache->get('help_cat')) {
	$sql = 'SELECT *
		FROM _help_cat
		ORDER BY help_order';
	if ($help_cat = sql_rowset($sql, 'help_id')) {
		$cache->save('help_cat', $help_cat);
	}
}

if (!$help_faq = $cache->get('help_faq')) {
	$sql = 'SELECT *
		FROM _help_faq
		ORDER BY faq_question_es';
	if ($help_faq = sql_rowset($sql, 'faq_id')) {
		$cache->save('help_faq', $help_faq);
	} 
}

if (!sizeof($help_modules) || !sizeof($help_cat) || !sizeof($help_faq)) {
	fatal_error();
}

$module = request_var('module', '');
$help = request_var('help', 0);

if ($module != '') {
	$module_id = (int) $help_modules[$module];
	
	if (!$module_id) {
		fatal_error();
	}
}

if ($help) {
	if (!isset($help_faq[$help])) {
		fatal_error();
	}
	
	$module_id = $help_faq[$help]['help_id'];
}

//
// Categories
//
$hm_flip = array_flip($help_modules);
$template->assign_block_vars('cat', array());

foreach ($help_cat as $cat_id => $data) {
	$template->assign_block_vars('cat.item', array(
		'URL' => s_link('help', array($hm_flip[$data['help_module']])),
		'TITLE' => $data['help_es'])
	);
}

//
// Selected category
//
if ($module_id || $help) {
	if (!$help) {
		$this_cat = array();
		foreach ($help_faq as $data) {
			if ($data['help_id'] == $module_id) {
				$this_cat[] = $data;
			}
		}
	}
	
	$help_name = '';
	foreach ($help_cat as $data) {
		if ($data['help_module'] == $module_id) {
			$help_name = $data['help_es'];
			break;
		}
	}
	
	$template->assign_block_vars('module', array(
		'HELP' => $help_name)
	);
	
	if (!$help) {
		if (sizeof($this_cat)) {
			$template->assign_block_vars('module.main', array());
			
			foreach ($this_cat as $data) {
				$template->assign_block_vars('module.main.item', array(
					'URL' => s_link('help', $data['faq_id']),
					'FAQ' => $data['faq_question_es'])
				);
			}
		} else {
			$template->assign_block_vars('module.empty', array());
		}
	} else {
		$dhelp = $help_faq[$help];
		
		$comments = new _comments();
		
		$template->assign_block_vars('module.faq', array(
			'CAT' => s_link('help', $hm_flip[$dhelp['help_id']]),
			'QUESTION_ES' => $dhelp['faq_question_es'],
			'QUESTION_EN' => $dhelp['faq_question_e'],
			'ANSWER_ES' => $comments->parse_message($dhelp['faq_answer_es']),
			'ANSWER_EN' => $comments->parse_message($dhelp['faq_answer_en']))
		);
	}
}

page_layout('HELP', 'help');

?>