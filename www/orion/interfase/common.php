<?php
namespace App;

$starttime = explode(' ', microtime());
$starttime = $starttime[1] + $starttime[0];

//error_reporting(E_ERROR | E_WARNING | E_PARSE);
error_reporting(E_ALL);

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
    $not_unset = array('_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_SESSION', '_ENV', '_FILES');

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

require_once ROOT . 'interfase/Constants.php';
require_once ROOT . 'interfase/Functions.php';
require_once ROOT . 'interfase/Database.php';
require_once ROOT . 'interfase/Mysqli.php';
require_once ROOT . 'interfase/Template.php';
require_once ROOT . 'interfase/Session.php';
require_once ROOT . 'interfase/User.php';
require_once ROOT . 'interfase/Auth.php';
require_once ROOT . 'interfase/Cache.php';
require_once ROOT . 'interfase/Comments.php';
require_once ROOT . 'interfase/Emailer.php';
require_once ROOT . 'interfase/Upload.php';
require_once ROOT . 'interfase/Downloads.php';
require_once ROOT . 'interfase/Ftp.php';
require_once ROOT . 'interfase/Zip.php';

set_error_handler('msg_handler');

$db       = new Database();
$user     = new User();
$auth     = new Auth();
$cache    = new Cache();
$template = new Template();
$comments = new Comments();
$upload   = new Upload();

$config   = $cache->config();
