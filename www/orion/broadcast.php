<?php

require_once './interfase/common.php';
header('Content-type: text/html; charset=utf-8');

$user->init();
$user->setup();

$offset = request_var('offset', 0);
$category = request_var('category', '');

if (!empty($category)) {
    $sql = 'SELECT *
        FROM _terms
        WHERE slug = ?';
    if (!$term_category = sql_fieldrow(sql_filter($sql, $category))) {
        fatal_error();
    }

    $sql = 'SELECT *
        FROM _posts p, _postmeta pm, _terms t, _term_relationships rs
        WHERE t.slug = ?
            AND p.post_status = ?
            AND p.ID = pm.post_id
            AND pm.meta_key = ?
            AND rs.object_id = p.ID
            AND rs.term_taxonomy_id = t.term_id
        ORDER BY p.post_date DESC
        LIMIT ??, ??';
    $podcast = sql_rowset(sql_filter($sql, $category, 'publish', '_podPressMedia', $offset, 25));

    foreach ($podcast as $i => $row) {
        if (!$i) {
            _style('podcast');
        }

        $dmedia = array_key(unserialize($row['meta_value']), 0);

        $title = htmlentities(utf8_encode($row['post_title']), ENT_COMPAT, 'utf-8');
        $artist = htmlentities(utf8_encode($row['name']), ENT_COMPAT, 'utf-8');

        _style(
            'podcast.row',
            array(
                'MP3' => $dmedia['URI'],
                'OGG' => '',
                'TITLE' => $title,
                'ARTIST' => $artist,
                'COVER' => $row['slug'],
                'DURATION' => $dmedia['duration']
            )
        );
    }

    $str = utf8_encode($term_category['name']);
    $str = htmlentities($str, ENT_COMPAT, 'utf-8');

    page_layout($str, 'broadcast_play');
}

$programs = w('supernova invasionrock antifm metalebrios themetalroom');

foreach ($programs as $i => $row) {
    if (!$i) {
        _style('programs');
    }

    _style(
        'programs.row',
        array(
            'IMAGE' => $row,
            'URL' => s_link('broadcast', $row)
        )
    );
}

$sql = 'SELECT *
    FROM _posts p
    INNER JOIN _term_relationships tr ON tr.object_id = p.ID
    INNER JOIN _term_taxonomy tx ON tr.term_taxonomy_id = tx.term_taxonomy_id
    INNER JOIN _terms t ON tx.term_id = t.term_id
    WHERE post_status = ?
    ORDER BY post_date DESC
    LIMIT ??, ??';
$podcast = sql_rowset(sql_filter($sql, 'publish', $offset, 10));

foreach ($podcast as $i => $row) {
    if (!$i) {
        _style('podcast');
    }

    $title = htmlentities(utf8_encode($row['post_title']), ENT_COMPAT, 'utf-8');

    _style(
        'podcast.row',
        array(
            'POST_DATE' => $row['post_date'],
            'POST_URL' => s_link('broadcast', $row['slug']),
            'POST_CONTENT' => $row['post_content'],
            'POST_TITLE' => $title
        )
    );
}

page_layout('BROADCAST', 'broadcast');
