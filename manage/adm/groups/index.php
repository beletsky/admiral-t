<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/adm/groups/index.php                                                 #
#   Группы пользователей.                                                      #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../../../');
define ('PATH_TO_ADMIN', '../../');
define ('PAGE_TITLE',    'Администрирование. Группы пользователей');
define ('PAGE_CODE',     'adm_groups');

define ('ACT_LIST',      'list');
define ('ACT_ADD',       'add');
define ('ACT_EDIT',      'edit');
define ('ACT_ADD_PROC',  'add_proc');
define ('ACT_EDIT_PROC', 'edit_proc');
define ('ACT_DEL_PROC',  'del_proc');
define ('ACT_DEL_PIC',   'del_pic');

require (PATH_TO_ADMIN . 'inc/top.inc.php');
require_once (PATH_TO_ADMIN . 'inc/func_groups.inc.php');

print get_header('Группы пользователей');

// Определим текущее действие
$action = '';
if (isset($_GET['a'])) $action = $_GET['a'];
if ($action == '' && isset($_POST['a'])) $action = $_POST['a'];
if ($action == '') $action = ACT_LIST;

// Получим параметры
if (!in_array($action, array(ACT_LIST, ACT_DEL_PIC))) {
    $id = 0;
    if (isset($_GET['id'])) $id = $_GET['id'];
    if (!string_is_id($id) && isset($_POST['id'])) $id = $_POST['id'];
    $form = GetData($id);
    $form['ID_Group'] = $id;
}

// Выполним изменения
$msg = '';
$err = '';
switch ($action) {
    case ACT_ADD_PROC: {
        if ($err = group_add($form)) {
            $action = ACT_ADD;
        } else {
            $form = array();
            $action = ACT_LIST;
        }
        break;
    }
    case ACT_EDIT_PROC: {
        if ($err = group_edit($form)) {
            $action = ACT_EDIT;
        } else {
            $action = ACT_LIST;
            $form = array();
        }
        break;
    }
    case ACT_DEL_PROC: {
        $err = group_del($id);
        $action = ACT_LIST;
        $form = array();
        break;
    }
}

// Покажем форму
print get_subheader($action == ACT_LIST ? 'Список' : ($action == ACT_ADD ? 'Добавление' : 'Редактирование'));
print get_formatted_error($err);
if ($action != ACT_LIST) {

    print get_link('Вернуться к списку', $this_page);
    print GetForm($form, $action == ACT_ADD ? ACT_ADD_PROC : ACT_EDIT_PROC);

} else {    

    // Покажем список
    print get_delete_script($this_page . '?a=' . ACT_DEL_PROC . '&id=', 'При удалении группы будут удалены все пользователи данной группы! Продолжить?');
    print get_link('Добавить', $this_page . '?a=' . ACT_ADD);
    print get_formatted_message($msg);
    print GetList();
 
}

require (PATH_TO_ADMIN . 'inc/bottom.inc.php');


# Форма
function GetForm($form, $action) {
    global $this_page, $db;
    $tpl = new Template();
    $tpl->set_file('main', 'form.ihtml');
    $tpl->set_block('main', 'resource', 'resources');
    $tpl->set_var('resources', '');
    $tpl->set_var('THIS_PAGE', $this_page);
    $tpl->set_var('ID', isset($form['ID_Group']) ? $form['ID_Group'] : '');
    $tpl->set_var('CODE', isset($form['GroupCode']) ? htmlspecialchars($form['GroupCode']) : '');
    $tpl->set_var('NAME', isset($form['GroupName']) ? htmlspecialchars($form['GroupName']) : '');
    $tpl->set_var('BACKEND', isset($form['GroupAccessBackEnd']) && $form['GroupAccessBackEnd'] ? ' checked' : '');
    $tpl->set_var('FRONTEND', isset($form['GroupAccessFrontEnd']) && $form['GroupAccessFrontEnd'] ? ' checked' : '');
    $tpl->set_var('BTN_CAPTION', $action == ACT_ADD_PROC ? 'Добавить' : 'Изменить');
    $tpl->set_var('ACTION', $action);
    // получим список ресурсов и выведем его
    $db->Query('select * from dwResources order by ResourceName');
    while ($db->NextRecord()) {
        $tpl->set_var('ID_RESOURCE', $db->F('ID_Resource'));
        $tpl->set_var('RESOURCE_NAME', $db->F('ResourceName'));
        $tpl->set_var('USED', isset($form['resources'][$db->F('ID_Resource')]) && $form['resources'][$db->F('ID_Resource')] ? ' checked' : '');
        $tpl->parse('resources', 'resource', true);
    }
    return $tpl->parse('C', 'main', false);
}

# Список
function GetList() {
    global $db, $this_page;
    $db->Query('select count(*) from dwGroups');
    $cnt = $db->NextRecord() ? $db->F(0) : 0;

    $tbl = new PslAdmTbl;

    $tbl->mSortDefault   = 'GroupName';
    $tbl->mRecordsCnt    = $cnt;
    $tbl->mSessionPrefix = 'a_a_g';
    $tbl->mDownImg       = PATH_TO_ADMIN . 'img/down.gif';
    $tbl->mUpImg         = PATH_TO_ADMIN . 'img/up.gif';
    $tbl->mSortFields    = array('ID_Group' => 'ID_Group', 'GroupCode' => 'GroupCode', 'GroupName' => 'GroupName', 
                                 'GroupAccessBackEnd' => 'GroupAccessBackEnd', 'GroupAccessFrontEnd' => 'GroupAccessFrontEnd');

    $tbl->SetInPageOptions(get_inpage_array());
    $tbl->SetHead($this_page, array('ID', 'Код', 'Название', 'Доступ&nbsp;к&nbsp;Back&nbsp;End', 'Доступ&nbsp;к&nbsp;Front&nbsp;End', 'Действия'), 
                  array('Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 
                        'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', ''));
    $q = 'select * from dwGroups' . $tbl->GetOrderByClause() . $tbl->GetLimitClause();
    $db->Query($q);

    while ($db->NextRecord()) 
        $tbl->SetRow(array($db->F('ID_Group'), 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_Group') . '&a=' . ACT_EDIT . '">' . htmlspecialchars($db->F('GroupCode')) . '</a>', 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_Group') . '&a=' . ACT_EDIT . '">' . htmlspecialchars($db->F('GroupName')) . '</a>',
                           $db->F('GroupAccessBackEnd') ? 'Да' : 'Нет',
                           $db->F('GroupAccessFrontEnd') ? 'Да' : 'Нет',
                           '<center><a href="javascript:deleteRecord(' . $db->F('ID_Group') . ')"><img src="' . PATH_TO_ADMIN . 'img/del.gif" border=0 alt="Удалить"></a></center>',
                           ));
     
    return $tbl->GetTable();
}

# Получить параметры как массив из постинга или из базы
function GetData($id) {
    global $_POST;
    $form = isset($_POST['form']) ? $_POST['form'] : GetDbData($id);
    return $form;
}

# Получить параметры как массив из базы
function GetDbData($id) {
    global $db;
    $r = array();
    if (string_is_id($id)) {
    
        // Получим параметры
        $db->Query('select * from dwGroups where ID_Group = ' . $id);
        if ($db->NextRecord()) $r = $db->mRecord;
        
        // Вытащим список ресурсов
        $q = 'select ID_Resource from dwGroupResources where ID_Group = ' . $id;
        $db->Query($q);
        while ($db->NextRecord()) $r['resources'][$db->F('ID_Resource')] = true;
        
    }
    return $r;
}

?>