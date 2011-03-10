<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/adm/menu/index.php                                                   #
#   Меню.                                                                      #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../../../');
define ('PATH_TO_ADMIN', '../../');
define ('PAGE_TITLE',    'Администрирование. Меню Back End');
define ('PAGE_CODE',     'adm_menu');

define ('ACT_LIST',      'list');
define ('ACT_ADD',       'add');
define ('ACT_EDIT',      'edit');
define ('ACT_ADD_PROC',  'add_proc');
define ('ACT_EDIT_PROC', 'edit_proc');
define ('ACT_DEL_PROC',  'del_proc');

require (PATH_TO_ADMIN . 'inc/top.inc.php');
require_once (PATH_TO_ADMIN . 'inc/func_menu.inc.php');

print get_header('Меню Back End');

// Определим текущее действие
$action = '';
if (isset($_GET['a'])) $action = $_GET['a'];
if ($action == '' && isset($_POST['a'])) $action = $_POST['a'];
if ($action == '') $action = ACT_LIST;

// Получим параметры
$id = 0; 
$pid = 0;
if ($action != ACT_LIST) {
    if (isset($_GET['id'])) $id = $_GET['id'];
    if (isset($_GET['pid'])) $pid = $_GET['pid'];
    if (!string_is_id($id) && isset($_POST['id'])) $id = $_POST['id'];
    if (!string_is_id($pid) && isset($_POST['pid'])) $pid = $_POST['pid'];
    $form = GetData($id);
    $form['ID_Menu'] = $id;
    if (!string_is_id($pid) && string_is_id($id)) $pid = get_parent_item($id);
    if (!isset($form['ID_Parent']) && string_is_id($pid)) $form['ID_Parent'] = $pid;
}

// Выполним изменения
$msg = '';
$err = '';
switch ($action) {
    case ACT_ADD_PROC: {
        if ($err = menu_add($form)) {
            $action = ACT_ADD;
        } elseif (!string_is_id($pid)) {
            $action = ACT_LIST;
        } else {
            $action = ACT_EDIT;
            $id = $pid;
            $pid = get_parent_item($id);
            $form = GetDbData($id);
        }
        break;
    }
    case ACT_EDIT_PROC: {
        if ($err = menu_edit($form)) {
            $action = ACT_EDIT;
        } elseif (!string_is_id($pid)) {
            $action = ACT_LIST;
        } else {
            $action = ACT_EDIT;
            $id = $pid;
            $pid = get_parent_item($id);
            $form = GetDbData($id);
        }
        break;
    }
    case ACT_DEL_PROC: {
        $err = menu_del($id);
        if (!string_is_id($pid)) {
            $action = ACT_LIST;
        } else {
            $action = ACT_EDIT;
            $id = $pid;
            $pid = get_parent_item($id);
            $form = GetDbData($id);
        }
        break;
    }
}

// Покажем форму
switch ($action) {
    case ACT_ADD:
        print get_subheader('Добавление');
        if (string_is_id($pid))
            print get_link('Вернуться к редактированию родительского элемента', $this_page . '?id=' . $pid . '&a=' . ACT_EDIT);
        else
            print get_link('Вернуться к списку', $this_page);
        print get_formatted_error($err);
        print GetForm($form, ACT_ADD_PROC);
        break;
    case 'edit':
        print get_subheader('Редактирование');
        if (string_is_id($pid))
            print get_link('Вернуться к редактированию родительского элемента', $this_page . '?id=' . $pid . '&a=' . ACT_EDIT);
        else
            print get_link('Вернуться к списку', $this_page);
        print get_formatted_error($err);
        print GetForm($form, ACT_EDIT_PROC);
        print get_subheader('Дочерние пункты меню');
        print get_delete_script($this_page . '?a=' . ACT_DEL_PROC . '&id=');
        print get_link('Добавить', $this_page . '?a=' . ACT_ADD . '&pid=' . $id);
        print GetList($id);
        break;
    case 'list':
        print get_subheader('Список');
        print get_link('Добавить', $this_page . '?a=' . ACT_ADD);
        print get_delete_script($this_page . '?a=' . ACT_DEL_PROC . '&id=');
        print GetList();
        break;
}

require (PATH_TO_ADMIN . 'inc/bottom.inc.php');


# Форма
function GetForm($form, $action) {
    global $this_page, $db;
    
    if (!isset($form['MenuOrder'])) $form['MenuOrder'] = get_new_menuorder(isset($form['ID_Parent']) ? $form['ID_Parent'] : '');
    
    $tpl = new Template();
    $tpl->set_file('main', 'form.ihtml');
    $tpl->set_var('THIS_PAGE', $this_page);
    $tpl->set_var('ID', isset($form['ID_Menu']) ? $form['ID_Menu'] : '');
    $tpl->set_var('PID', isset($form['ID_Parent']) ? $form['ID_Parent'] : '');
    $tpl->set_var('CODE', isset($form['MenuCode']) ? htmlspecialchars($form['MenuCode']) : '');
    $tpl->set_var('NAME', isset($form['MenuName']) ? htmlspecialchars($form['MenuName']) : '');
    $tpl->set_var('ORDER', isset($form['MenuOrder']) ? htmlspecialchars($form['MenuOrder']) : '');
    $tpl->set_var('PAGES_OPTIONS', get_select_options(isset($form['ID_Page']) && string_is_id($form['ID_Page']) ? $form['ID_Page'] : 0, get_pages_list()));
    $tpl->set_var('PARENT_OPTIONS', get_select_options(isset($form['ID_Parent']) && string_is_id($form['ID_Parent']) ? $form['ID_Parent'] : 0, 
        get_menu_list(isset($form['ID_Menu']) ? $form['ID_Menu'] : ''), false));
    $tpl->set_var('BTN_CAPTION', $action == ACT_ADD_PROC ? 'Добавить' : 'Изменить');
    $tpl->set_var('ACTION', $action);
    return $tpl->parse('C', 'main', false);
}

# Список
function GetList($pid = '') {
    global $db, $this_page;
    $db->Query('select count(*) from dwMenu where ID_Parent ' . (string_is_id($pid) ? '= ' . $pid : 'is null'));
    $cnt = $db->NextRecord() ? $db->F(0) : 0;

    $tbl = new PslAdmTbl;

    $tbl->mSortDefault   = 'MenuName';
    $tbl->mRecordsCnt    = $cnt;
    $tbl->mSessionPrefix = 'a_a_mn';
    $tbl->mDownImg       = PATH_TO_ADMIN . 'img/down.gif';
    $tbl->mUpImg         = PATH_TO_ADMIN . 'img/up.gif';
    $tbl->mSortFields    = array('ID_Menu' => 'ID_Menu', 'MenuCode' => 'MenuCode', 'MenuName' => 'MenuName', 'MenuOrder' => 'MenuOrder',
                                 'PageName' => 'PageName', 'PagePath' => 'PagePath');

    $tbl->SetInPageOptions(get_inpage_array());
    $tbl->SetHead($this_page, array('ID', 'Код', 'Название', 'Порядок&nbsp;вывода', 'Страница', 'Путь&nbsp;к&nbsp;странице', 'Действия'), 
                  array('Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 
                        'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 
                        ''), string_is_id($pid) ? 'id=' . $pid . '&a=' . ACT_EDIT : '');
                        
    $q = 'select m.*, p.PageName, p.PagePath from dwMenu m left join dwPages p on p.ID_Page = m.ID_Page where m.ID_Parent ' . 
         (string_is_id($pid) ? '= ' . $pid : 'is null') . $tbl->GetOrderByClause() . $tbl->GetLimitClause();
    $db->Query($q);

    while ($db->NextRecord()) 
        $tbl->SetRow(array($db->F('ID_Menu'), 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_Menu') . '&pid=' . $pid . '&a=' . ACT_EDIT . '">' . htmlspecialchars($db->F('MenuCode')) . '</a>', 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_Menu') . '&pid=' . $pid . '&a=' . ACT_EDIT . '">' . htmlspecialchars($db->F('MenuName')) . '</a>',
                           $db->F('MenuOrder'),
                           '<a href="' . PATH_TO_ADMIN . 'adm/pages/?id=' . $db->F('ID_Page') . '&a=' . ACT_EDIT . '">' . htmlspecialchars($db->F('PageName')) . '</a>',
                           '<a href="' . PATH_TO_ADMIN . $db->F('PagePath') . '" target=_blank>' . htmlspecialchars($db->F('PagePath')) . '</a>',
                           '<center><a href="javascript:deleteRecord(' . $db->F('ID_Menu') . ')"><img src="' . PATH_TO_ADMIN . 'img/del.gif" border=0 alt="Удалить"></a></center>',
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
        $db->Query('select * from dwMenu where ID_Menu = ' . $id);
        if ($db->NextRecord()) $r = $db->mRecord;
        
    }
    return $r;
}

?>