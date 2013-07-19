<?php

/**
 * Конвертирует результат запроса в ассоциативный массив
 *
 * @param query_result $res
 * @return array
 */
function resourceToArray($res) {
    if ($res)
        while ($e = fetch($res)) {
            $r[] = $e;
        } else {
        $r = false;
    }
    return $r;
}

/**
 * Возвращает все строки, подходящие под условия
 *
 * @global boolean $_require_again
 * @global boolean $_dont_cashing
 * @global array $_cash
 * @param string $table
 * @param string $where
 * @param string $sort
 * @param string $limit
 * @param boolean $relations Использовать ли связанные таблицы
 * @return array
 */
function fetchAll($table, $where = false, $sort = false, $limit = false, $relations = true) {
    global $_require_again;
    global $_dont_cashing;
    global $_cash;
    global $_relations;
    $where = ($where) ? ' WHERE ' . $where : '';
    $sort  = ($sort) ? ' ORDER BY ' . $sort : '';
    $limit = ($limit) ? ' LIMIT ' . $limit : '';
    $q     = 'SELECT * FROM `' . $table . '`' . $where . $sort . $limit;
    if ($_require_again OR (empty($_cash['queries'][$q]))) {
        $_require_again = false;
        $res   = query($q);
        $res = $_cash['queries'][$q] = resourceToArray($res);
    } elseif ($_dont_cashing) {
        $_dont_cashing = false;
        $res   = query($q);
        $res =  resourceToArray($res);
    } else {
        $res =  $_cash['queries'][$q];
    }
    if(!$res) return array();
    if(isset($_relations[$table])){
        foreach($_relations[$table] as $rel){
            if($ct = $rel['has_many'] AND $relations){
                foreach($res as $k=>$v){
                    $res[$k][$ct] = fetchAll($ct, '`' . $rel['keys'][1] . '` = \'' . $res[$k][$rel['keys'][0]] . '\'');
                }
            } elseif($pt = $rel['belongs_to']){
                foreach($res as $k=>$v){
                    $res[$k][$rel['keys'][0]] = fetchRow($pt, '`' . $rel['keys'][1] . '` = \'' . $res[$k][$rel['keys'][0]] . '\'', false, false);
                }
            }
        }
    }

    return $res;
}

/**
 * Возвращает только одну - первую - строку, подходящую под условия
 *
 * @global boolean $_require_again
 * @global array $_cash
 * @param string $table
 * @param string $where
 * @param string $sort
 * @param boolean $relations Использовать ли связанные таблицы
 * @return array
 */
function fetchRow($table, $where = false, $sort = false, $relations = true) {
    $r = fetchAll($table, $where, $sort, 1, $relations);
    if($r){
        return $r[0];
    } else {
        return false;
    }
}

/**
 * Ищет строку по ключевому полю id
 *
 * @param string $table
 * @param integer $id
 * @return array
 */
function findRow($table, $id) {
    return fetchRow($table, '`id` = ' . intval($id));
}

/**
 * Вставляет данные в новую строку в таблице
 *
 * @param string $table
 * @param array $values
 * @return boolean
 */
function insertRow($table, $values = array(), $return_id = true) {
    foreach ($values as $k => $v) {
        $cols[] = $k;
        $vals[] = addcslashes($v, '\'\\');
    }
    $qr = query('INSERT INTO `' . $table . '` (`' . implode('`, `', $cols) . '`) VALUES (\'' . implode('\',\'', $vals) . '\')');
    if($return_id){
        $qr = lastAutoincrement();
    }
    return $qr;
}

/**
 * Обновляет данные в строке
 *
 * @param string $table
 * @param string $where
 * @param array $values
 * @return boolean
 */
function updateRow($table, $where = false, $values = array()) {
    $where = ($where) ? ' WHERE ' . $where : '';
    foreach ($values as $k => $v) {
        $vals[] = '`' . $k . '`' . '=\'' . addcslashes($v, '\'\\') . '\'';
    }
    return query('UPDATE ' . $table . ' SET ' . implode(', ', $vals) . $where);
}

/**
 * Удаляет подходящую под условия строку (строки) из таблицы
 *
 * @param string $table
 * @param string $where
 * @return query_result
 */
function deleteRow($table, $where = false) {
    $where = ($where) ? ' WHERE ' . $where : '';
    return query('DELETE FROM `' . $table . '`' . $where);
}

/**
 * Возвращает количество строк, подходящих под условие
 *
 * @param string $table
 * @param string $where
 * @param string $sort
 * @param string $limit
 * @return integer
 */
function countRows($table, $where = false, $sort = false, $limit = false) {
    $where = ($where) ? ' WHERE ' . $where : '';
    $sort  = ($sort) ? ' ORDER BY ' . $sort : '';
    $limit = ($limit) ? ' LIMIT ' . $limit : '';
    $q     = 'SELECT * FROM `' . $table . '`' . $where . $sort . $limit;
    return num(query($q));
}