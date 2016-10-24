<?php
namespace App;

class Awards {
    private $default_title = 'AWARDS';
    private $default_view = 'awards';

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
        $sql = 'SELECT *
            FROM _awards_type
            ORDER BY type_order';
        $types = sql_rowset($sql);

        foreach ($types as $i => $row) {
            if (!$i) {
                _style('awards');
            }

            _style(
                'awards.row',
                array(
                    'NAME' => $row['type_name'],
                    'DESC' => $row['type_desc']
                )
            );
        }

        return;
    }
}
