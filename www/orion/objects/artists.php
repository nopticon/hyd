<?php
namespace App;

class Artists extends Downloads {
    public $auth = array();
    public $data = array();
    public $adata = array();
    public $images = array();
    public $layout = array();
    public $voting = array();
    public $msg = array();
    public $ajx = true;

    private $make;
    private $template;
    private $title;

    public function __construct() {
        $this->layout = array(
            '_01' => array('code' => 1, 'text' => 'UB_L01', 'tpl' => 'main'),
            '_02' => array('code' => 2, 'text' => 'UB_L02', 'tpl' => 'bio'),
            '_03' => array('code' => 3, 'text' => 'UB_L03', 'tpl' => 'albums'),
            '_04' => array('code' => 4, 'text' => 'UB_L04', 'tpl' => 'gallery'),
            '_05' => array('code' => 5, 'text' => 'UB_L05', 'tpl' => 'tabs'),
            '_06' => array('code' => 6, 'text' => 'UB_L06', 'tpl' => 'lyrics'),
            '_07' => array('code' => 7, 'text' => 'UB_L07', 'tpl' => 'interviews'),
            '_09' => array('code' => 9, 'text' => 'DOWNLOADS', 'tpl' => 'downloads'),
            '_13' => array('code' => 13, 'text' => '', 'tpl' => 'email'),
            '_16' => array('code' => 16, 'text' => '', 'tpl' => 'news'),
            '_18' => array('code' => 18, 'text' => 'UB_L17', 'tpl' => 'video')
        );

        $this->voting = array(
            'ub' => array(1, 2, 3, 5),
            'ud' => array(1, 2, 3, 4, 5)
        );
    }

    /*
    Default layout for artist.
    */
    public function main() {
        if ($this->make) {
            return true;
        }

        global $user, $config, $comments;

        //
        // Gallery
        //
        $image = '';
        if ($this->data['images']) {
            $simage = $this->getImages(false, $this->data['ub'], true);
            $imagedata = $this->images[$this->data['ub']][$simage];
            $image = $imagedata['path'];
        }

        _style(
            'ub_image',
            array(
                'IMAGE' => $image
            )
        );

        if ($this->data['images'] > 1) {
            _style(
                'ub_image.view',
                array(
                    'URL' => s_link('a', $this->data['subdomain'], 'gallery', $imagedata['image'], 'view')
                )
            );
        }

        //
        // News
        //
        if ($this->auth['user']) {
            _style(
                'publish',
                array(
                    'TITLE' => ($this->auth['mod']) ? lang('send_news') : lang('send_post'),
                    'URL' => s_link('a', $this->data['subdomain'])
                )
            );
        }

        if ($this->data['news'] || $this->data['posts']) {
            $sql = "(SELECT ?, p.topic_id, p.post_text, t.topic_time as post_time, m.user_id,
                    m.username, m.username_base, m.user_avatar
                FROM _forum_topics t, _forum_posts p, _members m
                WHERE t.forum_id = ?
                    AND t.topic_ub = ?
                    AND t.topic_poster = m.user_id
                    AND p.post_id = t.topic_first_post_id
                    AND t.topic_important = 0
                ORDER BY t.topic_time DESC)
                    UNION ALL
                (SELECT ?, p.post_id, p.post_text, p.post_time, m.user_id, m.username,
                    m.username_base, m.user_avatar
                FROM _artists a, _artists_posts p, _members m
                WHERE p.post_ub = ?
                    AND p.post_ub = a.ub
                    AND p.post_active = 1
                    AND p.poster_id = m.user_id
                ORDER BY p.post_time DESC
                LIMIT ??, ??)
                ORDER BY post_time DESC";
            $sql = sql_filter($sql, 'news', $config['ub_fans_f'], $this->data['ub'], 'post', $this->data['ub'], 0, 10);

            if ($result = sql_rowset($sql)) {
                _style('news');

                $user_profile = w();
                foreach ($result as $row) {
                    $uid = $row['user_id'];

                    if (!isset($user_profile[$uid]) || ($uid == GUEST)) {
                        $user_profile[$uid] = $comments->user_profile($row);
                    }

                    if (!isset($row['post_id'])) {
                        $row['post_id'] = 0;
                    }

                    $row_data = array(
                        'POST_ID'  => $row['post_id'],
                        'DATETIME' => $user->format_date($row['post_time']),
                        'MESSAGE'  => $comments->parse_message($row['post_text']),
                        'S_DELETE' => false
                    );

                    foreach ($user_profile[$uid] as $key => $value) {
                        $row_data[strtoupper($key)] = $value;
                    }

                    _style('news.row', $row_data);
                }
            }

            $total_rows = $this->data['news'] + $this->data['posts'];
        }

        //
        // Auth Members
        //
        if ($this->auth['mod'] || $this->data['mods_legend']) {
            $sql = 'SELECT b.ub, u.user_id, u.username, u.username_base, u.user_avatar, u.user_avatar_type
                FROM _artists_auth a, _artists b, _members u
                WHERE a.ub = ?
                    AND a.ub = b.ub
                    AND a.user_id = u.user_id
                ORDER BY u.username';
            if ($result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
                foreach ($result as $i => $row) {
                    if (!$i) {
                        _style('mods');
                    }

                    $user_profile = $comments->user_profile($row);

                    _style(
                        'mods.item',
                        array(
                            'PROFILE'  => $user_profile['profile'],
                            'USERNAME' => $user_profile['username']
                        )
                    );
                }

                $comments->reset();

                if ($this->auth['mod']) {
                    _style(
                        'mods.manage',
                        array(
                            'URL' => s_link('acp', array('artist_auth', 'a' => $this->data['subdomain']))
                        )
                    );
                }
            }
        }

        return;
    }

    //
    // Layout to show artist's biography
    //
    public function artistBio() {
        global $config, $comments;

        if ($this->make) {
            return ($this->data['bio']);
        }

        if ($this->data['featured_image']) {
            $image = $config['artists_url'] . $this->data['ub'] . '/gallery/' . $this->data['featured_image'] . '.jpg';

            _style(
                'featured_image',
                array(
                    'IMAGE' => $image,
                    'URL'   => s_link('a', $this->data['subdomain'], 'gallery', $this->data['featured_image'], 'view')
                )
            );
        }

        //
        // Parse Biography
        //
        v_style(
            array(
                'UB_BIO' => $comments->parse_message($this->data['bio'])
            )
        );

        return;
    }

    /*
    Layout to show artist's albums.
    */
    public function artistAlbums() {
        if ($this->make) {
            return;
        }

        return;
    }

    /*
    Show all pictures associated to this artist.
    */
    public function artistGallery() {
        global $config;

        if ($this->make) {
            return ($this->data['images'] > 1);
        }

        $mode = request_var('mode', '');
        $download_id = request_var('download_id', 0);

        if ($mode == 'view') {
            if (!$download_id) {
                redirect(s_link('a', $this->data['subdomain'], 'gallery'));
            }

            if ($mode == 'view') {
                $sql = 'SELECT g.*, COUNT(g2.image) AS prev_images
                    FROM _artists_images g, _artists_images g2
                    WHERE g.ub = ?
                        AND g2.ub = g.ub
                        AND g.image = ?
                        AND g2.image <= ?
                    GROUP BY g.image
                    ORDER BY g.image ASC';
                $sql = sql_filter($sql, $this->data['ub'], $download_id, $download_id);
            } else {
                $sql = 'SELECT g.*
                    FROM _artists a, _artists_images g
                    WHERE a.ub = ?
                        AND a.ub = g.ub
                        AND g.image = ?';
                $sql = sql_filter($sql, $this->data['ub'], $download_id);
            }

            if (!$imagedata = sql_fieldrow($sql)) {
                redirect(s_link('a', $this->data['subdomain'], 'gallery'));
            }
        }

        switch ($mode) {
            case 'view':
            default:
                if ($mode == 'view') {
                    if (!$this->auth['mod']) {
                        $sql = 'UPDATE _artists_images SET views = views + 1
                            WHERE ub = ?
                                AND image = ?';
                        sql_query(sql_filter($sql, $this->data['ub'], $imagedata['image']));
                    }

                    $image = $config['artists_url'] . $this->data['ub'] . '/gallery/' . $imagedata['image'] . '.jpg';

                    _style(
                        'selected',
                        array(
                            'IMAGE'  => $image,
                            'WIDTH'  => $imagedata['width'],
                            'HEIGHT' => $imagedata['height']
                        )
                    );

                    /*if ($imagedata['allow_dl']) {
                        _style('selected.download', array(
                            'URL' => s_link('a', $this->data['subdomain'], 'gallery', $imagedata['image'], 'save'))
                        );
                    }*/

                    $this->data['images']--;
                }

                //
                // Get thumbnails
                //
                $sql_image = ($download_id) ? sql_filter(' AND g.image <> ? ', $download_id) : '';

                $sql = 'SELECT g.*
                    FROM _artists a, _artists_images g
                    WHERE a.ub = ' . $this->data['ub'] . '
                        AND a.ub = g.ub
                        ' . $sql_image . '
                    ORDER BY image DESC';
                if (!$result = sql_rowset(sql_filter($sql, $this->data['ub']))) {
                    redirect(s_link('a', $this->data['subdomain'], 'gallery'));
                }

                foreach ($result as $i => $row) {
                    if (!$i) {
                        _style('thumbnails');
                    }

                    $image = $config['artists_url'] . $this->data['ub'] . '/thumbnails/' . $row['image'] . '.jpg';

                    $rimage = get_a_imagepath(
                        $config['artists_path'],
                        $config['artists_url'],
                        $this->data['ub'],
                        $row['image'] . '.jpg',
                        w('x1 gallery')
                    );

                    _style(
                        'thumbnails.row',
                        array(
                            'URL'    => s_link('a', $this->data['subdomain'], 'gallery', $row['image'], 'view'),
                            'IMAGE'  => $image,
                            'RIMAGE' => $rimage,
                            'WIDTH'  => $row['width'],
                            'HEIGHT' => $row['height'],
                            'FOOTER' => $row['image_footer']
                        )
                    );
                }
                break;
        }

        return;
    }

    /*
    Show all tabs for this artist.
    */
    public function artistTabs() {
        if ($this->make) {
            return;
        }

        return;
    }

    /*
    Show all lyrics associated to this artist.
    */
    public function artistLyrics() {
        if ($this->make) {
            return ($this->data['lirics'] > 0);
        }

        global $config, $lang;

        $mode = request_var('mode', '');
        $download_id = request_var('download_id', 0);

        if ($mode == 'view' || $mode == 'save') {
            if (!$download_id) {
                redirect(s_link('a', $this->data['subdomain'], 'lyrics'));
            }

            $sql = 'SELECT l.*
                FROM _artists_lyrics l
                LEFT JOIN _artists a ON a.ub = l.ub
                WHERE l.ub = ?
                    AND l.id = ?';
            if (!$lyric_data = sql_fieldrow(sql_filter($sql, $this->data['ub'], $download_id))) {
                redirect(s_link('a', $this->data['subdomain'], 'lyrics'));
            }
        }

        switch ($mode) {
            case 'view':
            default:
                if ($mode == 'view') {
                    if (!$this->auth['mod']) {
                        $sql = 'UPDATE _artists_lyrics SET views = views + 1
                            WHERE ub = ?
                            AND id = ?';
                        sql_query(sql_filter($sql, $this->data['ub'], $lyric_data['id']));

                        $lyric_data['views']++;
                    }

                    _style(
                        'read',
                        array(
                            'TITLE'  => $lyric_data['title'],
                            'AUTHOR' => $lyric_data['author'],
                            'TEXT'   => str_replace(nr(), '<br />', $lyric_data['text']),
                            'VIEWS'  => $lyric_data['views']
                        )
                    );
                }

                $sql = 'SELECT l.*
                    FROM _artists_lyrics l
                    LEFT JOIN _artists a ON a.ub = l.ub
                    WHERE l.ub = ?
                    ORDER BY title';
                $result = sql_rowset(sql_filter($sql, $this->data['ub']));

                foreach ($result as $i => $row) {
                    if (!$i) {
                        _style('select');
                    }

                    _style(
                        'select.item',
                        array(
                            'URL'      => s_link('a', $this->data['subdomain'], 'lyrics', $row['id'], 'view') . '#read',
                            'TITLE'    => $row['title'],
                            'SELECTED' => ($download_id && $download_id == $row['id']) ? true : false
                        )
                    );
                }
                break;
        }

        return;
    }

    /*
    Show all interviews made to this artist.
    */
    public function artistInterviews() {
        if ($this->make) {
            return;
        }

        return;
    }

    /*
    Show list of all songs available for listening and download.
    */
    public function artistDownloads() {
        if ($this->make) {
            return ($this->data['layout'] == 'downloads');
        }

        $this->downloadSetup();

        $mode = request_var('dl_mode', '');
        if ($mode == '') {
            $mode = 'view';
        }



        if (!in_array($mode, w('view save vote fav'))) {
            redirect(s_link('a', $this->data['subdomain']));
        }

        $mode = 'download' . ucfirst($mode);
        if (!method_exists($this, $mode)) {
            redirect(s_link('a', $this->data['subdomain']));
        }

        return $this->$mode();
    }

    /*
    Send private message from any user to this artist.
    */
    public function artistEmail() {
        if ($this->make) {
            return;
        }

        if (empty($this->data['email'])) {
            fatal_error();
        }

        if (!$this->auth['user']) {
            do_login();
        }

        global $user, $config;

        $error_msg = '';
        $subject = '';
        $message = '';
        $current_time = time();

        if (_button()) {
            $subject = request_var('subject', '');
            $message = request_var('message', '', true);

            if (empty($subject) || empty($message)) {
                $error_msg .= (($error_msg != '') ? '<br />' : '') . lang('fields_empty');
            }

            if (empty($error_msg)) {
                $sql = 'UPDATE _artists SET last_email = ?, last_email_user = ?
                    WHERE ub = ?';
                sql_query(sql_filter($sql, $current_time, $user->d('user_id'), $this->data['ub']));

                $emailer = new emailer($config['smtp_delivery']);

                $emailer->from($user->d('user_email'));

                $email_headers = 'X-AntiAbuse: User_id - ' . $user->d('user_id') . nr();
                $email_headers .= 'X-AntiAbuse: Username - ' . $user->d('username') . nr();
                $email_headers .= 'X-AntiAbuse: User IP - ' . $user->ip . nr();

                $emailer->use_template('mmg_send_email', $config['default_lang']);
                $emailer->email_address($this->data['email']);
                $emailer->set_subject($subject);
                $emailer->extra_headers($email_headers);

                $emailer->assign_vars(
                    array(
                        'SITENAME'      => $config['sitename'],
                        'BOARD_EMAIL'   => $config['board_email'],
                        'FROM_USERNAME' => $user->d('username'),
                        'UB_NAME'       => $this->data['name'],
                        'MESSAGE'       => $message
                    )
                );
                $emailer->send();
                $emailer->reset();

                redirect(s_link('a', $this->data['subdomain']));
            }
        }

        if ($error_msg != '') {
            _style('error');
        }

        v_style(
            array(
                'ERROR_MESSAGE' => $error_msg,
                'SUBJECT'       => $subject,
                'MESSAGE'       => $message
            )
        );

        return;
    }

    /*
    Register stats when user want to view artist's website.
    */
    public function artistWebsite() {
        if ($this->make) {
            return;
        }

        if ($this->data['www'] == '') {
            redirect(s_link('a', $this->data['subdomain']));
        }

        global $user;

        if (!$this->data['www_awc'] && !check_www($this->data['www'])) {
            trigger_error(sprintf(lang('links_cant_redirect'), $this->data['www']));
        }

        $sql = 'UPDATE _artists SET www_views = www_views + 1
            WHERE ub = ?';
        sql_query(sql_filter($sql, $this->data['ub']));

        header('Location: http://' . $this->data['www']);
        exit;
    }

    /*
    Manage artist's favorites from users.
    */
    public function artistFavorites() {
        if ($this->make) {
            return;
        }

        global $user;

        if (!$this->auth['user']) {
            do_login(sprintf(lang('login_be_fan'), $this->data['name']));
        }

        $url = s_link('a', $this->data['subdomain']);

        if ($this->auth['smod']) {
            redirect($url);
        }

        if ($this->auth['fav']) {
            $sql_member = array('user_a_favs' => $user->d('user_a_favs') - 1);

            if ($user->d('user_a_favs') == 1 && $user->d('user_type') == USER_FAN) {
                $sql_member += array('user_type' => USER_NORMAL);
            }

            $sql = 'DELETE FROM _artists_fav
                WHERE ub = ?
                    AND user_id = ?';
            sql_query(sql_filter($sql, $this->data['ub'], $user->d('user_id')));

            $user->delete_all_unread(UH_AF, $user->d('user_id'));
        } else {
            $sql_member = array('user_a_favs' => $user->d('user_a_favs') + 1);

            if ($user->d('user_type') == USER_NORMAL) {
                $sql_member += array('user_type' => USER_FAN);
            }

            $sql_insert = array(
                'ub' => (int) $this->data['ub'],
                'user_id' => (int) $user->d('user_id'),
                'joined' => time()
            );
            $fav_nextid = sql_insert('artists_fav', $sql_insert);

            $user->save_unread(UH_AF, $fav_nextid, $this->data['ub']);
        }

        $sql = 'UPDATE _members SET ??
            WHERE user_id = ?';
        sql_query(sql_build($sql, sql_build('UPDATE', $sql_member), $user->d('user_id')));

        redirect($url);

        return;
    }

    /*
    Manage news from this artist.
    */
    public function artistNews() {
        if ($this->make) {
            return;
        }

        return;
    }

    /*
    Users can vote and rate artist quality.
    */
    public function artistVote() {
        if ($this->make) {
            return;
        }

        if (!$this->auth['user']) {
            do_login();
        }

        $option_id = request_var('vote_id', 0);
        $url = s_link('a', $this->data['subdomain']);

        if ($this->auth['mod'] || !$option_id || !in_array($option_id, $this->voting['ub'])) {
            redirect($url);
        }

        global $user;

        $sql = 'SELECT user_id
            FROM _artists_voters
            WHERE ub = ?
                AND user_id = ?';
        if ($row = sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
            redirect($url);
        }

        //
        $sql = 'UPDATE _artists_votes SET vote_result = vote_result + 1
            WHERE ub = ?
                AND option_id = ?';
        sql_query(sql_filter($sql, $this->data['ub'], $option_id));

        if (!sql_affectedrows()) {
            $sql_insert = array(
                'ub' => $this->data['ub'],
                'option_id' => $option_id,
                'vote_result' => 1
            );
            sql_insert('artists_votes', $sql_insert);
        }

        $sql_insert = array(
            'ub' => $this->data['ub'],
            'user_id' => $user->d('user_id'),
            'user_option' => $option_id
        );
        sql_insert('artists_voters', $sql_insert);

        $sql = 'UPDATE _artists SET votes = votes + 1
            WHERE ub = ?';
        sql_query(sql_filter($sql, $this->data['ub']));

        redirect($url);
    }

    /*
    Show all artist's videos.
    */
    public function artistVideo() {
        if ($this->make) {
            return ($this->data['a_video'] > 0);
        }

        global $user;

        $sql = 'SELECT *
            FROM _artists_video
            WHERE video_a = ?
            ORDER BY video_added DESC';
        $result = sql_rowset(sql_filter($sql, $this->data['ub']));

        foreach ($result as $i => $row) {
            if (!$i) {
                _style('video');
            }

            _style(
                'video.row',
                array(
                    'NAME' => $row['video_name'],
                    'CODE' => $row['video_code'],
                    'TIME' => $user->format_date($row['video_added'])
                )
            );
        }

        return;
    }

    public function getTitle($default = '') {
        return (!empty($this->title)) ? $this->title : $default;
    }

    public function getTemplate($default = '') {
        return (!empty($this->template)) ? $this->template : $default;
    }

    private function make($flag = false) {
        $this->make = $flag;
    }

    public function ajax() {
        return $this->ajx;
    }

    public function run() {
        if ($this->setup()) {
            $this->panel();

            $this->title = $this->data['name'];
            $this->template = 'artists.view';
        } else {
            $this->list();
            $this->latest();
        }

        return;
    }

    public function v($property, $value = -5000) {
        if ($value != -5000) {
            $this->data[$property] = $value;
            return $value;
        }

        if (!isset($this->data[$property])) {
            return false;
        }

        return $this->data[$property];
    }

    public function getData() {
        $sql = 'SELECT *
            FROM _artists
            ORDER BY name ASC';
        $this->adata = sql_rowset($sql, 'ub');

        return;
    }

    public function setup() {
        global $user;

        $_a = request_var('id', '');
        if (!empty($_a)) {
            if (preg_match('/([0-9a-zA-Z]+)/', $_a)) {
                $sql = 'SELECT *
                    FROM _artists
                    WHERE subdomain = ?
                    LIMIT 1';
                if ($this->data = sql_fieldrow(sql_filter($sql, strtolower($_a)))) {
                    return true;
                }
            }

            fatal_error();
        }

        return false;
    }

    public function artistAuth() {
        global $user;

        $this->auth['user'] = ($user->is('member')) ? true : false;
        $this->auth['adm'] = ($user->is('founder')) ? true : false;
        $this->auth['mod'] = ($this->auth['adm']) ? true : false;
        $this->auth['smod'] = false;
        $this->auth['fav'] = false;
        $this->auth['post'] = true;

        if (!$this->auth['user'] || $this->data['layout'] == 'website') {
            return;
        }

        if ($user->is('artist')) {
            $sql = 'SELECT u.user_id
                FROM _members u, _artists_auth a, _artists b
                WHERE a.ub = ?
                    AND a.user_id = ?
                    AND a.user_id = u.user_id
                    AND b.ub = a.ub
                    AND b.ub = a.ub';
            if (sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
                $this->auth['smod'] = $this->auth['mod'] = true;
                return;
            }
        }

        $sql = 'SELECT aa.*
            FROM _artists_access aa
            LEFT JOIN _artists a ON aa.ub = a.ub
            RIGHT JOIN _members m ON aa.user_id = m.user_id
            WHERE aa.ub = ?
                AND aa.user_id = ?';
        if ($row = sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
            $current_time = time();

            if (!$row['ban_time'] || $row['ban_time'] > $current_time) {
                if ($row['ban_access']) {
                    $message = sprintf(lang('ub_banned_until'), $user->format_date($row['ban_time']));
                    $message = (!$row['ban_time']) ? 'UB_BANNED' : $message;

                    trigger_error($message);
                } else {
                    $this->auth['post'] = false;
                    $this->auth['post_until'] = ($row['ban_time']) ? $row['ban_time'] : 0;
                }
            } else {
                $sql = 'DELETE FROM _artists_access
                    WHERE user_id = ?
                        AND ub = ?';
                sql_query(sql_filter($sql, $user->d('user_id'), $this->data['ub']));
            }
        }

        if ($user->d('user_type') != USER_NORMAL) {
            $sql = 'SELECT f.*
                FROM _artists b, _artists_fav f, _members m
                WHERE b.ub = ?
                    AND b.ub = f.ub
                    AND f.user_id = ?
                    AND f.user_id = m.user_id';
            if (sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
                $this->auth['fav'] = true;
            }
        }

        return;
    }

    public function callLayout() {
        $layout = 'artist' . ucfirst($this->data['layout']);
        if (!method_exists($this, $layout)) {
            redirect(s_link('a'));
        }

        return $this->$layout();
    }

    public function stats($id) {
        if (is_array($id)) {
            $all_stats = w();
            foreach ($id as $item) {
                $all_stats[$item] = $this->stats($item);
            }
            return $all_stats;
        }

        $t_ub = $s_ub = 0;
        foreach ($this->adata as $data) {
            if ($data[$id] > $s_ub) {
                $s_ub = $data[$id];
                $t_ub = $data['ub'];
            }
        }

        return ($t_ub) ? $this->adata[$t_ub] : false;
    }

    public function lastRecords() {
        global $user, $config, $cache;

        if (!$a_records = $cache->get('a_records')) {
            $sql = 'SELECT ub, subdomain, name, genre
                FROM _artists
                ORDER BY datetime DESC
                LIMIT 3';
            $a_records = sql_rowset($sql, 'ub');

            $cache->save('a_records', $a_records);
        }

        if (!$ai_records = $cache->get('ai_records')) {
            $ai_records = w();

            foreach ($a_records as $row) {
                $sql = 'SELECT ub, images
                    FROM _artists_images
                    WHERE ub = ?
                    ORDER BY image';
                $result = sql_rowset(sql_filter($sql, $row['ub']));

                foreach ($result as $row) {
                    $ai_records[$row['ub']][] = $row2['image'];
                }
            }

            $cache->save('ai_records', $ai_records);
        }

        _style('a_records');

        foreach ($a_records as $row) {
            _style(
                'a_records.item',
                array(
                    'URL'   => s_link('a', $row['subdomain']),
                    'NAME'  => $row['name'],
                    'GENRE' => $row['genre']
                )
            );

            if (isset($ai_records[$row['ub']])) {
                $ai_select = array_rand($ai_records[$row['ub']]);

                $image  = $config['artists_url'];
                $image .= $row['ub'] . '/thumbnails/' . $ai_records[$row['ub']][$ai_select] . '.jpg';

                _style(
                    'a_records.item.image',
                    array(
                        'IMAGE' => $image
                    )
                );
            }
        }
    }

    public function latestMusic() {
        $sql = 'SELECT d.id, d.title, a.subdomain, a.name
            FROM _dl d, _artists a
            WHERE d.ud = 1
                AND d.ub = a.ub
            ORDER BY d.date DESC
            LIMIT 0, 10';
        $result = sql_rowset($sql);

        foreach ($result as $row) {
            _style(
                'downloads',
                array(
                    'URL' => s_link('a', $row['subdomain'], 'downloads', $row['id']),
                    'A'   => $row['name'],
                    'T'   => $row['title']
                )
            );
        }

        return true;
    }

    public function topStats() {
        global $user, $config;

        _style('a_stats');

        $all_data = $this->stats(array('datetime', 'views', 'votes', 'posts'));

        if ($all_data['datetime']) {
            global $cache;

            $a_random = w();
            if (!$a_random = $cache->get('a_last_images')) {
                $sql = 'SELECT *
                    FROM _artists_images
                    WHERE ub = ?
                    ORDER BY image';
                if ($a_random = sql_rowset(sql_filter($sql, $all_data['datetime']['ub']), false, 'image')) {
                    $cache->save('a_last_images', $a_random);
                }
            }

            if (sizeof($a_random)) {
                $selected_image = array_rand($a_random);
                if (isset($a_random[$selected_image])) {
                    $image  = $config['artists_url'];
                    $image .= $all_data['datetime']['ub'] . '/thumbnails/' . $a_random[$selected_image] . '.jpg';

                    _style(
                        'a_stats.gallery',
                        array(
                            'IMAGE' => $image,
                            'URL'   => s_link('a', $all_data['datetime']['subdomain'])
                        )
                    );
                }
            }
        }

        foreach ($all_data as $id => $data) {
            if ($data['name'] != '') {
                _style(
                    'a_stats.item',
                    array(
                        'LANG'     => lang('ub_top_' . strtoupper($id)),
                        'URL'      => s_link('a', $data['subdomain']),
                        'NAME'     => $data['name'],
                        'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
                        'GENRE'    => $data['genre']
                    )
                );
            }
        }

        return;
    }

    public function thumbnails() {
        global $cache, $config;

        if (!$a_recent = $cache->get('a_recent')) {
            $sql = 'SELECT ub
                FROM _artists
                ORDER BY datetime DESC
                LIMIT 10';
            $result = sql_rowset($sql);

            $a_recent = w();
            foreach ($result as $row) {
                $a_recent[$row['ub']] = 1;
            }

            $cache->save('a_recent', $a_recent);
        }

        $a_ary = w();
        for ($i = 0; $i < 3; $i++) {
            $_a = array_rand($a_recent);
            if (!$this->adata[$_a]['images'] || isset($a_ary[$_a])) {
                $i--;
                continue;
            }
            $a_ary[$_a] = $this->adata[$_a];
        }

        for ($i = 0; $i < 2; $i++) {
            $_a = array_rand($this->adata);
            if (!$this->adata[$_a]['images'] || isset($a_ary[$_a])) {
                $i--;
                continue;
            }
            $a_ary[$_a] = $this->adata[$_a];
        }

        if (sizeof($a_ary)) {
            $sql = 'SELECT *
                FROM _artists_images
                WHERE ub IN (??)
                ORDER BY RAND()';
            $result = sql_rowset(sql_filter($sql, implode(',', array_keys($a_ary))));

            $random_images = w();
            foreach ($result as $row) {
                if (!isset($random_images[$row['ub']])) {
                    $random_images[$row['ub']] = $row['image'];
                }
            }

            _style('thumbnails');

            foreach ($a_ary as $ub => $data) {
                _style(
                    'thumbnails.item',
                    array(
                        'NAME'     => $data['name'],
                        'IMAGE'    => $config['artists_url'] . $ub . '/thumbnails/' . $random_images[$ub] . '.jpg',
                        'URL'      => s_link('a', $data['subdomain']),
                        'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
                        'GENRE'    => $data['genre']
                    )
                );
            }
        }

        return;
    }

    public function getImages($mainframe = false, $ub = 0, $rand = false) {
        if ($this->images) {
            return;
        }

        global $config;

        if ($mainframe) {
            $sql = 'SELECT i.*
                FROM _artists_images i, _artists a
                WHERE i.ub = a.ub
                ORDER BY i.image';
        } else {
            if ($ub) {
                $sql = 'SELECT i.*
                    FROM _artists_images i
                    LEFT JOIN _artists a ON a.ub = i.ub
                    WHERE i.ub = ?
                    ORDER BY ' . (($rand) ? 'RAND() LIMIT 1' : 'image');
                $sql = sql_filter($sql, $ub);
            }
        }

        if ($ub && !$mainframe) {
            if ($row = sql_fieldrow($sql)) {
                $this->images[$row['ub']][$row['image']] = array(
                    'path' => $config['artists_url'] . $row['ub'] . '/gallery/' . $row['image'] . '.jpg',
                    'image' => $row['image'],
                    'allow_dl' => $row['allow_dl']
                );
            }

            return $row['image'];
        }

        $result = sql_rowset($sql);

        foreach ($result as $row) {
            $this->images[$row['ub']][$row['image']] = array(
                'path' => $config['artists_url'] . $row['ub'] . '/gallery/' . $row['image'] . '.jpg',
                'image' => $row['image'],
                'allow_dl' => $row['allow_dl']
            );
        }

        return;
    }

    public function downloads() {
        global $config;

        $sql = 'SELECT *
            FROM _dl';
        $this->ud_song = sql_rowset($sql, 'ud', false, true);

        _style('downloads');

        $ud_in_ary = w();
        foreach ($this->ud_song as $ud => $dl_data) {
            $dl_size = sizeof($dl_data);
            if (!$dl_size) {
                continue;
            }

            $ud_size = ($dl_size > $config['main_dl']) ? $config['main_dl'] : $dl_size;
            $download_type = $this->downloadType($ud);

            _style(
                'downloads.panel',
                array(
                    'UD'          => $download_type['lang'],
                    'TOTAL_COUNT' => $dl_size
                )
            );

            for ($i = 0; $i < $ud_size; $i++) {
                $ud_rand = array_rand($dl_data);

                if (isset($ud_in_ary[$ud][$ud_rand])) {
                    $i--;
                    continue;
                }

                $ud_in_ary[$ud][$ud_rand] = true;

                $url = s_link(
                    'a',
                    $this->adata[$dl_data[$ud_rand]['ub']]['subdomain'],
                    'downloads',
                    $dl_data[$ud_rand]['id']
                );

                _style(
                    'downloads.panel.item',
                    array(
                        'UB'    => $this->adata[$dl_data[$ud_rand]['ub']]['name'],
                        'TITLE' => $dl_data[$ud_rand]['title'],
                        'URL'   => $url
                    )
                );
            }
        }

        return;
    }

    public function list() {
        global $user, $config;

        $sql = 'SELECT *
            FROM _artists
            ORDER BY local DESC, name ASC';
        $result = sql_rowset($sql);

        $alphabet = w();
        foreach ($result as $row) {
            $this->adata[$row['local']][$row['ub']] = $row;

            $alpha_id = strtolower($row['name']);
            $alpha_id = $alpha_id{0};
            if (!isset($alphabet[$alpha_id])) {
                if (is_numb($alpha_id)) {
                    $alpha_id = '#';
                }
                $alphabet[$alpha_id] = true;
            }
        }

        $selected_char = '';
        $s_alphabet = request_var('alphabet', 0);

        if ($s_alphabet) {
            $selected_char = chr(octdec($s_alphabet));
            if (!preg_match('/([\#a-z])/', $selected_char)) {
                redirect(s_link('a'));
            }
        }

        if ($s_alphabet) {
            $sql_where = (($selected_char == '#') ? "name NOT RLIKE '^[a-z]'" : '';
            $sql_where = $sql_where ?: sql_filter('name LIKE ?', $selected_char . '%');
        } else {
            $sql_where = 'images > 1';
        }

        $sql_order = (!$s_alphabet) ? 'RAND() LIMIT 12' : 'name';

        $sql = 'SELECT *
            FROM _artists
            WHERE ' . $sql_where . '
            ORDER BY ' . $sql_order;
        if (!$selected_artists = sql_rowset($sql, 'ub')) {
            redirect(s_link('a'));
        }

        $sql = 'SELECT *
            FROM _artists_images
            WHERE ub IN (??)
            ORDER BY RAND()';
        $result = sql_rowset(sql_filter($sql, implode(',', array_keys($selected_artists))));

        $random_images = w();
        foreach ($result as $row) {
            if (!isset($random_images[$row['ub']])) {
                $random_images[$row['ub']] = $row['image'];
            }
        }

        _style('search_match');

        if (!$s_alphabet) {
            _style('search_match.ajx');
            $this->ajx = false;
        }

        foreach ($selected_artists as $ub => $data) {
            $image = '';

            if (isset($random_images[$ub])) {
                $image = $ub . '/thumbnails/' . $random_images[$ub] . '.jpg';
            }

            _style(
                'row',
                array(
                    'NAME'     => $data['name'],
                    'IMAGE'    => $config['artists_url'] . $image,
                    'URL'      => s_link('a', $data['subdomain']),
                    'LOCATION' => ($data['local']) ? 'Guatemala' : $data['location'],
                    'GENRE'    => $data['genre']
                )
            );
        }

        ksort($alphabet);

        foreach ($alphabet as $key => $null) {
            _style(
                'alphabet_item',
                array(
                    'CHAR' => strtoupper($key),
                    'URL'  => s_link('a', '_' . decoct(ord($key)))
                )
            );
        }

        v_style(
            array(
                'TOTAL_A'         => $config['max_artists'],
                'SELECTED_LETTER' => ($selected_char) ? strtoupper($selected_char) : ''
            )
        );

        return;
    }

    public function panel() {
        global $user, $config, $template;

        $this->data['layout'] = request_var('layout', '');
        $this->_auth();

        if (!$this->data['layout']) {
            $this->data['layout'] = 'main';
        }

        switch ($this->data['layout']) {
            case 'website':
            case 'favorites':
            case 'vote':
                $this->callLayout();
                break;
            default:
                $this->make(true);

                /*
                Build nav menu
                */
                /*$s_layout = w();
                $s_layout['a']['_01'] = true;
                $s_layout['a']['_02'] = ($this->data['bio'] != '') ? true : false;
                $s_layout['a']['_04'] = ($this->data['images'] > 1) ? true : false;
                $s_layout['a']['_06'] = ($this->data['lirics'] > 0) ? true : false;
                $s_layout['a']['_09'] = ($this->data['layout'] == 9) ? true : false;
                $s_layout['a']['_12'] = ($this->data['layout'] == 12) ? true : false;
                $s_layout['a']['_18'] = ($this->data['a_video'] > 0) ? true : false;
                */

                $available = w();
                foreach ($this->layout as $i => $row) {
                    if ($this->data['layout'] == $row['tpl']) {
                        $this->data['template'] = $row['tpl'];
                    }

                    if ($this->{'_' . $row['tpl']}()) {
                        $available[$row['tpl']] = true;

                        _style(
                            'nav',
                            array(
                                'LANG' => lang($row['text'])
                            )
                        );

                        if ($this->data['layout'] == $row['tpl']) {
                            _style('nav.strong');
                        } else {
                            $tpl = ($row['tpl'] == 'main') ? '' : $row['tpl'];

                            _style(
                                'nav.a',
                                array(
                                    'URL' => s_link('a', $this->data['subdomain'], $tpl)
                                )
                            );
                        }
                    }
                }

                if (!isset($available[$this->data['layout']])) {
                    redirect(s_link('a', $this->data['subdomain']));
                }

                $this->_make();

                //_pre($available, true);

                /*foreach ($this->layout as $item => $data) {
                    $s_layout['x'][$item] = $data['code'];

                    if ($data['text'] == '') {
                        $s_layout['e'][$item] = $data['code'];
                    }

                    if (isset($s_layout['a'][$item]) && $s_layout['a'][$item] && $data['tpl'] != '') {
                        $s_layout['s'][$data['code']] = $data;
                    }

                    if (($this->data['layout'] == $data['code']) && $data['tpl'] != '') {
                        $this->data['template'] = $data['tpl'];
                    }
                }

                if (!in_array($this->data['layout'], $s_layout['x']) ||
                (!isset($s_layout['s'][$this->data['layout']]) && !in_array($this->data['layout'], $s_layout['e']))) {
                    redirect(s_link('a', $this->data['subdomain']));
                }*/

                //
                // Call selected layout
                //
                $this->call_layout();

                //
                // Build nav
                //
                /*foreach ($s_layout['s'] as $data) {
                    _style('nav', array(
                        'LANG' => lang($data['text']))
                    );

                    if ($this->data['layout'] == $data['code']) {
                        _style('nav.strong');
                        continue;
                    }

                    if ($data['code'] === 1) $data['code'] = '';

                    _style('nav.a', array(
                        'URL' => s_link('a', $this->data['subdomain'], $data['code']))
                    );
                }*/

                //
                // Update stats
                //
                if (!$this->auth['mod']) {
                    $update_views = false;
                    $current_time = time();
                    $current_month = date('Ym', $current_time);

                    if ($this->auth['user']) {
                        $sql_viewers = array(
                            'datetime' => (int) $current_time,
                            'user_ip' => $user->ip
                        );

                        $sql_viewers2 = array(
                            'ub' => (int) $this->data['ub'],
                            'user_id' => (int) $user->d('user_id')
                        );

                        $sql = 'UPDATE _artists_viewers SET ??
                            WHERE ??';
                        $sql = sql_filter($sql, sql_build('UPDATE', $sql_viewers), sql_build('SELECT', $sql_viewers2));

                        sql_query($sql);

                        if (!sql_affectedrows()) {
                            $update_views = true;
                            $sql_stats = array('ub' => (int) $this->data['ub'], 'date' => (int) $current_month);

                            sql_insert('artists_viewers', $sql_viewers + $sql_viewers2);

                            $sql = 'UPDATE _artists_stats SET members = members + 1
                                WHERE ??';
                            sql_query(sql_filter($sql, sql_build('SELECT', $sql_stats)));

                            if (!sql_affectedrows()) {
                                $sql_insert = array(
                                    'members' => 1,
                                    'guests' => 0
                                );
                                sql_insert('artists_stats', $sql_stats + $sql_insert);
                            }

                            $sql = 'SELECT user_id
                                FROM _artists_viewers
                                WHERE ub = ?
                                ORDER BY datetime DESC
                                LIMIT 10, 1';
                            if ($row = sql_fieldrow(sql_filter($sql, $this->data['ub']))) {
                                $sql = 'DELETE FROM _artists_viewers
                                    WHERE ub = ?
                                        AND user_id = ?';
                                sql_query(sql_filter($sql, $this->data['ub'], $row['user_id']));
                            }
                        }
                    }

                    $_ps = request_var('ps', 0);

                    if ((($this->auth['user'] && $update_views) || (!$this->auth['user'] && $this->data['layout'] == 1)) && !$_ps) {
                        $sql = 'UPDATE _artists SET views = views + 1
                            WHERE ub = ?';
                        sql_query(sql_filter($sql, $this->data['ub']));
                        $this->data['views']++;

                        if ((!$this->auth['user'] && $this->data['layout'] == 1) && !$_ps) {
                            $sql_stats = array(
                                'ub' => (int) $this->data['ub'],
                                'date' => (int) $current_month
                            );
                            $sql = 'UPDATE _artists_stats SET guests = guests + 1
                                WHERE ??';
                            sql_query(sql_filter($sql, sql_build('SELECT', $sql_stats)));

                            if (!sql_affectedrows()) {
                                $sql_insert = array(
                                    'members' => 0,
                                    'guests' => 1
                                );
                                sql_insert('artists_stats', $sql_stats + $sql_insert);
                            }
                        }
                    }
                }

                //
                // Own events
                //
                $timezone = $config['board_timezone'] * 3600;

                list($d, $m, $y) = explode(' ', gmdate('j n Y', time() + $user->timezone + $user->dst));
                $midnight = gmmktime(0, 0, 0, $m, $d, $y) - $user->timezone - $user->dst;

                $g = getdate($midnight);
                $week = mktime(0, 0, 0, $m, ($d + (7 - ($g['wday'] - 1)) - (!$g['wday'] ? 7 : 0)), $y) - $timezone;

                $sql = 'SELECT *
                    FROM _events e, _artists_events ae
                    WHERE ae.a_artist = ?
                        AND ae.a_event = e.id
                    ORDER BY e.date';
                $result = sql_rowset(sql_filter($sql, $this->data['ub']));

                $events = w();
                foreach ($result as $row) {
                    if ($row['date'] >= $midnight) {
                        if ($row['date'] >= $midnight && $row['date'] < $midnight + 86400) {
                            $events['is_today'][] = $row;
                        } elseif ($row['date'] >= $midnight + 86400 && $row['date'] < $midnight + (86400 * 2)) {
                            $events['is_tomorrow'][] = $row;
                        } elseif ($row['date'] >= $midnight + (86400 * 2) && $row['date'] < $week) {
                            $events['is_week'][] = $row;
                        } else {
                            $events['is_future'][] = $row;
                        }
                    } elseif ($row['images']) {
                        $events['is_gallery'][] = $row;
                    }
                }

                if (isset($events['is_gallery']) && sizeof($events['is_gallery'])) {
                    $gallery = $events['is_gallery'];
                    @krsort($gallery);

                    _style('events_gallery');
                    foreach ($gallery as $row) {
                        _style(
                            'events_gallery.item',
                            array(
                                'URL'      => s_link('events', $row['event_alias']),
                                'TITLE'    => $row['title'],
                                'DATETIME' => $user->format_date($row['date'], lang('date_format'))
                            )
                        );
                    }

                    unset($events['is_gallery']);
                }

                if (sizeof($events)) {
                    _style('events_future');

                    foreach ($events as $is_date => $data) {
                        _style(
                            'events_future.set',
                            array(
                                'L_TITLE' => lang('ue_' . $is_date)
                            )
                        );

                        foreach ($data as $item) {
                            _style(
                                'events_future.set.row',
                                array(
                                    'ITEM_ID'   => $item['id'],
                                    'TITLE'     => $item['title'],
                                    'DATE'      => $user->format_date($item['date']),
                                    'THUMBNAIL' => $config['events_url'] . 'future/thumbnails/' . $item['id'] . '.jpg',
                                    'SRC'       => $config['events_url'] . 'future/' . $item['id'] . '.jpg'
                                )
                            );
                        }
                    }
                }

                //
                // Poll
                //
                $user_voted = false;
                if ($this->auth['user'] && !$this->auth['mod']) {
                    $sql = 'SELECT *
                        FROM _artists_voters
                        WHERE ub = ?
                            AND user_id = ?';
                    if (sql_fieldrow(sql_filter($sql, $this->data['ub'], $user->d('user_id')))) {
                        $user_voted = true;
                    }
                }

                _style('ub_poll');

                if ($this->auth['mod'] || !$this->auth['user'] || $user_voted) {
                    $sql = 'SELECT option_id, vote_result
                        FROM _artists_votes
                        WHERE ub = ?
                        ORDER BY option_id';
                    $results = sql_rowset(sql_filter($sql, $this->data['ub']), 'option_id', 'vote_result');

                    _style('ub_poll.results');

                    foreach ($this->voting['ub'] as $item) {
                        $vote_result = (isset($results[$item])) ? intval($results[$item]) : 0;
                        $vote_percent = ($this->data['votes'] > 0) ? $vote_result / $this->data['votes'] : 0;

                        _style(
                            'ub_poll.results.item',
                            array(
                                'CAPTION' => lang('ub_vc' . $item),
                                'RESULT'  => $vote_result,
                                'PERCENT' => sprintf("%.1d", ($vote_percent * 100))
                            )
                        );
                    }
                } else {
                    _style(
                        'ub_poll.options',
                        array(
                            'S_VOTE_ACTION' => s_link('a', $this->data['subdomain'], 'vote')
                        )
                    );

                    foreach ($this->voting['ub'] as $item) {
                        _style(
                            'ub_poll.options.item',
                            array(
                                'ID'      => $item,
                                'CAPTION' => lang('ub_vc' . $item)
                            )
                        );
                    }
                }

                //
                // Downloads
                //
                if ($this->data['um'] || $this->data['uv']) {
                    $sql = 'SELECT *
                        FROM _dl
                        WHERE ub = ?
                        ORDER BY ud, title';
                    $this->ud_song = sql_rowset(sql_filter($sql, $this->data['ub']), 'ud', false, true);

                    foreach ($this->ud_song as $key => $data) {
                        $download_type = $this->downloadType($key);
                        _style('ud_block', array('LANG' => $download_type['lang']));

                        foreach ($data as $song) {
                            _style(
                                'ud_block.item',
                                array(
                                    'TITLE' => $song['title']
                                )
                            );

                            if (isset($this->dl_data['id']) && ($song['id'] == $this->dl_data['id'])) {
                                _style('ud_block.item.strong');
                                continue;
                            }

                            _style(
                                'ud_block.item.a',
                                array(
                                    'URL' => s_link('a', $this->data['subdomain'], 'downloads', $song['id'])
                                )
                            );
                        }
                    }
                }

                //
                // Fan count
                //
                $sql = 'SELECT COUNT(user_id) AS fan_count
                    FROM _artists_fav
                    WHERE ub = ?
                    ORDER BY joined DESC';
                $fan_count = sql_field(sql_filter($sql, $this->data['ub']), 'fan_count', 0);

                //
                // Make fans
                //
                if (!$this->auth['mod'] && !$this->auth['smod']) {
                    _style(
                        'make_fans',
                        array(
                            'FAV_URL' => s_link('a', $this->data['subdomain'], 'favorites'),
                            'FAV_LANG' => ($this->auth['fav']) ? '' : lang('ub_fav_add')
                        )
                    );
                }

                if ($this->data['local']) {
                    $location = (($this->data['location'] != '') ? $this->data['location'] . ', ' : '') . 'Guatemala';
                } else {
                    $location = $this->data['location'];
                }

                //
                // Set template
                //
                v_style(
                    array(
                        'INACTIVE' => !$this->data['a_active'],
                        'UNAME'    => $this->data['name'],
                        'GENRE'    => $this->data['genre'],
                        'POSTS'    => number_format($this->data['posts']),
                        'VOTES'    => number_format($this->data['votes']),
                        'FANS'     => $fan_count,
                        'L_FANS'   => ($fan_count == 1) ? lang('fan') : lang('fans'),
                        'LOCATION' => $location
                    )
                );

                $template->set_filenames(
                    array(
                        'a_body' => 'artists.' . $this->data['template'] . '.htm'
                    )
                );
                $template->assign_var_from_handle('UB_BODY', 'a_body');
                break;
        }

        return;
    }

    public function latest() {
        return;
    }

    public function sidebar() {
        global $config;

        $sql = 'SELECT *
            FROM _artists a, _artists_images i
            WHERE a.ub = i.ub
            ORDER BY RAND()
            LIMIT 1';
        if ($row = sql_fieldrow($sql)) {
            _style(
                'random_a',
                array(
                    'NAME'  => $row['name'],
                    'IMAGE' => $config['artists_url'] . $row['ub'] . '/thumbnails/' . $row['image'] . '.jpg',
                    'URL'   => s_link('a', $row['subdomain']),
                    'GENRE' => $row['genre']
                )
            );
        }

        return;
    }
}
