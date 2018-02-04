<?php namespace App;

class Tos {
    private $default_title = 'PRIVACY_POLICY';
    private $default_view  = 'tos';

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
        return;
    }
}
