<?php namespace App;

class __news_create extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('colab');
    }

    public function home() {
        global $user, $cache, $upload, $comments;

        if (_button()) {
            $cat_id       = request_var('cat_id', 0);
            $post_subject = request_var('post_subject', '');
            $post_desc    = request_var('post_desc', '', true);
            $post_message = request_var('post_text', '', true);

            if (empty($post_desc) || empty($post_message)) {
                _pre('Campos requeridos.', true);
            }

            $post_message = $comments->prepare($post_message);
            $post_desc = $comments->prepare($post_desc);
            $news_alias = friendly($post_subject);

            //
            $sql_insert = [
                'news_fbid'    => '',
                'cat_id'       => $cat_id,
                'news_active'  => 1,
                'news_alias'   => $news_alias,
                'post_reply'   => 0,
                'post_type'    => 0,
                'poster_id'    => $user->d('id'),
                'post_subject' => $post_subject,
                'post_text'    => $post_message,
                'post_desc'    => $post_desc,
                'post_views'   => 0,
                'post_replies' => 0,
                'post_time'    => time(),
                'post_ip'      => $user->ip,
                'image'        => 0
            ];
            $sql = 'INSERT _news' . sql_build('INSERT', $sql_insert);
            $news_id = sql_query_nextid($sql);

            // Upload news thumbnail

            $send = $upload->process(config('news_path'), 'thumbnail');

            if ($send !== false) {
                foreach ($send as $row) {
                    $resize = $upload->resize(
                        $row,
                        config('news_path'),
                        config('news_path'),
                        $news_id,
                        [100, 100],
                        false,
                        false,
                        true
                    );

                    if ($resize === false) {
                        continue;
                    }
                }
            }

            $cache->delete('news');
            redirect(s_link('news', $news_alias));
        }

        $sql = 'SELECT cat_id, cat_name
            FROM _news_cat
            ORDER BY cat_order';
        $news_cat = sql_rowset($sql);

        foreach ($news_cat as $i => $row) {
            if (!$i) {
                _style('cat');
            }

            _style('cat.row', [
                'CAT_ID'   => $row['cat_id'],
                'CAT_NAME' => $row['cat_name']
            ]);
        }

        return;
    }
}
