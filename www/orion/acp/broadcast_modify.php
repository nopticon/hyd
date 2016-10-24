<?php
namespace App;

class __broadcast_modify extends mac {
    public function __construct() {
        parent::__construct();

        $this->auth('founder');
    }

    public function home() {
        global $user, $cache;

        $ftp = new ftp();

        if (!$ftp->ftp_connect(config('broadcast_host'))) {
            _pre('Can not connect', true);
        }

        if (!$ftp->ftp_login(config('broadcast_username'), config('broadcast_password'))) {
            $ftp->ftp_quit();
            _pre('Can not login', true);
        }

        $cds_file = ROOT . 'interfase/cds/schedule_playlist.txt';

        // Submit
        if (_button()) {
            $hours = request_var('hours', array('' => ''));

            $build = '';
            foreach ($hours as $hour => $play) {
                $build .= ((!empty($build)) ? nr(1) : '') . trim($hour) . ':' . trim($play);
            }

            if ($fp = @fopen($cds_file, 'w')) {
                @flock($fp, LOCK_EX);
                fputs($fp, $build);
                @flock($fp, LOCK_UN);
                fclose($fp);

                _chmod($cds_file, config('mask'));

                if ($ftp->ftp_put('/Schedule/schedule_playlist.txt', $cds_file)) {
                    echo '<h1>El archivo fue procesado correctamente.</h1>';
                } else {
                    echo '<h1>Error al procesar, intenta nuevamente.</h1>';
                }
            } else {
                echo 'Error de escritura en archivo local.';
            }

            echo '<br />';
        }

        if (!@file_exists($cds_file)) {
            fatal_error();
        }

        $cds = @file($cds_file);

        $filelist = $ftp->ftp_nlist('/Schedule');
        echo '<pre>';
        print_r($filelist);
        echo '</pre>';

        foreach ($cds as $item) {
            $e_item = array_map('trim', explode(':', $item));

            if (!empty($e_item[0])) {
                $format = '%s <input type="text" name="hours[%s]" value="%s" size="100" %s /><br />%s';
                $oclock = oclock($e_item[0]) ? 'class="highlight"' : '';

                echo sprintf(sumhour($e_item[0]), $e_item[0], $e_item[1], $oclock, nr();
            }
        }

        $ftp->ftp_quit();

        return true;
    }
}
