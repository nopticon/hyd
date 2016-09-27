<?php

// Include the NuSOAP class file:
require_once('class.nusoap.php');
require_once('class.xml.php');
require_once('class.blowfish.php');

class libws extends blowfish {
	private $ws;
	private $url;
	private $wsdl;
	private $origin;
	private $client;
	private $destiny;
	private $server;
	private $bridge;
	private $params;
	private $unique;
	private $namespace;
	private $object;
	private $type;
	
	public function __construct($url = '', $wsdl = 0) {
		if (is_array($url)) {
			$this->params = $this->bridge = $url;
			$url = $url[0];
		}
		
		$ini_bridge = '';
		if (strpos($url, '://') === false) {
			if (empty($url)) {
				$url = 'libws';
			}
			
			if (strpos($url, ':') !== false) {
				$ini_bridge_part = explode(':', $url);
				
				$url = $ini_bridge_part[0];
				$ini_bridge = $ini_bridge_part[1];
			} else {
				$ini_bridge = $url;
			}

			$ini_bridge = strtoupper($ini_bridge);
			$ini_file_path = dirname(__FILE__) . '/';

			$level = array_merge(array(''), w('./ ../ ../../ ../../../'));
			foreach ($level as $path) {
				$ini_file = $path . 'ini.' . $url . '.php';

				if (!empty($path)) {
					$ini_file = $ini_file_path . $ini_file;
				}

				if (@file_exists($ini_file)) {
					$this->params = parse_ini_file($ini_file);
					break;
				}
			}

			if (!is_array($this->params) || !isset($this->params[$ini_bridge])) {
				return false;
			}

			$this->bridge = $this->params[$ini_bridge];
			unset($this->params[$ini_bridge]);
			
			$url = $this->bridge[0];
			$this->destiny = end($this->bridge);
			reset($this->bridge);
		}

		foreach (w('wsdl mysql php facebook') as $row) {
			if (!is_array($url) && strpos($url, $row) !== false) {
				$this->type = $row;

				if ($row == 'wsdl') $wsdl = true;
				break;
			}
		}

		$this->url = $url;
		$this->wsdl = $wsdl;
		$this->origin = true;
		$this->unique = true;

		return true;
	}

	public function __ws_construct($app, $object, $namespace = '') {
		$this->server = new nusoap_server();
		$this->namespace = (!empty($namespace)) ? $namespace : $this->url;
		$this->object = $object;

		$this->server->configureWSDL($app, $namespace);
		$this->server->wsdl->schemaTargetNamespace = $namespace;

		return;
	}

	public function __ws_method($method, $input, $output) {
		if (!function_exists($method)) {
			$format = 'function %s(%s) { return %s::%s(%s); }';
			$assign = "%s::__combine('%s', '%s', true);";

			$arg = '';
			if (count($input)) {
				$arg = array_keys($input);

				eval(sprintf($assign, $this->object, $method, implode(' ', $arg)));

				$arg = '$' . implode(', $', $arg);
			}

			eval(sprintf($format, $method, $arg, $this->object, $method, $arg));
		}

		$this->server->register($method, $input, $output, $this->namespace . $this->namespace . '/' . $method);
	}

	public function __ws_service() {
		$HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : implode("\r\n", file('php://input'));

        $this->server->service($HTTP_RAW_POST_DATA);
	}

	public function __ws_object() {
		return $this->server;
	}

	private function _filter($response) {
		$a = array();

		if (!is_array($response)) {
			$response = array($response);
		}

		foreach ($response as $i => $row) {
			$a[$i] = is_array($row) ? $this->_filter($row) : str_replace(array('&lt;', '&gt;'), array('<', '>'), /*htmlentities(*/utf8_encode($row)/*, ENT_COMPAT, 'utf-8')*/);
		}
		
		return $a;
	}
	
	public function _param_replace($arg) {
		$arg = (is_object($arg)) ? (array) $arg : $arg;

		if (is_array($arg)) {
			foreach ($arg as $i => $row) {
				$arg[$i] = $this->_param_replace($row);
			}

			return $arg;
		}

		return (strpos($arg, '#') !== false) ? preg_replace('/\#([A-Z\_]+)/e', '(isset($this->params["$1"])) ? @$this->params["$1"] : "$1"', $arg) : $arg;
	}
	
	private function _build($ary, $s = true) {
		$query = '';
		foreach ($ary as $i => $row) {
			if (is_array($row)) {
				$i = $row[0];
				$row = $row[1];
			}

			$query .= ((!empty($query)) ? '&' : '') . $i . '=' . urlencode($row);
		}

		return ($s ? '?' : '') . $query;
	}
	
	private function _format($data) {
		if (is_array($data) && isset($data['response'])) {
			$data = $data['response'];
		}
		
		preg_match_all('#([a-z0-9\.]+)\=(.*?)\n#i', $data, $parts);
		
		$details = 'identitydetails.attribute.name';
		$details2 = 'userdetails.attribute.name';
		$values = 'identitydetails.attribute.value';
		$values2 = 'userdetails.attribute.value';
		$attr = 'identitydetails.attribute';

		$open = false;
		$response = array();
		foreach ($parts[1] as $i => $name) {
			$value = $parts[2][$i];

			switch ($name) {
				case $attr:
					break;
				case $details:
				case $details2:
					if ($open) {
						$response[$open] = '';
						$open = false;
					}

					if (!$open) {
						$open = str_replace(w('. -'), '_', strtolower($value));
						continue;
					}
					break;
				case $values:
				case $values2:
					if ($open) {
						$response[$open] = $value;
						$open = false;
					}
					break;
				default:
					$name = str_replace(w('. -'), '_', strtolower($name));
					$response[$name] = $value;
					break;
			}
		}

		return $response;
	}

	private function _format_users($data) {
		if (isset($data['response'])) {
			$data = $data['response'];
		}

		preg_match_all('#([a-z0-9\.]+)\=(.*?)\n#i', $data, $parts);

		return $parts[2];
	}

	public function __enrichment($override = false) {
		static $number;

		if ($override !== false) {
			$_SERVER['HTTP_X_NOKIA_MSISDN'] = $override;
		}

		if (!isset($number)) {
			$number = (isset($_SERVER['HTTP_X_NOKIA_MSISDN']) && !empty($_SERVER['HTTP_X_NOKIA_MSISDN'])) ? $_SERVER['HTTP_X_NOKIA_MSISDN'] : '';
		}

		preg_match('/(\d{3})(\d+)/i', $number, $part);
		unset($part[0]);

		foreach (w('1 2') as $i) {
			if (!isset($part[$i])) $part[$i] = '';
		}

		return (object) array_combine(w('area number'), $part);
	}
	
	public function __claro_is($country, $phone, $by_name = false) {
		$is = $this->IsClaro_Phone(array(
			'user' => '#SMS_USER',
			'pass' => '#SMS_PASS',
			'area' => $country,
			'phone' => $phone
		));
		
		if (isset($is->IsClaro_PhoneResult)) {
			$response = (int) $is->IsClaro_PhoneResult;
			
			if ($by_name !== false) {
				switch ($response) {
					case -1: $response = 'ESPE'; break;
					case 1: $response = 'PREP'; break;
					case 2: $response = 'HIBR'; break;
					case 3: $response = 'POST'; break;
				}
			}
			return $response;
		}
		
		return false;
	}
	
	public function __claro_sms($country, $phone, $message) {
		if ($this->__claro_is($country, $phone)) {
			$sms = $this->Send_SMS(array(
				'user' => '#SMS_USER',
				'pass' => '#SMS_PASS',
				'to_phone' => $country.$phone,
				'text' => $message
			));
			
			return true;
		}
		
		return false;
	}

	public function __sso_create($email, $password, $fn, $sn) {
		$token = $this->authenticate(array(
			'username' => '#SSO_USER',
			'password' => '#SSO_PASS')
		);

		if (!count($token)) {
			return array('timeout' => true);
		}

		$is_created = false;

		if (isset($token->token_id)) {
			$user = $this->read(array(
				'name' => $email,
				'admin' => $token->token_id)
			);

			if (!isset($user->user_id)) {
				$cn = $fn . ((!empty($fn) && !empty($sn)) ? ' ' : '') . $sn;
		
				$query = array(
					array('identity_name', $email),
					array('identity_attribute_names', 'userpassword'),
					array('identity_attribute_values_userpassword', $password),
					array('identity_attribute_names', 'givenname'),
					array('identity_attribute_values_givenname', $fn),
					array('identity_attribute_names', 'sn'),
					array('identity_attribute_values_sn', $sn),
					array('identity_attribute_names', 'cn'),
					array('identity_attribute_values_cn', $cn),
					array('identity_attribute_names', 'mail'),
					array('identity_attributes_values_mail', $email),
					array('identity_attribute_names', 'inetuserstatus'),
					array('identity_attributes_values_inetuserstatus', 'Active'),
					
					array('identity_realm', '/'),
					array('identity_type', 'user'),
					array('inetuserstatus', 'Active'),
					array('admin', $token->token_id)
				);
				$create = $this->create($query);
				
				$user = $this->read(array(
					'name' => $email,
					'admin' => $token->token_id)
				);

				if (isset($user->user_id)) {
					$this->update(array(
						'identity_name' => $email,
						'identity_attribute_names' => 'mail',
						'identity_attribute_values_mail' => $email,
						'admin' => $token->token_id)
					);
					$user->mail = $email;
					
					$is_created = $user;
				}
			}
			
			$out = $this->logout(array(
				'subjectid' => $token->token_id)
			);
		}
		
		return $is_created;
	}
	
	public function __sso_read($username) {
		$token = $this->authenticate(array(
			'username' => '#SSO_USER',
			'password' => '#SSO_PASS')
		);

		if (!count($token)) {
			return array('timeout' => true);
		}
		
		if (isset($token->token_id)) {
			$user = $this->read(array(
				'name' => $username,
				'admin' => $token->token_id)
			);
			
			$out = $this->logout(array(
				'subjectid' => $token->token_id)
			);
			
			if (isset($user->user_id)) {
				return $user;
			}
		}
		
		return false;
	}
	
	public function __sso_update($username, $name, $value) {
		$token = $this->authenticate(array(
			'username' => '#SSO_USER',
			'password' => '#SSO_PASS')
		);

		if (!count($token)) {
			return array('timeout' => true);
		}
		
		if (isset($token->token_id)) {
			$user = $this->update(array(
				'identity_name' => $username,
				'identity_attribute_names' => $name,
				'identity_attribute_values_' . $name => $value,
				'admin' => $token->token_id)
			);
			
			$out = $this->logout(array(
				'subjectid' => $token->token_id)
			);
			
			return true;
		}
		
		return false;
	}
	
	public function __sso_delete($username) {
		$token = $this->authenticate(array(
			'username' => '#SSO_USER',
			'password' => '#SSO_PASS')
		);

		if (!count($token)) {
			return array('timeout' => true);
		}
		
		if (isset($token->token_id)) {
			$user = $this->delete(array(
				'identity_name' => $username,
				'admin' => $token->token_id)
			);
			
			$out = $this->logout(array(
				'subjectid' => $token->token_id)
			);
			
			return true;
		}
		
		return false;
	}
	
	public function _() {
		$this->origin = false;
		$this->unique = false;

		$method = $_REQUEST['_method'];

		unset($_REQUEST['_method']);
		unset($_REQUEST['_chain']);
		unset($_REQUEST['_unique']);

		echo @$this->$method($_REQUEST);
		exit;
	}
	
	public function auth($username, $password, $type = 'basic') {
		return $this->client->setCredentials($username, $password, $type);
	}
	
	public function __call($method, $arg) {
		if (empty($this->url)) {
			error_log('libws: No url is configured.');
			return;
		}

		if (!is_array($arg)) {
			$arg = array($arg);
		}

		if (count($arg) == 1 && isset($arg[0]) && is_array($arg[0])) {
			$arg = $arg[0];
		}

		if (strpos($this->destiny, 'facebook') !== false) {
			$add = array(
				'APPID' => '#APPID',
				'APPSECRET' => '#APPSECRET'
			);
			$arg = array_merge($add, $arg);
		}

		if (isset($arg) && is_array($arg)) {
			$arg = $this->_param_replace($arg);
		} else {
			$arg_cp = $arg;
			$_arg = isset($arg[0]) ? w($arg[0]) : w();

			$arg = w();
			foreach ($_arg as $v) {
				if (isset($_REQUEST[$v])) $arg[$v] = $_REQUEST[$v];
			}

			$arg = (!$arg) ? $arg_cp : $arg;
		}

		$_bridge = $this->bridge;
		$_url = $this->url;

		$count_bridge = count($_bridge);
		$response = null;

		switch ($this->type) {
			case 'wsdl':
				$this->client = new nusoap_client($this->url, $this->wsdl);

				if ($error = $this->client->getError()) {
					echo 'Client error: ' . $error;
					exit;
				}

				$response = $this->client->call($method, $arg);
				
				// Check if there were any call errors, and if so, return error messages.
				if ($this->client->getError()) {
					$response = $this->client->response;
					$response = substr($response, strpos($response, '<?xml'));
					$response = xml2array($response);
					
					if (isset($response['soap:Envelope']['soap:Body']['soap:Fault']['faultstring'])) {
						$fault_string = $response['soap:Envelope']['soap:Body']['soap:Fault']['faultstring'];
						
						$response = explode("\n", $fault_string);
						$response = $response[0];
					} else {
						$response = $this->client->getError();
					}
					
					$response = array(
						'error' => true,
						'message' => $response
					);
				}
				
				$response = json_decode(json_encode($this->_filter($response)));
				break;
			case 'mysql':
				if (isset($arg['_mysql'])) {
					$this->params['_MYSQL'] = $arg['_mysql'];
					unset($arg['_mysql']);
				}

				$connect = (isset($this->params['_MYSQL']) && $this->params['_MYSQL']) ? $this->params['_MYSQL'] : '';

				if (empty($arg)) {
					return false;
				}

				global $db;

				require_once('class.mysql.php');
				$db = new database($connect);

				if (count($arg) > 1) {
					$sql = array_shift($arg);
					$arg = sql_filter($sql, $arg);
				}

				$response = (@function_exists($method)) ? $method($arg) : array('error' => true, 'message' => $method . ' is undefined');
				break;
			case 'php':
				if (isset($arg['_php'])) {
					unset($arg['_php']);
				}

				$print = w();
				switch ($method) {
					case 'tail':
					case 'cat':
						if (!@is_readable($arg[0])) {
							$response = 'Can not read file: ' . $arg[0];
						}
						break;
					case 'ping':
						$arg[1] = '-c' . ((isset($arg[1])) ? $arg[1] : 3);
						break;
				}

				switch ($method) {
					case 'tail':
					case 'cat':
					case 'ping':
					case 'exec':
						if ($response === null) {
							exec($method . ' ' . implode(' ', $arg), $print);
							$response = implode("\r\n", $print);
						}
						break;
					default:
						ob_start();

						if (@function_exists($method) || $method == 'eval') {
							eval(($method == 'eval') ? $arg[0] : 'echo @$method(' . (count($arg) ? "'" . implode("', '", $arg) . "'" : '') . ');');

							$_arg = error_get_last();
						} else {
							$_arg = array('message' => 'PHP Fatal error: Call to undefined function ' . $method . '()');
						}

						$response = (null === $_arg) ? ob_get_contents() : array('url' => $_url . $method, 'error' => 500, 'message' => $_arg['message']);

						ob_end_clean();
						break;
				}
				break;
			case 'facebook':
				if (isset($arg['_facebook'])) {
					unset($arg['_facebook']);
				}

				//header('Content-type: text/html; charset=utf-8');
				require_once('class.facebook.php');

				$facebook = new Facebook(array(
					'appId'  => $arg['APPID'],
					'secret' => $arg['APPSECRET'])
				);
				unset($arg['APPID'], $arg['APPSECRET']);

				try {
					$page = array_shift($arg);
					$page = (is_string($page)) ? '/' . $page : $page;
					
					$req = (isset($arg[0]) && is_string($arg[0])) ? array_shift($arg) : '';
					$req = (empty($req)) ? 'get' : $req;

					$arg = (isset($arg[0])) ? $arg[0] : $arg;

					$response = (!empty($page)) ? (count($arg) ? $facebook->$method($page, $req, $arg) : $facebook->$method($page, $req)) : $facebook->$method();
				} catch (FacebookApiException $e) {
					$response = array(
						'url' => $_url,
						'error' => 500,
						'message' => trim(str_replace('OAuthException: ', '', $e))
					);

					error_log($e);
				}

				unset($facebook);

				/*
				$feed = array(
					//$facebook->api($page)
					//$facebook->api('/228224130571301')
				);

				$attr = array(
					'access_token' => '125858306409|f1e0c20bc063e5f9a0c89615.1-1134314335|48722647107|JOI6oOl4sdhfX8Xf-rU3MfRwl70',
					'message' => 'Coca Cola!'
				);
				$feed['kamil'] = $facebook->api('/40796308305/posts/10150378826523306', 'post', $attr);*/
				break;
			default:
				$send_var = w('sso mysql php facebook');
				$send = new stdClass;

				if ($count_bridge == 1 && $_bridge[0] === $_url) {
					$count_bridge--;
					array_shift($_bridge);
				}

				foreach ($send_var as $row) {
					$val = '_' . strtoupper($row);
					$send->$row = (isset($this->params[$val]) && $this->params[$val]) ? $this->params[$val] : false;

					if (!$count_bridge && ($send->$row || isset($arg['_' . $row]))) {
						$this->type = $row;
					}
				}

				switch ($this->type) {
					case 'sso':
						$this->origin = false;

						$_url .= $method;
						unset($arg['_sso']);
						break;
					default:
						foreach ($send_var as $row) {
							if (isset($send->$row) && !empty($send->$row)) {
								$arg['_' . $row] = $send->$row;
							}
						}

						$arg['_method'] = $method;
						$arg['_unique'] = (!$this->unique) ? $this->unique : 1;
						
						if (isset($_bridge) && count($_bridge)) {
							array_shift($_bridge);
							$arg['_chain'] = implode('|', $_bridge);
						}
						break;
				}

				// _pre($arg, true);

				$_arg = $arg;
				$arg = ($this->type == 'sso') ? $this->_build($arg, false) : __encode($arg);

				$socket = @curl_init();
				@curl_setopt($socket, CURLOPT_URL, $_url);
				@curl_setopt($socket, CURLOPT_VERBOSE, 0);
				@curl_setopt($socket, CURLOPT_HEADER, 0);
				@curl_setopt($socket, CURLOPT_RETURNTRANSFER, 1);
				@curl_setopt($socket, CURLOPT_POST, 1);
				@curl_setopt($socket, CURLOPT_POSTFIELDS, $arg);
				@curl_setopt($socket, CURLOPT_SSL_VERIFYPEER, 0);
				@curl_setopt($socket, CURLOPT_SSL_VERIFYHOST, 1);

				$response = @curl_exec($socket);

				$_curl = new stdClass;
				$_curl->err = @curl_errno($socket);
				$_curl->msg = @curl_error($socket);
				$_curl->inf = (object) @curl_getinfo($socket);
				@curl_close($socket);

				switch ($_curl->err) {
					/**
					If the request has no errors.
					*/
					case 0:
						switch ($this->type) {
							/**
							SSO type
							*/
							case 'sso':
								if (preg_match('#<body>(.*?)</body>#i', $response, $response_part)) {
									preg_match('#<p><b>description</b>(.*?)</p>#i', $response_part[1], $status);
									
									$response = array(
										'url' => $_url,
										'error' => $_curl->inf->http_code,
										'message' => trim($status[1])
									);
								} else {
									switch($method) {
										case 'search':
											break;
										default:
											$first_parts = explode('&', substr($response, 0, -1));
											
											$ret = w();
											foreach ($first_parts as $v) {
												$second_parts = explode('=', $v);
									
												if (!isset($second_parts[1])) {
													continue;
												}
												
												$second_parts[0] = str_replace('.', '_', $second_parts[0]);
												$ret[$second_parts[0]] = $second_parts[1];
											}

											$response = $this->_format($response);
											break;
									}
								}
								break;
							/**
							Any other type
							*/
							default:
								$_json = json_decode($response);

								if ($_json === null) {
									$response = trim($response);
									$response = (!empty($response)) ? $response : $_curl->inf;

									$_json = $response;

									/*$_json = array(
										'url' => $_url,
										'error' => 500,
										'message' => $response
									);*/
								}
								
								$response = $_json;
								break;
						}
						break;
					/**
					Some error was generated after the request.
					*/
					default:
						$response = array(
							'url' => $_url,
							'error' => 500,
							'message' => $_curl->msg
						);
						break;
				}

				break;
		}

		if (!$this->origin) {
			$response = json_encode($response);
		}

		if ($this->type == 'sso' && $this->unique) {
			$response = json_decode($response);
		}

		if (is_array($response) && isset($response[0]) && is_string($response[0]) && strpos($response[0], '<?xml') !== false) {
			$response = array_change_key_case_recursive(xml2array($response[0]));

			$response = json_decode(json_encode($response));
		}

		return $response;
	}
}

//
// General functions
//
function w($a = '', $d = false) {
	if (empty($a) || !is_string($a)) return array();
	
	$e = explode(' ', trim($a));
	if ($d !== false) {
		foreach ($e as $i => $v) {
			$e[$v] = $d;
			unset($e[$i]);
		}
	}
	
	return $e;
}

function array_change_key_case_recursive($input, $case = null) {
	if (!is_array($input)) {
		trigger_error("Invalid input array '{$array}'",E_USER_NOTICE); exit;
	}

	// CASE_UPPER|CASE_LOWER
	if (null === $case) {
		$case = CASE_LOWER;
	}

	if (!in_array($case, array(CASE_UPPER, CASE_LOWER))) {
		trigger_error("Case parameter '{$case}' is invalid.", E_USER_NOTICE); exit;
	}

	$input = array_change_key_case($input, $case);
	foreach ($input as $key => $array) {
		if (is_array($array)) {
			$input[$key] = array_change_key_case_recursive($array, $case);
		}
	}

	return $input;
}

function hex2asc($str) {
	$str2 = '';
	for ($n = 0, $end = strlen($str); $n < $end; $n += 2) {
		$str2 .=  pack('C', hexdec(substr($str, $n, 2)));
	}
	
	return $str2;
}

function encode($str) {
	return bin2hex(base64_encode($str));
}

function decode($str) {
	return base64_decode(hex2asc($str));
}

if (!function_exists('_pre')) {
	function _pre($a, $d = false) {
		echo '<pre>';
		print_r($a);
		echo '</pre>';
		
		if ($d === true) {
			exit;
		}
	}
}

function __encode($arg) {
	foreach ($arg as $i => $row) {
		$_i = encode($i);
		$arg[$_i] = encode(json_encode($row));
		unset($arg[$i]);
	}

	return $arg;
}

function __decode($arg) {
	foreach ($arg as $i => $row) {
		$_i = decode($i);
		$arg[$_i] = json_decode(decode($row));
		unset($arg[$i]);
	}
	
	return $arg;
}

function __($url = '', $wsdl = 0) {
	if (!isset($_REQUEST)) {
		exit;
	}

	$_REQUEST = __decode($_REQUEST);
	if (!isset($_REQUEST['_method']) || !isset($_REQUEST['_chain'])) {
		exit;
	}
	
	$url = explode('|', $_REQUEST['_chain']);
	$wsdl = 0;
	
	if (count($url) == 1 && preg_match('#wsdl(\=(true|false))?$#is', $url[0], $part)) {
		if (isset($part[2])) {
			$url[0] = str_replace('=' . $part[2], '', $url[0]);
		} else {
			$part[2] = 1;
		}
		
		$wsdl = ($part[2] == 'false') ? false : true;
	}

	$ws = new libws($url, $wsdl);
	return $ws->_();
}

?>