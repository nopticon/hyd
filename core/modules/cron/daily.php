<?php
namespace App;

$d = getdate();
$start_1 = mktime(0, 0, 0, $d['mon'], ($d['mday'] - 7), $d['year']);
$start_2 = mktime(0, 0, 0, $d['mon'], ($d['mday'] - 14), $d['year']);

//
// Banners
$banner_end = mktime(23, 59, 0, $d['mon'], $d['mday'], $d['year']);

$sql = 'SELECT *
    FROM _monetize
    WHERE monetize_end > ' . (int) $_end . '
    ORDER BY monetize_end';
$deleted = sql_rowset(sql_filter($sql, $_end), false, 'monetize_id');

if (count($deleted)) {
    $sql = 'DELETE FROM _monetize
        WHERE monetize_id IN (??)';
    sql_query(sql_filter($sql, implode(',', $deleted)));

    $cache->delete('monetize');
}

//
// Optimize
set_config('site_disable', 1);

$sql = 'SHOW TABLES';
$result = sql_rowset($sql, false, false, false, MYSQL_NUM);

foreach ($result as $row) {
    $tables[] = $row[0];
}

$sql = 'OPTIMIZE TABLE ' . implode(', ', $tables);
sql_query($sql);

set_config('site_disable', 0);

_pre('Done.', true);
