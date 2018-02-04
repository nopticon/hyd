<?php namespace App;

class Win {
    private $object;
    private $title;
    private $template;

    private $default_title = 'WIN';
    private $default_view = 'win';

    public function __construct() {
        return;
    }

    public function getTitle() {
        return !empty($this->title) ? $this->title : $this->default_title;
    }

    public function getTemplate() {
        return !empty($this->template) ? $this->template : $this->default_view;
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

            _style('win.row', []);
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
