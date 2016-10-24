<?php
namespace App;

class Media extends common {
    public $no = true;
    public $methods = array();

    public function home() {
        global $db, $nucleo;

        error_reporting(0);
        $v = $this->control->__(array('v' => array('default' => 0)));

        if (!$v['v']) {
            $sql = 'SELECT id
                FROM _dl
                WHERE ud = 1
                    AND dl_mp3 = 0
                ORDER BY id
                LIMIT 1';
            $v['v'] = $this->_field($sql, 'id');
        }

        $sql = 'SELECT d.*, a.name
            FROM _dl d, _artists a
            WHERE d.id = ' . (int) $v['v'] . '
                AND d.ub = a.ub';
        if ($songd = $this->_fieldrow($sql)) {
            $spaths = '/data/artists/' . $songd['ub'] . '/media/';
            $spath  = '/var/www/vhosts/rockrepublik.net/httpdocs' . $spaths;
            $songid = $songd['id'];
            $fwma   = $spath . $songid . '.wma';
            $fmp3   = $spath . $songid . '.mp3';

            $path_wma = '.' . $spaths . $songid . '.wma';
            $path_mp3 = '.' . $spaths . $songid . '.mp3';

            if (@file_exists($path_wma) && !@file_exists($path_mp3) && !$songd['dl_mp3']) {
                exec('ffmpeg -i ' . $fwma . ' -vn -ar 44100 -ac 2 -ab 64kb -f mp3 ' . $fmp3);

                // MP3 tags
                $tag_format = 'UTF-8';

                include_once(SROOT . 'core/getid3/getid3.php');
                $getID3 = new getID3;
                $getID3->setOption(array('encoding' => $tag_format));
                getid3_lib::IncludeDependency(GETID3_INCLUDEPATH.'write.php', __FILE__, true);

                $tagwriter = new getid3_writetags;
                $tagwriter->filename = getid3_lib::SafeStripSlashes($fmp3);
                $tagwriter->tagformats = array('id3v1');
                $tagwriter->overwrite_tags = true;
                $tagwriter->tag_encoding = $tag_format;
                $tagwriter->remove_other_tags = true;

                $tag_comment = 'Visita www.rockrepublik.net';

                $songd['album'] = (!empty($songd['album'])) ? $songd['album'] : 'Single';
                $songd['genre'] = (!empty($songd['genre'])) ? $songd['genre'] : 'Rock';

                $songd_f = array('title', 'name', 'album', 'genre');
                foreach ($songd_f as $songd_r) {
                    $songd[$songd_r] = getid3_lib::SafeStripSlashes(utf8_encode(html_entity_decode($songd[$songd_r])));
                }

                $tagwriter->tag_data = array(
                    'title'       => array($songd['title']),
                    'artist'      => array($songd['name']),
                    'album'       => array($songd['album']),
                    'year'        => array(getid3_lib::SafeStripSlashes($songd['year'])),
                    'genre'       => array($songd['genre']),
                    'comment'     => array(getid3_lib::SafeStripSlashes($tag_comment)),
                    'tracknumber' => array('')
                );
                $tagwriter->WriteTags();

                $sql = 'UPDATE _dl SET dl_mp3 = 1
                    WHERE id = ' . (int) $songd['id'];
                $db->sql_query($sql);

                $fp = @fopen('./conv.txt', 'a+');
                fwrite($fp, $fmp3 . "\n");
                fclose($fp);
            }

            if (!@file_exists('.' . $spaths . $songid . '.wma')) {
                $sql = 'UPDATE _dl SET dl_mp3 = 2
                    WHERE id = ' . (int) $songd['id'];
                $db->sql_query($sql);
            }
        }

        $sql = 'SELECT id
            FROM _dl
            WHERE ud = 1
                AND dl_mp3 = 0
            ORDER BY id
            LIMIT 1';
        if ($v_next = $this->_field($sql, 'id', 0)) {
            sleep(1);
            $nucleo->redirect($nucleo->link('conv', array('v' => $v_next)));
        } else {
            die('no_next');
        }

        $this->e('.');

        return;
    }
}
