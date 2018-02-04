<?php namespace App;

$artists = new Artists();
$artists->get_data();

$a_ary = [];
for ($i = 0; $i < 4; $i++) {
    $_a = array_rand($artists->adata);
    if (!$artists->adata[$_a]['images'] || isset($a_ary[$_a])) {
        $i--;
        continue;
    }
    $a_ary[$_a] = $artists->adata[$_a];
}

if (sizeof($a_ary)) {
    $sql = 'SELECT *
        FROM _artists_images
        WHERE ub IN (??)
        ORDER BY RAND()';
    $result = sql_rowset(sql_filter($sql, implode(',', array_keys($a_ary))));

    $random_images = [];
    foreach ($result as $row) {
        if (!isset($random_images[$row['ub']])) {
            $random_images[$row['ub']] = $row['image'];
        }
    }

    $response = '<table width="100%" class="t-collapse"><tr>';

    $format  = '<td class="%s pad6"><a href="%s">%s</a><br/ ><small>%s</small>';
    $format .= '<br /><div class="sep2-top"><a href="%s"><img class="box" ';
    $format .= 'src="/data/artists/%s" title="%s" /></a></div></td>';


    $i = 0;
    foreach ($a_ary as $ub => $data) {
        $url = s_link('a', $data['subdomain']);
        $class = ($i % 2) ? 'dark-color' : '';
        $location = $data['local'] ? 'Guatemala' : $data['location'];
        $image = config('artists_url') . $ub . '/thumbnails/' . $random_images[$ub] . '.jpg';

        $response .= sprintf($format, $class, $url, $data['name'], $location, $url, $image, $data['genre']);
        $i++;
    }

    $response .= '</tr></table>';
    echo rawurlencode($response);
}
