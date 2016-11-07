<?php
namespace App;

class Emoticons {
    private $default_title = 'EMOTICONS';
    private $default_view = 'emoticons';

    public function __construct() {
        return;
    }

    public function getTitle($default = '') {
        return !empty($this->title) ? $this->title : $this->default_title;
    }

    public function getTemplate($default = '') {
        return !empty($this->template) ? $this->template : $this->default_view;
    }

    public function run() {
        global $cache;

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
                    'IMAGE' => config('assets_url') . '/emoticon/' . $data['smile_url'],
                    'DESC'  => $data['emoticon']
                )
            );
        }

        return;
    }
}
