<?php
namespace App;

require_once '../core/classes/Constants.php';
require_once '../core/classes/Common.php';

$config['cache_path'] = '/var/www/prd/rock-republik/cache/';

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
            exit;
        case 'sign':
            do_login();
            exit;
    }

    require_once ROOT . 'modules/' . $pagename . '.php';

    switch ($pagename) {
        case 'home':
            require_once ROOT . 'modules/artists.php';
            require_once ROOT . 'modules/events.php';
            break;
        case 'events':
            require_once ROOT . 'modules/downloads.php';
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