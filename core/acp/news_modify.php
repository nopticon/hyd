<?php namespace App;

class __news_modify extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        $submit2 = _button('submit2');

        if (_button() || $submit2) {
            $news_id = request_var('news_id', 0);

            $sql = 'SELECT *
                FROM _news
                WHERE news_id = ?';
            if (!$news_data = sql_fieldrow(sql_filter($sql, $news_id))) {
                fatal_error();
            }

            if ($submit2) {
                $post_subject = request_var('post_subject', '');
                $post_desc    = request_var('post_desc', '', true);
                $post_message = request_var('post_text', '', true);

                if (empty($post_desc) || empty($post_message)) {
                    _pre('Campos requeridos.', true);
                }

                $comments = new _comments();

                $post_message = $comments->prepare($post_message);
                $post_desc    = $comments->prepare($post_desc);

                //
                $sql = 'UPDATE _news SET post_subject = ?, post_desc = ?, post_text = ?
                    WHERE news_id = ?';
                sql_query(sql_filter($sql, $post_subject, $post_desc, $post_message, $news_id));

                $cache->delete('news');
                redirect(s_link('news', $news_id));
            }

            if (_button()) {
                _style('edit', [
                    'ID'      => $news_data['news_id'],
                    'SUBJECT' => $news_data['post_subject'],
                    'DESC'    => $news_data['post_desc'],
                    'TEXT'    => $news_data['post_text']
                ]);
            }
        }

        if (!_button()) {
            _style('field');
        }

        return;
    }
}
