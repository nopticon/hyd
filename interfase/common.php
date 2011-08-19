<?php
// -------------------------------------------------------------
// $Id: common.php,v 1.1 2006/02/16 04:41:40 Psychopsia Exp $
//
// STARTED   : Fri Dec 12, 2003
// COPYRIGHT : 2006 Rock Republik
// -------------------------------------------------------------

if (!defined('IN_NUCLEO'))
{
	die('Rock Republik');
}

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ALL);
//set_magic_quotes_runtime(0);

// Protect against GLOBALS tricks
if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
	exit;
}

// Protect against _SESSION tricks
if (isset($_SESSION) && !is_array($_SESSION)) {
	exit;
}

// Be paranoid with passed vars
if (@ini_get('register_globals') == '1' || strtolower(@ini_get('register_globals')) == 'on') {
	$not_unset = array('_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_SESSION', '_ENV', '_FILES', 'phpEx', 'phpbb_root_path');

	// Not only will array_merge give a warning if a parameter
	// is not an array, it will actually fail. So we check if
	// _SESSION has been initialised.
	if (!isset($_SESSION) || !is_array($_SESSION)) {
		$_SESSION = array();
	}

	// Merge all into one extremely huge array; unset
	// this later
	$input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_SESSION, $_ENV, $_FILES);

	foreach ($input as $varname => $void) {
		if (!in_array($varname, $not_unset)) {
			unset(${$varname});
		}
	}

	unset($input);
}

//
// Set the root path
//
if (!defined('ROOT')) {
	define('ROOT', './');
}

//
// Start the main system
//
define('USE_CACHE', true);
define('STRIP', (get_magic_quotes_gpc()) ? true : false);

if (!defined('REQC')) {
	define('REQC', (strtolower(ini_get('request_order')) == 'gp'));
}

require(ROOT.'interfase/constants.php');
require(ROOT.'interfase/mysql.php');
require(ROOT.'interfase/template.php');
require(ROOT.'interfase/session.php');
require(ROOT.'interfase/functions.php');
require(ROOT.'interfase/cache.php');

set_error_handler('msg_handler');

// Make the database connection
$db = new sql_db();

$user = new user();
$auth	= new auth();
$cache = new cache();
$template	= new template();
$config = $cache->config();

?>
