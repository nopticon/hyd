<?php
namespace App;

class Help {
    private $object;
    private $title;
    private $template;

    private $default_title = 'HELP';
    private $default_view = 'help';

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
        global $cache, $comments;

        $alias = request_var('alias', '');

        if (!empty($alias)) {
            $sql = 'SELECT *
                FROM _help_cat c, _help_modules m, _help_faq f
                WHERE c.help_module = m.module_id
                    AND f.help_id = c.help_id
                    AND m.module_name = ?
                ORDER BY f.faq_question_es';
            $module = sql_rowset(sql_filter($sql, $alias));

            foreach ($module as $i => $row) {
                if (!$i) _style('module', array('TITLE' => $row['help_es']));

                _style('module.row', array(
                    'QUESTION' => $row['faq_question_es'],
                    'ANSWER'   => $comments->parse_message($row['faq_answer_es']))
                );
            }
        }

        if (!$help = $cache->get('help')) {
            $sql = 'SELECT *
                FROM _help_cat c, _help_modules m
                WHERE c.help_module = m.module_id
                ORDER BY c.help_order';
            if ($help = sql_rowset($sql)) {
                $cache->save('help', $help);
            }
        }

        foreach ($help as $i => $row) {
            if (!$i) _style('categories');

            _style('categories.row', array(
                'URL'   => s_link('help', $row['module_name']),
                'TITLE' => $row['help_es'])
            );
        }

        return;
    }
}
