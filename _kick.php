<?php
// -------------------------------------------------------------
// $Id: _kick.php,v 1.1 2008/10/28 11:59:00 Psychopsia Exp $
//
// COPYRIGHT : 2008 Nopticon
// WWW       : http://www.nopticon.com/
// -------------------------------------------------------------

class __kick extends common
{
	var $_no = true;
	var $methods = array();
	
	function home()
	{
		global $db, $nucleo, $user;
		
		$v = $this->control->__(array('a' => array('default' => 0)));
		if (!$v['a'])
		{
			$nucleo->redirect($nucleo->link('radio'));
		}
		
		if (!$user->data['is_member'] || $user->data['is_bot'] || $nucleo->config['request_method'] != 'post')
		{
			if ($v['a'])
			{
				$this->e('403 Forbidden.');
			}
			$nucleo->redirect($nucleo->link('radio'));
		}
		
		if (!$user->data['is_founder'])
		{
			$sql = 'SELECT *
				FROM _team_members
				WHERE team_id = 4
					AND member_id = ' . (int) $user->data['user_id'];
			if (!$this->_fieldrow($sql))
			{
				if ($v['a'])
				{
					$this->e('403 Forbidden.');
				}
				$nucleo->redirect($nucleo->link('radio'));
			}
		}
		
		//
		$connect_param = 'GET http://' . $nucleo->config['sc_stats_host'] . '/shoutcast/text/index.php?';
		$connect_param.= 'server=' . $nucleo->config['sc_stats_ip'] . '&port=' . $nucleo->config['sc_stats_ipport'] . " HTTP/1.0\r\n";
		$connect_param.= "User-Agent: StreamSolutions  (Mozilla Compatible)\r\n\r\n";
		
		$connect_recv = $this->sock($nucleo->config['sc_stats_host'], $connect_param, $nucleo->config['sc_stats_port']);
		if (!$connect_recv)
		{
			$this->e('RADIO_UNAVAILABLE');
		}
		
		$response = array();
		$lines = array_slice(split("\n", trim($connect_recv)), 8);
		foreach ($lines as $line)
		{
			$e = explode('<SSTAG>', $line);
			$response[$e[0]] = $e[1];
		}
		
		if ($response['server_status'] == $nucleo->config['sc_stats_down'])
		{
			$this->e('RADIO_UNAVAILABLE');
		}
		
		//
		$kick_request = 'GET /admin.cgi?pass=' . $nucleo->config['sc_stats_key'] . '&mode=kicksrc' . " HTTP/1.0\r\n";
		$kick_request.= "User-Agent: StreamSolutions  (Mozilla Compatible)\r\n\r\n";
		$this->e($kick_request . '/' . $nucleo->config['sc_stats_ip'] . '/' . $nucleo->config['sc_stats_ipport'] . '/');
		
		$kick_recv = $this->sock($nucleo->config['sc_stats_ip'], $connect_param, $nucleo->config['sc_stats_ipport']);
		if (!$kick_recv)
		{
			$this->e('RADIO_UNAVAILABLE');
		}
		
		$lines2 = split("\n", trim($kick_recv));
		if (!isset($lines2[4]))
		{
			$lines2[4] = '';
		}
		
		// If successful
		if (strstr($lines2[4], 'redirect'))
		{
			$insert = array(
				'log_uid' => $user->data['user_id'],
				'log_time' => time()
			);
			$sql = 'INSERT INTO _radio_dj_log' . $db->sql_build_array('INSERT', $insert);
			$db->sql_query($sql);
		}
		
		$this->e('Disconnected.');
		return;
	}
}

?>