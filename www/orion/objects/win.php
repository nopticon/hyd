<?php
namespace App;

class Win {
    private $object;
    private $title;
    private $template;

    public function __construct() {
        return;
    }

    public function getTitle($default = '') {
        return !empty($this->title) ? $this->title : $default;
    }

    public function getTemplate($default = '') {
        return !empty($this->template) ? $this->template : $default;
    }

    public function run() {
        $alias = request_var('alias', '');

        if (empty($alias)) {
            return $this->elements();
        }

        $sql = 'SELECT *
            FROM _win
            WHERE win_alias = ?';
        if (!$this->object = sql_fieldrow(sql_filter($sql, $alias))) {
            fatal_error();
        }

        return $this->runObject();
    }

    private function elements() {
        $sql = 'SELECT *
            FROM _win
            ORDER BY win_date';
        $win = sql_rowset($sql);

        foreach ($win as $i => $row) {
            if (!$ui) {
                _style('win');
            }

            _style(
                'win.row',
                array()
            );
        }
        return;
    }

    private function runObject() {
        if (_button()) {
            return $this->store();
        }

        return;
    }

    private function store() {
        return;
    }
}
