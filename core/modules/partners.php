<?php namespace App;

class Partners {
    private $default_title = 'PARTNERS';
    private $default_view  = 'partners';

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
        $sql = 'SELECT *
            FROM _partners
            ORDER BY partner_order';
        $partners = sql_rowset($sql);

        foreach ($partners as $i => $row) {
            if (!$i) {
                _style('partners');
            }

            _style('partners.row', [
                'NAME'  => $row['partner_name'],
                'IMAGE' => $row['partner_image'],
                'URL'   => config('assets_url') . '/style/sites/' . $row['partner_url']
            ]);
        }

        return;
    }
}
