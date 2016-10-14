<?php

require_once './interfase/common.php';

$user->init();
$user->setup();

if (!$smilies = $cache->get('smilies')) {
    $sql = 'SELECT *
        FROM _smilies
        ORDER BY LENGTH(code) DESC';
    if ($smilies = sql_rowset($sql)) {
        $cache->save('smilies', $smilies);
    }
}

foreach ($smilies as $smile_url => $data) {
    _style(
        'smilies_row',
        array(
            'CODE'  => $data['code'],
            'IMAGE' => $config['assets_url'] . '/emoticon/' . $data['smile_url'],
            'DESC'  => $data['emoticon']
        )
    );
}

page_layout('EMOTICONS', 'emoticons');
