<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/inc/func_menu.inc.php                                                #
#   Изменения в таблице меню.                                                  #
#                                                                              #
################################################################################
/*

  function menu_add()      - Функции для работы с меню.
  function menu_edit()       Возвращают текст ошибки. 
  function menu_del()        Принимают на вход массив с параметрами - полями.
  
  function get_pages_list()   - Возвращает список страниц как массив для отображения в форме редактирования меню.
  function get_menu_list($id) - Возвращает список пунктов меню для отображения в форме редактирования меню. Принимает id текущего пункта.
  function get_parent_item($id) - Возвращает ID родительского эл-та
  function get_new_menuorder($pid) - Возвращает порядковый номер для нового подчиненного элемента $pid
  
*/

require_once (PATH_TO_ADMIN . 'inc/func_pages.inc.php');

function menu_add($data) {
    global $db;
    $data = _menu_prepare($data);
    $db->Lock(array('dwMenu', 'dwPages'));
    $r = _menu_check($data, true);
    if ($r == '') {
        $q  = 'insert into dwMenu set';
        $q .= ' ID_Parent = ' . (string_is_id($data['ID_Parent']) ? $data['ID_Parent'] : 'NULL') . ',';
        $q .= ' ID_Page = ' . (string_is_id($data['ID_Page']) ? $data['ID_Page'] : 'NULL') . ',';
        $q .= ' MenuName = "' . addslashes($data['MenuName']) . '",';
        $q .= ' MenuCode = "' . addslashes($data['MenuCode']) . '",';
        $q .= ' MenuOrder = ' . $data['MenuOrder'];
        $db->Query($q);
    }
    $db->Unlock();
    return $r;
}

function menu_edit($data) {
    global $db;
    $r = '';
    $data = _menu_prepare($data);
    $db->Lock(array('dwMenu', 'dwPages'));
    $r = _menu_check($data);
    if ($r == '') {
        $q  = 'update dwMenu set';
        $q .= ' ID_Parent = ' . (string_is_id($data['ID_Parent']) ? $data['ID_Parent'] : 'NULL') . ',';
        $q .= ' ID_Page = ' . (string_is_id($data['ID_Page']) ? $data['ID_Page'] : 'NULL') . ',';
        $q .= ' MenuName = "' . addslashes($data['MenuName']) . '",';
        $q .= ' MenuCode = "' . addslashes($data['MenuCode']) . '",';
        $q .= ' MenuOrder = ' . $data['MenuOrder'];
        $q .= ' where ID_Menu = ' . $data['ID_Menu'];
        $db->Query($q);
    }
    $db->Unlock();
    return $r;
}

function menu_del($id) {
    global $db;
    $r = '';
    if (!_is_menu_exists($id)) $r .= 'Пункт меню не найден.<br>';
    if ($r == '') {
        $db->Lock(array('dwMenu'));
        _menu_del_children($id);
        $db->Query('delete from dwMenu where ID_Menu = ' . $id);
        $db->Unlock();
    }
    return $r;
}

function get_pages_list() {
    global $db;
    $r = array('' => '');
    $db->Query('select * from dwPages order by PageName');
    while ($db->NextRecord()) $r[$db->F('ID_Page')] = $db->F('PageName');
    return $r;
}

function get_menu_list($idXclude, $idParent = '', $level = 0) {
    global $db;
    static $r = array('' => '');
    $q = 'select * from dwMenu where ID_Parent ' . (string_is_id($idParent) ? '= ' . $idParent : 'is null') .
         (string_is_id($idXclude) ? ' and ID_Menu <> ' . $idXclude : '') . ' order by MenuOrder';
    $db->Query($q);
    $tmp = array();
    while ($db->NextRecord()) $tmp[$db->F('ID_Menu')] = $db->F('MenuName');
    foreach ($tmp as $k => $v) {
        $r[$k] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level) . htmlspecialchars($v);
        get_menu_list($idXclude, $k, $level + 1);
    }
    return $r;
}

function get_parent_item($id) {
    global $db;
    $r = 0;
    if (string_is_id($id)) {
        $db->Query('select ID_Parent from dwMenu where ID_Menu = ' . $id);
        $r = $db->NextRecord() ? $db->F(0) : 0;
    }
    return $r;
}

function get_new_menuorder($pid) {
    global $db;
    $r = 0;
    $q = 'select max(MenuOrder) from dwMenu where ID_Parent ' . (string_is_id($pid) ? '= ' . $pid : 'is null');
    $db->Query($q);
    $r = $db->NextRecord() ? $db->F(0) + 1 : 0;
    return $r;
}

# Удаление всех подчиненных элементов данного элемента
function _menu_del_children($id) {
    global $db;
    // Вытащим все подчиненные элементы, для каждого вызовем рекурсивно туже функцию, 
    // затем удалим все элементы
    if (string_is_id($id)) {
        $menu = array();
        $db->Query('select ID_Menu from dwMenu where ID_Parent = ' . $id);
        while ($db->NextRecord()) $menu[] = $db->F(0);
        
        foreach ($menu as $m) _menu_del_children($m);
        
        $db->Query('delete from dwMenu where ID_Parent = ' . $id);
    }
}

function _menu_check($data, $isAdd = false) {
    global $db;
    $r = '';
    
    // Проверка наличия полей
    if ($data['MenuName'] == '') $r .= 'Введите имя.<br>';
    if ($data['MenuCode'] == '') $r .= 'Введите код.<br>';
    if ($data['MenuCode'] != '' && !string_is_login($data['MenuCode'])) $r .= 'Неверный код.<br>';
    if ($data['MenuOrder'] == '') $r .= 'Введите порядок вывода.<br>';
    if ($data['MenuOrder'] != '' && !string_is_int($data['MenuOrder'])) $r .= 'Порядок вывода должен быть целым числом.<br>';
    if ($data['ID_Parent'] != '' && !string_is_id($data['ID_Parent'])) $r .= 'Ошибка передачи параметра. Неверный ID родительского элемента.<br>';
    if ($data['ID_Page'] != '' && !string_is_id($data['ID_Page'])) $r .= 'Ошибка передачи параметра. Неверный ID страницы.<br>';
    // Если не isAdd, проверим, существует ли запись с таким ID
    if ($r == '' && !$isAdd && !_is_menu_exists($data['ID_Menu'])) $r .= 'Пункт меню не найден.<br>';
    // Проверим, существует ли запись с таким же кодом
    if ($r == '') {
        $q = 'select count(*) from dwMenu where MenuCode = "' . addslashes($data['MenuCode']) . '"';
        if (!$isAdd && string_is_id($data['ID_Menu'])) $q .= ' and ID_Menu <> ' . $data['ID_Menu'];
        $db->Query($q);
        $db->NextRecord();
        if ($db->F(0)) $r .= 'Пункт меню с таким кодом уже существует.<br>';
    }
    // Если введены parent и page, проверим существуют ли объекты, на которые они ссылаются
    if ($r == '') {
        if (string_is_id($data['ID_Parent']) && !_is_menu_exists($data['ID_Parent']))
            $r .= 'Родительский элемент не найден.<br>';
        if (string_is_id($data['ID_Page']) && !_is_page_exists($data['ID_Page']))
            $r .= 'Страница не найдена.<br>';
    }
    
    return $r;
}

function _menu_prepare(&$data) {
    $data['MenuName'] = isset($data['MenuName']) ? trim($data['MenuName']) : '';
    $data['MenuCode'] = isset($data['MenuCode']) ? trim($data['MenuCode']) : '';
    return $data;
}

function _is_menu_exists($id) {
    return is_obj_exists($id, 'ID_Menu', 'dwMenu');
}

?>