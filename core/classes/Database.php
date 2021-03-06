<?php namespace App;

class DatabaseCommon {
    protected $connect;
    protected $result;
    protected $history;
    protected $row;
    protected $rowset;
    protected $queries;
    protected $noerror;
    protected $login = array();

    final protected function login($d) {
        if ($d === false) {
            $d = decode_ht('.htda');
        }

        foreach (w('server login secret database') as $i => $k) {
            $this->login[$k] = _decode($d[$i]);
        }
        unset($d);

        return;
    }
}

function prefix($prefix, $arr) {
    $prefix = ($prefix != '') ? $prefix . '_' : '';

    $a = w();
    foreach ($arr as $k => $v) {
        $a[$prefix . $k] = $v;
    }
    return $a;
}

// Database filter layer
// Idea from http://us.php.net/manual/en/function.sprintf.php#93156
function sql_filter() {
    if (!$args = func_get_args()) {
        return false;
    }

    $sql = array_shift($args);

    if (is_array($sql)) {
        $sql_ary = w();
        foreach ($sql as $row) {
            $sql_ary[] = sql_filter($row, $args);
        }

        return $sql_ary;
    }

    $count_args = count($args);
    $sql = str_replace('%', '[!]', $sql);

    if (!$count_args || $count_args < 1) {
        return str_replace('[!]', '%', $sql);
    }

    if ($count_args == 1 && is_array($args[0])) {
        $args = $args[0];
    }

    foreach ($args as $i => $arg) {
        $args[$i] = (strpos($arg, '/***/') !== false) ? $arg : sql_escape($arg);
    }

    foreach ($args as $i => $row) {
        if (strpos($row, 'addquotes') !== false) {
            $e_row = explode(',', $row);
            array_shift($e_row);

            foreach ($e_row as $j => $jr) {
                $e_row[$j] = "'" . $jr . "'";
            }

            $args[$i] = implode(',', $e_row);
        }
    }

    array_unshift($args, str_replace(w('?? ?'), w("%s '%s'"), $sql));

    // Conditional deletion of lines if input is zero
    if (strpos($args[0], '-- ') !== false) {
        $e_sql = explode(nr(), $args[0]);

        $matches = 0;
        foreach ($e_sql as $i => $row) {
            $e_sql[$i] = str_replace('-- ', '', $row);
            if (strpos($row, '%s')) {
                $matches++;
            }

            if (strpos($row, '-- ') !== false && !$args[$matches]) {
                unset($e_sql[$i], $args[$matches]);
            }
        }

        $args[0] = implode($e_sql);
    }

    return str_replace('[!]', '%', hook('sprintf', $args));
}

function sql_insert($table, $insert) {
    $sql = 'INSERT INTO _' . $table . sql_build('INSERT', $insert);
    return sql_query_nextid($sql);
}

function sql_query($sql) {
    global $db;

    return $db->query($sql);
}

function sql_transaction($status = 'begin') {
    global $db;

    return $db->transaction($status);
}

function sql_field($sql, $field, $def = false) {
    global $db;

    $db->query($sql);
    $response = $db->fetchfield($field);
    $db->freeresult();

    if ($response === false) {
        $response = $def;
    }

    if ($response !== false) {
        $response = $response;
    }

    return $response;
}

function sql_fieldrow($sql, $result_type = MYSQLI_ASSOC) {
    global $db;

    $db->query($sql);

    $response = false;
    if ($row = $db->fetchrow($result_type)) {
        $row['_numrows'] = $db->numrows();
        $response = $row;
    }
    $db->freeresult();

    return $response;
}

function sql_rowset($sql, $a = false, $b = false, $global = false, $type = MYSQLI_ASSOC) {
    global $db;

    $arr = w();

    $db->query($sql);
    if (!$data = $db->fetchrowset($type)) {
        return $arr;
    }

    foreach ($data as $row) {
        $data = ($b === false) ? $row : $row[$b];

        if ($a === false) {
            $arr[] = $data;
        } else {
            if ($global) {
                $arr[$row[$a]][] = $data;
            } else {
                $arr[$row[$a]] = $data;
            }
        }
    }
    $db->freeresult();

    return $arr;
}

function sql_truncate($table) {
    $sql = 'TRUNCATE TABLE ??';

    return sql_query(sql_filter($sql, $table));
}

function sql_total($table) {
    return sql_field("SHOW TABLE STATUS LIKE '" . $table . "'", 'Auto_increment', 0);
}

function sql_close() {
    global $db;

    if ($db->close()) {
        return true;
    }

    return false;
}

function sql_queries() {
    global $db;

    return $db->num_queries();
}

function sql_query_nextid($sql) {
    global $db;

    $db->query($sql);

    return $db->nextid();
}

function sql_nextid() {
    global $db;

    return $db->nextid();
}

function sql_affected($sql) {
    global $db;

    $db->query($sql);

    return $db->affectedrows();
}

function sql_affectedrows() {
    global $db;

    return $db->affectedrows();
}

function sql_escape($sql) {
    global $db;

    return $db->escape($sql);
}

function sql_build($cmd, $a, $b = false) {
    global $db;

    if (is_object($a)) {
        $_a = w();
        foreach ($a as $a_k => $a_v) {
            $_a[$a_k] = $a_v;
        }

        $a = $_a;
    }

    return '/***/' . $db->build($cmd, $a, $b);
}

function sql_cache($sql, $sid = '', $private = true) {
    global $db;

    return $db->cache($sql, $sid, $private);
}

function sql_cache_limit(&$arr, $start, $end = 0) {
    global $db;

    return $db->cache_limit($arr, $start, $end);
}

function sql_numrows(&$a) {
    $response = $a['_numrows'];
    unset($a['_numrows']);

    return $response;
}

function sql_history() {
    global $db;

    return $db->history();
}
