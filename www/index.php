<?php
namespace App;

require_once '../vendor/autoload.php';
require_once '../core/classes/Constants.php';
require_once '../core/classes/Common.php';

$config['mailgun_key'] = 'key-c1bac79ed8fdb0a81502deae8fe94d90';
$config['mailgun_domain'] = 'rockrepublik.net';

$config['cache_path'] = '/var/www/prd/rock-republik/cache/';
$config['assets_url'] = '/dist/images/';

$user->init();
$user->setup();

$pagename = request_var('pagename', '');

$pages = array(
    'acp'       => '_acp',
    'artists'   => 'Artists',
    'async'     => 'Async',
    'awards'    => 'Awards',
    'board'     => 'Board',
    'broadcast' => 'Broadcast',
    'comments'  => 'Comments',
    'community' => 'Community',
    'cron'      => 'Cron',
    'emoticons' => 'Emoticons',
    'events'    => 'Events',
    'help'      => 'Help',
    'home'      => 'Home',
    'news'      => 'News',
    'partners'  => 'Partners',
    'rss'       => 'Rss',
    'sign'      => 'Sign',
    'today'     => 'Today',
    'topic'     => 'Topic',
    'topics'    => 'topics',
    'tos'       => 'Tos',
    'userpage'  => 'Userpage',
    'win'       => 'Win'
);

if (isset($pages[$pagename])) {
    switch ($pagename) {
        case 'comments':
            $comments->receive();
            break;
        case 'sign':
            do_login();
            break;
    }

    require_once ROOT . 'modules/' . $pagename . '.php';

    switch ($pagename) {
        case 'home':
            require_once ROOT . 'modules/artists.php';
            require_once ROOT . 'modules/events.php';
            break;
        case 'events':
            require_once ROOT . 'classes/Downloads.php';
            break;
    }

    $classname = __NAMESPACE__ . '\\' . $pages[$pagename];

    $instance = new $classname();
    $instance->run();

    if (!isset($instance->no_layout)) {
        page_layout($instance->getTitle(), $instance->getTemplate());
    }
}

exit;
