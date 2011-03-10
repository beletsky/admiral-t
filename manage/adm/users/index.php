<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/adm/users/index.php                                                  #
#   Пользователи.                                                              #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../../../');
define ('PATH_TO_ADMIN', '../../');
define ('PAGE_TITLE',    'Администрирование. Пользователи');
define ('PAGE_CODE',     'adm_users');

define ('ACT_ADD',       'add');
define ('ACT_EDIT',      'edit');
define ('ACT_ADD_PROC',  'add_proc');
define ('ACT_EDIT_PROC', 'edit_proc');
define ('ACT_DEL_PROC',  'del_proc');

require (PATH_TO_ADMIN . 'inc/top.inc.php');
include_once (PATH_TO_ADMIN . 'inc/func_users.inc.php');

print get_header('Пользователи');

// Определим текущее действие
$action = '';
if (isset($_GET['a'])) $action = $_GET['a'];
if ($action == '' && isset($_POST['a'])) $action = $_POST['a'];
if ($action == '') $action = ACT_ADD;

// Получим параметры
$id = 0;
if (isset($_GET['id'])) $id = $_GET['id'];
if (!string_is_id($id) && isset($_POST['id'])) $id = $_POST['id'];
$form = GetData($id);
$form['ID_User'] = $id;

// Выполним изменения
$msg = '';
$err = '';
switch ($action) {
    case ACT_ADD_PROC: {
        $err = user_adm_add($form);
        if ($err == '') $form = array();
        $action = ACT_ADD;
        break;
    }
    case ACT_EDIT_PROC: {
        if ($err = user_adm_edit($form)) {
            $action = ACT_EDIT;
        } else {
            $action = ACT_ADD;
            $form = array();
        }
        break;
    }
    case ACT_DEL_PROC: {
        $err = user_adm_del($id);
        $action = ACT_ADD;
        $form = array();
        break;
    }
}

// Покажем форму
print get_subheader($action == ACT_ADD ? 'Добавление' : 'Редактирование');
print get_formatted_error($err);
print GetForm($form, $action == ACT_ADD ? ACT_ADD_PROC : ACT_EDIT_PROC);

// Покажем список
print get_delete_script($this_page . '?a=' . ACT_DEL_PROC . '&id=');
print get_subheader('Список');
if ($action != ACT_ADD) print get_link('Добавить', $this_page);
print get_formatted_message($msg);
print GetList();

require (PATH_TO_ADMIN . 'inc/bottom.inc.php');


# Форма
function GetForm($form, $action) {
    global $this_page;
    $tpl = new Template();
    $tpl->set_file('main', 'form.ihtml');
    $tpl->set_var('THIS_PAGE', $this_page);
    $tpl->set_var('ID', isset($form['ID_User']) ? $form['ID_User'] : '');
    $tpl->set_var('LOGIN', isset($form['UserLogin']) ? htmlspecialchars($form['UserLogin']) : '');
    $tpl->set_var('PWD', isset($form['UserPwd']) ? htmlspecialchars($form['UserPwd']) : '');
    $tpl->set_var('DELETED_CHECKED', isset($form['UserDeleted']) ? ($form['UserDeleted'] ? ' checked' : '') : '');
    $tpl->set_var('GROUP_OPTIONS', get_select_options(isset($form['GroupCode']) ? $form['GroupCode'] : '', get_all_admin_groups()));
    $tpl->set_var('BTN_CAPTION', $action == ACT_ADD_PROC ? 'Добавить' : 'Изменить');
    $tpl->set_var('ACTION', $action);
    return $tpl->parse('C', 'main', false);
}

# Список
function GetList() {
    global $db, $this_page;
    $db->Query('select count(*) from dwUsers inner join dwGroups on dwGroups.ID_Group = dwUsers.ID_Group');
    $cnt = $db->NextRecord() ? $db->F(0) : 0;

    $tbl = new PslAdmTbl;

    $tbl->mSortDefault   = 'UserLogin';
    $tbl->mRecordsCnt    = $cnt;
    $tbl->mSessionPrefix = 'a_a_u';
    $tbl->mDownImg       = PATH_TO_ADMIN . 'img/down.gif';
    $tbl->mUpImg         = PATH_TO_ADMIN . 'img/up.gif';
    $tbl->mSortFields    = array('ID_User' => 'ID_User', 'GroupName' => 'GroupName', 'UserLogin' => 'UserLogin', 'UserDeleted' => 'UserDeleted');

    $tbl->SetInPageOptions(get_inpage_array());
    $tbl->SetHead($this_page, array('ID', 'Группа', 'Логин', 'Удален', 'Действия'), 
                  array('Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 
                        'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', ''));
    $q  = 'select dwUsers.*, dwGroups.GroupName';
    $q .= ' from dwUsers';
    $q .= ' inner join dwGroups on dwGroups.ID_Group = dwUsers.ID_Group ';
    $q .= $tbl->GetOrderByClause() . $tbl->GetLimitClause();             
    $db->Query($q);

    while ($db->NextRecord()) 
        $tbl->SetRow(array($db->F('ID_User'), 
                           htmlspecialchars($db->F('GroupName')), 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_User') . '&a=' . ACT_EDIT . '">' . $db->F('UserLogin') . '</a>', 
                           $db->F('UserDeleted') ? 'Да' : 'Нет',
                           '<center><a href="javascript:deleteRecord(' . $db->F('ID_User') . ')"><img src="' . PATH_TO_ADMIN . 'img/del.gif" border=0 alt="Удалить"></a></center>',
                           ));
     
    return $tbl->GetTable();
}

# Получить параметры как массив из постинга или из базы
function GetData($id) {
    global $_POST;
    return isset($_POST['form']) ? $_POST['form'] : GetDbData($id);
}

# Получить параметры как массив из базы
function GetDbData($id) {
    global $db;
    $r = array();
    if (string_is_id($id)) {
        $db->query('select dwUsers.*, dwGroups.GroupCode from dwUsers inner join dwGroups on dwGroups.ID_Group = dwUsers.ID_Group where dwUsers.ID_User = ' . $id);
        if ($db->NextRecord()) $r = $db->mRecord;
    } 
    return $r;
}

?>