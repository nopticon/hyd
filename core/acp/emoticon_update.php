<?php namespace App;

class __emoticon_update extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        sql_truncate('_smilies');

        $emoticon_path = config('assets_path') . 'emoticon/';
        $process = 0;

        $fp = @opendir($emoticon_path);
        while ($file = @readdir($fp)) {
            if (preg_match('#([a-z0-9]+)\.(gif|png)#is', $file, $part)) {
                $insert = [
                    'code'      => ':' . $part[1] . ':',
                    'smile_url' => $part[0]
                ];
                sql_insert('smilies', $insert);

                $process++;
            }
        }
        @closedir($fp);

        $cache->delete('smilies');

        return _pre($process . ' emoticons.');
    }
}
