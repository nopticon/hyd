<?php namespace App;

class News {
    private $data = [];
    private $template;
    private $title;

    private $default_title = 'NEWS';
    private $default_view = 'news';

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
        $news_alias  = request_var('alias', '');
        $news_module = request_var('module', '');

        if (!empty($news_module)) {
            return $this->action($news_module);
        }

        if (empty($news_alias)) {
            return $this->all();
        }

        if (!preg_match('#[a-z0-9\_\-]+#i', $news_alias)) {
            fatal_error();
        }

        $news_field = !is_numb($news_alias) ? 'news_alias' : 'news_id';

        $sql = 'SELECT *
            FROM _news n
            INNER JOIN _news_cat c ON n.cat_id = c.cat_id
            WHERE n.?? = ?';
        if (!$this->data = sql_fieldrow(sql_filter($sql, $news_field, $news_alias))) {
            fatal_error();
        }

        if ($news_field === 'news_id' && !empty($this->data['news_alias'])) {
            redirect(s_link('news', $this->data['news_alias']));
        }

        return $this->object();
    }

    public function action($module) {
        global $user, $cache;

        switch ($module) {
            case 'create':
                $submit = _button();

                if ($submit) {
                    $cat_id       = request_var('cat_id', 0);
                    $news_active  = 0;
                    $news_alias   = '';
                    $news_subject = '';
                    $news_text    = '';
                    $news_desc    = '';

                    $sql_insert = [
                        'news_fbid'    => 0,
                        'cat_id'       => '',
                        'news_active'  => $mews_active,
                        'news_alias'   => $news_alias,
                        'post_reply'   => 0,
                        'post_type'    => 0,
                        'poster_id'    => $user->d('user_id'),
                        'post_subject' => $news_subject,
                        'post_text'    => $news_text,
                        'post_desc'    => $news_desc,
                        'post_views'   => 0,
                        'post_replies' => 0,
                        'post_time'    => time(),
                        'post_ip'      => $user->ip,
                        'image'        => ''
                    ];
                    $news_id = sql_insert('news', $sql_insert);
                }

                $sql = 'SELECT cat_id, cat_name
                    FROM _news_cat
                    ORDER BY cat_order';
                $news_cat = sql_rowset($sql);

                foreach ($news_cat as $i => $row) {
                    if (!$i) {
                        _style('news_cat');
                    }

                    _style('news_cat.row', [
                        'CAT_ID'   => $row['cat_id'],
                        'CAT_NAME' => $row['cat_name']
                    ]);
                }
                break;
        }
    }

    public function all() {
        global $user, $cache;

        $sql = 'SELECT n.*, m.username, m.username_base
            FROM _news n, _members m
            WHERE n.poster_id = m.user_id
            ORDER BY n.post_time DESC, n.news_id DESC';
        $result = sql_rowset($sql);

        foreach ($result as $i => $row) {
            if (!$i) {
                _style('cat');
            }

            _style('cat.row', [
                'URL'      => s_link('news', $row['news_alias']),
                'SUBJECT'  => $row['post_subject'],
                'DESC'     => $row['post_desc'],
                'TIME'     => $user->format_date($row['post_time'], 'd M'),
                'USERNAME' => $row['username'],
                'PROFILE'  => s_link('m', $row['username_base'])
            ]);
        }

        return;
    }

    public function object() {
        global $user, $comments;

        $offset = request_var('ps', 0);

        if ($this->data['poster_id'] != $user->d('user_id') && !$offset) {
            $sql = 'UPDATE _news SET post_views = post_views + 1
                WHERE news_id = ?';
            sql_query(sql_filter($sql, $this->data['news_id']));
        }

        $news_main = [
            'MESSAGE'   => $comments->parse_message($this->data['post_text']),
            'POST_TIME' => $user->format_date($this->data['post_time'])
        ];

        $sql = 'SELECT user_id, username, username_base, user_avatar, user_posts, user_gender, user_rank
            FROM _members
            WHERE user_id = ?';
        $result = sql_fieldrow(sql_filter($sql, $this->data['poster_id']));

        $user_profile = $comments->user_profile($result);
        $news_main    = array_merge($news_main, _style_uv($user_profile));

        _style('mainpost', $news_main);

        $comments_ref = s_link('news', $this->data['news_alias']);

        if ($this->data['post_replies']) {
            $comments->reset();
            $comments->ref = $comments_ref;

            $sql = 'SELECT p.*, m.user_id, m.username, m.username_base, m.user_avatar, m.user_rank,
                    m.user_posts, m.user_gender, m.user_sig
                FROM _news_posts p, _members m
                WHERE p.news_id = ?
                    AND p.post_active = 1
                    AND p.poster_id = m.user_id
                ORDER BY p.post_time DESC
                LIMIT ??, ??';

            $comments->data = [
                'SQL' => sql_filter($sql, $this->data['news_id'], $offset, config('posts_per_page'))
            ];

            $comments->view($offset, 'ps', $this->data['post_replies'], config('posts_per_page'), '', '', 'TOPIC_');
        }

        v_style([
            'CAT_URL'      => s_link('news', $this->data['cat_url']),
            'CAT_NAME'     => $this->data['cat_name'],
            'POST_SUBJECT' => $this->data['post_subject'],
            'POST_REPLIES' => number_format($this->data['post_replies'])
        ]);

        //
        // Posting box
        //
        if ($user->is('member')) {
            _style('publish', [
                'REF' => $comments_ref
            ]);
        }

        $this->template = 'news.view';
        $this->title    = $this->data['post_subject'];

        return;
    }
}
