<?php
// -------------------------------------------------------------
//
// $Id: functions_selects.php,v 1.1.1.1 2006/01/06 03:36:48 Psychopsia Exp $
//
// FILENAME  : functions_selects.php
// STARTED   : Sat Feb 13, 2001
// COPYRIGHT : © 2001 Rock Republik NET
// WWW       : http://www.rockrepublik.net/
// LICENCE   : GPL vs2.0 [ see /docs/COPYING ] 
// 
// -------------------------------------------------------------

//
// Pick a language, any language ...
//
function language_select($default, $select_name = "language", $dirname="language")
{
	$lang = array();
	
	$dir = opendir(ROOT.$dirname);
	while ( $file = readdir($dir) )
	{
		if (preg_match('#^lang_#i', $file) && !is_file(@realpath(ROOT.$dirname . '/' . $file)) && !is_link(@realpath(ROOT.$dirname . '/' . $file)))
		{
			$filename = trim(str_replace('lang_', '', $file));
			$displayname = preg_replace("/^(.*?)_(.*)$/", "\\1 [ \\2 ]", $filename);
			$displayname = preg_replace("/\[(.*?)_(.*)\]/", "[ \\1 - \\2 ]", $displayname);
			$lang[$displayname] = $filename;
		}
	}
	closedir($dir);

	@asort($lang);
	@reset($lang);

	$lang_select = '<select name="' . $select_name . '">';
	while ( list($displayname, $filename) = @each($lang) )
	{
		$selected = ( strtolower($default) == strtolower($filename) ) ? ' selected="selected"' : '';
		$lang_select .= '<option value="' . $filename . '"' . $selected . '>' . ucwords($displayname) . '</option>';
	}
	$lang_select .= '</select>';

	return $lang_select;
}

//
// Pick a timezone
//
function tz_select($default, $select_name = 'timezone')
{
	global $sys_timezone, $lang;

	if ( !isset($default) )
	{
		$default == $sys_timezone;
	}
	$tz_select = '<select name="' . $select_name . '">';
	
	foreach ($lang['tz'] as $offset => $zone)
	{
		$selected = ( $offset == $default ) ? ' selected="selected"' : '';
		$tz_select .= '<option value="' . $offset . '"' . $selected . '>' . $zone . '</option>';
	}
	$tz_select .= '</select>';

	return $tz_select;
}

?>
