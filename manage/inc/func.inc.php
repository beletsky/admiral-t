<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/inc/func.inc.php                                                     #
#   Общие функции модуля администрирования.                                    #
#                                                                              #
################################################################################

include_once( PATH_TO_ROOT . 'inc/func.php' );


/*

  string get_formatted_error(string err)      - Отдает форматированную ошибку как строку.
  string get_formatted_message(string msg)    - Отдает форматированное сообщение как строку.
  string get_header(string str)               - Отдает заголовок как строку.
  string get_subheader(string str)            - Отдает подзаголовок как строку.
  string get_link(string str, string lnk)     - Отдает ссылку с текстом str на страницу lnk.
  string get_inpage_array()                   - Отдает массив для выбиралки количества записей в таблице.
   array get_admin_groups()                   - Вернуть список админских групп пользователей.
  string get_delete_script(string lnk, string msg = '')        - Вернуть <SCRIPT> для функции удаления записи со ссылкой на lnk и текстом msg.
    bool is_obj_exists(int id, string idField, string tblName) - Проверка существования записи в таблице tblName, где idField = id

*/

function get_formatted_error($err) {
    return $err != '' ? '<p><font color=red>' . $err . '</font></p>' : '';
}

function get_formatted_message($msg) {
    return $msg != '' ? '<p>' . $msg . '</p>' : '';
}

function get_header($str) {
    return $str != '' ? '<h1>' . $str . '</h1>' : '';
}

function get_subheader($str) {
    return $str != '' ? '<h2>' . $str . '</h2>' : '';
}

function get_link($str, $lnk) {
    return $str != '' ? '<p>' . ($lnk != '' ? '<a href="' . $lnk . '">' : '') . $str . ($lnk != '' ? '</a>' : '') . '</p>' : '';
}

function get_inpage_array() {
    return array('10' => '10', '25' => '25', '100' => '100', '500' => '500', '0' => 'Все');
}

function get_admin_groups() {
    global $db;
    $r = array();
    $db->Query('select GroupCode, GroupName from dwGroups where GroupAccessBackEnd = 1');
    while ($db->NextRecord()) $r[$db->f('GroupCode')] = $db->f('GroupName');
    return $r;
}

function get_all_admin_groups() {
    global $db;
    $r = array();
    $db->Query('select GroupCode, GroupName from dwGroups ');
    while ($db->NextRecord()) $r[$db->f('GroupCode')] = $db->f('GroupName');
    return $r;
}

function get_delete_script($lnk, $msg = '', $funcName = '') {
    if ($msg == '') $msg = 'Подтвердить удаление?';
    if ($funcName == '') $funcName = 'deleteRecord';
    $r = "<script language=javascript><!--\n" .
         "function $funcName(id){if(confirm('$msg'))location.href='$lnk'+id;}\n" .
         "//-->\n</script>\n";
    return $r;
}

function is_obj_exists($id, $idField, $tblName) {
    global $db;
    $r = false;
    if (string_is_id($id)) {
        $db->Query('select ' . $idField . ' from ' . $tblName . ' where ' . $idField . ' = ' . $id . ' limit 1');
        if ($db->Nf()) $r = true;
    }
    return $r;
}

?>