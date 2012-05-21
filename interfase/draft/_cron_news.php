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
define('IN_APP', true);
require_once('./interfase/common.php');
require_once(ROOT . 'interfase/mail.php');
require_once(ROOT . 'interfase/pop3.php');

$user->init(true, true);
$user->setup();

$mail = new _mail();
$pop3 = new POP3();
$emailer = new emailer();

if (!$pop3->connect($config['mailserver_url'], $config['mailserver_port'])) {
	_pre($pop3->ERROR, true);
}

$count = $pop3->login($config['mailserver_news_login'], $config['mailserver_news_pass']);
if (!$count) {
	_pre('There does not seem to be any new mail.', true);
}

if (!$emails = $cache->get('team_email'))
{
	$sql = 'SELECT DISTINCT member_id
		FROM _team_members
		ORDER BY member_id';
	$mods = sql_rowset($sql, false, 'member_id');
	
	$sql = 'SELECT DISTINCT user_id, user_email
		FROM _members
		WHERE user_id IN (??)
			OR user_type = ?';
	if ($emails = sql_rowset(sql_filter($sql, implode(',', $mods), USER_FOUNDER), 'user_id', 'user_email')) {
		$cache->save('team_email', $emails);
	}
}

if (!$news_cat = $cache->get('news_cat_mail'))
{
	$sql = 'SELECT cat_id, cat_name
		FROM _news_cat
		ORDER BY cat_id';
	if ($news_cat = sql_rowset($sql, 'cat_id', 'cat_name')) {
		$cache->save('news_cat_mail', $news_cat);
	}
}

$team = array_flip($emails);
$news_cat = array_flip($news_cat);
$spam = array();

for ($i = 1; $i <= $count; $i++)
{
	$header = implode('', $pop3->top($i));
	$s_header = $mail->parse_header(split(nr(1), $header));
	
	$from = $mail->parse_address($s_header['from']);
	if (!in_array($from, $emails))
	{
		@error_log('[bot: news] ' . $from . ' - Not allowed', 0);
		$spam[] = $i;
		continue;
	}
	
	$sql = 'SELECT user_id, username, user_password
		FROM _members
		WHERE user_email = ?';
	if (!$userdata = sql_fieldrow(sql_filter($sql, $from))) {
		@error_log('[bot: news] ' . $from . ' - Userdata not found', 0);
		$spam[] = $i;
		continue;
	}
	
	//
	$s_header['user_id'] = $team[$author];
	
	$message = $pop3->fbody($i);
	$body = $mail->body($s_header, $message);
	
	$post_text = '';
	if (isset($body['text-plain']) && isset($body['text-html']))
	{
		$post_text = $body['text-plain'];
	}
	elseif (isset($body['text-plain']) && !isset($body['text-html']))
	{
		$post_text = $body['text-plain'];
		
		if (preg_match('/&lt;html&gt;/', $post_text))
		{
			$post_text = preg_replace('#&lt;html(.*)html&gt;#is', '', $post_text);
		}
	}
	elseif (!isset($body['text-plain']) && isset($body['text-html']))
	{
		$post_text = $body['text-html'];
	}
	
	$post_text = trim($post_text);
	
	$post_parts = explode("\n", $post_text);
	$user_password = $post_parts[0];
	$post_subject = $s_header['subject'];
	
	$post_aproved = false;
	if (!empty($user_password)) {
		if (ValidatePassword($user_password, $userdata['user_password'])) {
			$post_aproved = true;
		}
	}
	
	if ($post_aproved) {
		$post_category = $post_parts[1];
		
		if (isset($body['text-html'])) {
			$post_category = htmlentities($post_category);
		}
		
		$post_category_id = 0;
		if (!empty($post_category)) {
			$post_category_id = (isset($news_cat[$post_category])) ? $news_cat[$post_category] : 0;
		}
		
		if (!$post_category_id) {
			$post_category_id = 5;
			$post_category = 'Otras noticias';
		}
		
		$post_text = implode(nr(), array_splice($post_parts, 3));
		$post_desc = explode('.', $post_text);
		
		$post_date = $mail->parse_date($s_header['date']);
		$post_ip = $mail->parse_ip($s_header['received']);
		
		$insert = array(
			'cat_id' => $post_category_id,
			'poster_id' => $team[$from],
			'post_subject' => htmlencode($post_subject),
			'post_text' => $post_text,
			'post_desc' => $post_desc[0] . '.',
			'post_time' => $post_date,
			'post_ip' => $post_ip
		);
		$sql = 'INSERT INTO _news' . sql_build('INSERT', $insert);
		$post_id = sql_query_nextid($sql);
		
		$cache->delete('news', 'news_cat');
		$user->save_unread(UH_GN, $post_id);
		
		$email_subject = 'Noticia publicada';
		$email_message = 'Gracias por enviarnos la noticia "' . $post_subject . '".
Puedes verla en la secci&oacute;n de Noticias, categor&iacute;a: ' . $post_category . '

Puedes revisar la noticia en esta direcci&ooacute;n: ' . s_link('news', $post_id);
	}
	else
	{
		$email_subject = 'Error en noticia';
		$email_message = "La noticia enviada hace unos minutos \"" . $post_subject . "\" tiene un error y no se pudo publicar.
La contrase&ntilde;a no coincide. Debes escribir la contrase&ntilde;a de tu usuario en Rock Republik en la primera l&iacute;nea del mensaje.

Intenta enviar la noticia nuevamente.";
		
		@error_log('[bot: news] ' . $from . ' - Password empty or not match', 0);
	}
	
	//
	// Send email
	//
	$emailer->from('info');
	$emailer->set_subject('Rock Republik: ' . $email_subject);
	$emailer->use_template('mcp_news', $config['default_lang']);
	$emailer->email_address($from);
	
	$emailer->assign_vars(array(
		'USERNAME' => $userdata['username'],
		'MESSAGE' => $email_message)
	);
	$emailer->send();
	$emailer->reset();
	
	if ($pop3->delete($i))
	{
		if ($post_aproved)
		{
			echo '<ul><li>' . $post_subject . '</li><li>' . $user->format_date($post_date) . '</li></ul>';
		}
	}
	else
	{
		@error_log('[bot: news] ' . $from . ' - Can not delete email message', 0);
		echo '<p>Oops ' . $pop3->ERROR . '</p></div>';
		$pop3->reset();
		exit;
	}
}

if (sizeof($spam))
{
	foreach ($spam as $i)
	{
		$pop3->delete($i);
	}
}

$pop3->quit();

?>
