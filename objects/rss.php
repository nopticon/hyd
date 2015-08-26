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
if (!defined('IN_APP')) exit;

if (class_exists('_rss')) {
	return;
}

class _rss {
	public $mode;
	public $xml = array();
	
	function __construct() {
		return;
	}
	
	function smode($mode) {
		$this->mode = $mode;
	}
	
	function _news() {
		$sql = 'SELECT n.*, m.username
			FROM _news n, _members m
			WHERE n.poster_id = m.user_id
			ORDER BY post_time DESC
			LIMIT 15';
		$result = sql_rowset($sql);
		
		foreach ($result as $row) {
			$this->xml[] = array(
				'title' => $row['post_subject'],
				'link' => s_link('news', $row['news_id']),
				'description' => $row['post_desc'],
				'pubdate' => $row['post_time'],
				'author' => $row['username']
			);
		}
		
		return;
	}
	
	function _events() {
		return;
	}
	
	function _artists() {
		$sql = 'SELECT name, subdomain, genre, datetime, local, location
			FROM _artists
			ORDER BY datetime DESC
			LIMIT 15';
		$result = sql_rowset($sql);
		
		foreach ($result as $row) {
			$this->xml[] = array(
				'title' => $row['name'],
				'link' => s_link('a', $row['subdomain']),
				'description' => ($row['genre'] . "<br />" . (($row['local']) ? 'Guatemala' : $row['location'])),
				'pubdate' => $row['datetime']
			);
		}
		
		return;
	}
	
	function output() {
		global $user;
		
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
	</item>' . nr();
		}
		
		header('Content-type: text/xml');
		echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0">
<channel>
	<title>' . html_entity_decode_utf8(lang('rss_' . $umode)) . '</title>
	<link>http://www.rockrepublik.net/</link>
	<description><![CDATA[' . html_entity_decode_utf8(lang('rss_desc_' . $umode)) . ']]></description>
	<lastBuildDate>' . date('D, d M Y H:i:s \G\M\T', $this->xml[0]['pubdate']) . '</lastBuildDate>
	<webMaster>info@rockrepublik.net</webMaster>
' . $items . '</channel>
</rss>';
		
		sql_close();
		exit;
	}
}

?>