<?php
// -------------------------------------------------------------
// $Id: rss.php,v 1.2 2007/07/07 19:54:00 Psychopsia Exp $
//
// STARTED   : Sun Jul 01, 2007
// COPYRIGHT : © 2007 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	die('Rock Republik &copy;');
}

if (class_exists('_rss'))
{
	return;
}

class _rss
{
	var $mode;
	var $xml = array();
	
	function _rss()
	{
		return;
	}
	
	function smode($mode)
	{
		$this->mode = $mode;
	}
	
	function _news()
	{
		global $db;
		
		$sql = 'SELECT n.*, m.username
			FROM _news n, _members m
			WHERE n.poster_id = m.user_id
			ORDER BY post_time DESC
			LIMIT 15';
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$this->xml[] = array(
				'title' => $row['post_subject'],
				'link' => s_link('news', $row['news_id']),
				'description' => $row['post_desc'],
				'pubdate' => $row['post_time'],
				'author' => $row['username']
			);
		}
		$db->sql_freeresult($result);
		
		return;
	}
	
	function _events()
	{
		global $db;
	}
	
	function _artists()
	{
		global $db;
		
		$sql = 'SELECT name, subdomain, genre, datetime, local, location
			FROM _artists
			ORDER BY datetime DESC
			LIMIT 15';
		$result = $db->sql_query($sql);
		
		while ($row = $db->sql_fetchrow($result))
		{
			$this->xml[] = array(
				'title' => $row['name'],
				'link' => s_link('a', $row['subdomain']),
				'description' => ($row['genre'] . "<br />" . (($row['local']) ? 'Guatemala' : $row['location'])),
				'pubdate' => $row['datetime']
			);
		}
		$db->sql_freeresult($result);
		
		return;
	}
	
	function output()
	{
		global $db, $user;
		
		$umode = strtoupper($this->mode);
		
		$items = '';
		foreach ($this->xml as $item)
		{
			$items .= "\t" . '<item>
		' . (isset($item['author']) ? '<author>' . $item['author'] . '</author>' : '') . '
		<title><![CDATA[' . html_entity_decode_utf8($item['title']) . ']]></title>
		<link>' . $item['link'] . '</link>
		<guid>' . $item['link'] . '</guid>
		<description><![CDATA[' . html_entity_decode_utf8($item['description']) . ']]></description>
		<pubDate>' . date('D, d M Y H:i:s \G\M\T', $item['pubdate']) . '</pubDate>
	</item>' . "\n";
		}
		
		header('Content-type: text/xml');
		echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
<channel>
	<title>' . html_entity_decode_utf8($user->lang['RSS_' . $umode]) . '</title>
	<link>http://www.rockrepublik.net/</link>
	<description><![CDATA[' . html_entity_decode_utf8($user->lang['RSS_DESC_' . $umode]) . ']]></description>
	<lastBuildDate>' . date('D, d M Y H:i:s \G\M\T', $this->xml[0]['pubdate']) . '</lastBuildDate>
	<webMaster>info@rockrepublik.net</webMaster>
' . $items . '</channel>
</rss>';
		
		if (isset($db))
		{
			$db->sql_close();
		}
		
		exit();
	}
}

?>