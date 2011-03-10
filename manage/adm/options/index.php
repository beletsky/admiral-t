<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/adm/options/index.php                                                #
#   Константы и списки.                                                        #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../../../');
define ('PATH_TO_ADMIN', '../../');
define ('PAGE_TITLE',    'Администрирование. Константы и списки');
define ('PAGE_CODE',     'adm_options');

define ('ACT_LIST',      'list');
define ('__ACT_ADD',       'add');
define ('__ACT_EDIT',      'edit');
define ('__ACT_ADD_PROC',  'add_proc');
define ('__ACT_EDIT_PROC', 'edit_proc');
define ('__ACT_DEL_PROC',  'del_proc');
define ('ACT_DEL_PIC',   'del_pic');

require (PATH_TO_ADMIN . 'inc/top.inc.php');
require_once (PATH_TO_ADMIN . 'inc/func_options.inc.php');

print get_header('Константы и списки');

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
    $form['ID_Option'] = $id;
}

// Выполним изменения
$msg = '';
$err = '';
switch ($action) {
    case __ACT_ADD_PROC: {
        if ($err = options_add($form)) {
            $action = __ACT_ADD;
        } else {
            $form = array();
            $action = ACT_LIST;
        }
        break;
    }
    case __ACT_EDIT_PROC: {
        if ($err = options_edit($form)) {
            $action = __ACT_EDIT;
        } else {
            $action = ACT_LIST;
            $form = array();
        }
        break;
    }
    case __ACT_DEL_PROC: {
        $err = options_del($id);
        $action = ACT_LIST;
        $form = array();
        break;
    }
}

// Покажем форму
print get_subheader($action == ACT_LIST ? 'Список' : ($action == __ACT_ADD ? 'Добавление' : 'Редактирование'));
print get_formatted_error($err);
if ($action != ACT_LIST) {

    print get_link('Вернуться к списку', $this_page);
    print GetForm($form, $action == __ACT_ADD ? __ACT_ADD_PROC : __ACT_EDIT_PROC);

} else {    

    // Покажем список
    print get_delete_script($this_page . '?a=' . __ACT_DEL_PROC . '&id=');
    print get_link('Добавить', $this_page . '?a=' . __ACT_ADD);
    print get_formatted_message($msg);
    print GetList();
 
}

require (PATH_TO_ADMIN . 'inc/bottom.inc.php');


# Форма
function GetForm($form, $action) {
    global $this_page;
    $tpl = new Template();
    $tpl->set_file('main', 'form.ihtml');
    $tpl->set_block('main', 'value', 'values');
    $tpl->set_var('values', '');
    $tpl->set_var('THIS_PAGE', $this_page);
    $tpl->set_var('ID', isset($form['ID_Option']) ? $form['ID_Option'] : '');
    $tpl->set_var('BTN_CAPTION', $action == __ACT_ADD_PROC ? 'Добавить' : 'Изменить');
    $tpl->set_var('ACTION', $action);
    // максимальный номер -- пустые строки будут со следующими номерами
    $maxNum = 0;
    // номер последней строки с данными
    $lastRow = 0;
    // выведем значения
    $i = 0;
    if (isset($form['values']) && is_array($form['values']))
        foreach ($form['values'] as $v)
            if (is_array($v)) {
                if (!$v['OptionOrder']) $v['OptionOrder'] = 1;
                $tpl->set_var('I', $i);
                $tpl->set_var('OPTION_CODE', isset($v['OptionSubCode']) ? htmlspecialchars($v['OptionSubCode']) : '');
                $tpl->set_var('OPTION_VALUE', isset($v['OptionValue']) ? htmlspecialchars($v['OptionValue']) : '');
                $tpl->set_var('OPTION_ORDER', isset($v['OptionOrder']) ? htmlspecialchars($v['OptionOrder']) : '');
                if (isset($v['OptionOrder']) && string_is_int($v['OptionOrder']) && $v['OptionOrder'] > $maxNum) 
                    $maxNum = $v['OptionOrder'];
                $tpl->parse('values', 'value', true);
                $i++;
                if ((isset($v['OptionSubCode']) && $v['OptionSubCode'] != '') 
                    || (isset($v['OptionValue']) && $v['OptionValue'] != '')) $lastRow = $i;
            }
    // выведем пустые строки
    $rowCnt = 20 - $i + $lastRow;
    for ($j = 0; $j < $rowCnt; $j++) {
        $maxNum++;
        $tpl->set_var('I', $i);
        $tpl->set_var('OPTION_CODE', '');
        $tpl->set_var('OPTION_VALUE', '');
        $tpl->set_var('OPTION_ORDER', $maxNum);
        $tpl->parse('values', 'value', true);
        $i++;
    }
    $tpl->set_var('OPTION_CODE', isset($form['OptionCode']) ? htmlspecialchars($form['OptionCode']) : '');
    $tpl->set_var('OPTION_NAME', isset($form['OptionName']) ? htmlspecialchars($form['OptionName']) : '');
    return $tpl->parse('C', 'main', false);
}

# Список
function GetList() {
    global $db, $this_page;
    $db->Query('select count(*) from dwOptions where ID_Parent is null');
    $cnt = $db->NextRecord() ? $db->F(0) : 0;

    $tbl = new PslAdmTbl;

    $tbl->mSortDefault   = 'o.ID_Option';
    $tbl->mRecordsCnt    = $cnt;
    $tbl->mSessionPrefix = 'a_a_o';
    $tbl->mDownImg       = PATH_TO_ADMIN . 'img/down.gif';
    $tbl->mUpImg         = PATH_TO_ADMIN . 'img/up.gif';
    $tbl->mSortFields    = array('o.ID_Option' => 'o.ID_Option', 'OptionCode' => 'OptionCode', 'OptionName' => 'OptionName', 
                                 'OptionValue' => 'OptionValue', 'ChildCnt' => 'ChildCnt');

    $tbl->SetInPageOptions(get_inpage_array());
    $tbl->SetHead($this_page, array('ID', 'Код', 'Название', 'Значение', 'Кол-во&nbsp;эл-в&nbsp;списка', 'Действия'), 
                  array('Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', 
                        'Кликните для сортировки по колонке', 'Кликните для сортировки по колонке', ''));
    $q  = 'select o.*, count(l.ID_Option) as ChildCnt from dwOptions o left join dwOptions l on l.ID_Parent = o.ID_Option' .
          ' where o.ID_Parent is null group by o.ID_Option' . 
          $tbl->GetOrderByClause() . $tbl->GetLimitClause();
    $db->Query($q);

    while ($db->NextRecord()) 
        $tbl->SetRow(array($db->F('ID_Option'), 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_Option') . '&a=' . __ACT_EDIT . '">' . htmlspecialchars($db->F('OptionCode')) . '</a>', 
                           '<a href="' . $this_page . '?id=' . $db->F('ID_Option') . '&a=' . __ACT_EDIT . '">' . htmlspecialchars($db->F('OptionName')) . '</a>',
                           $db->F('ChildCnt') ? '-список-' : htmlspecialchars($db->F('OptionValue')),
                           '<center>' . ($db->F('ChildCnt') ? $db->F('ChildCnt') + 1 : '-') . '</center>',
                           '<center><a href="javascript:deleteRecord(' . $db->F('ID_Option') . ')"><img src="' . PATH_TO_ADMIN . 'img/del.gif" border=0 alt="Удалить"></a></center>',
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
        $db->Query('select * from dwOptions where ID_Option = ' . $id);
        if ($db->NextRecord()) $r = $db->mRecord;
        $r['values'][0]['OptionSubCode'] = $db->F('OptionSubCode');
        $r['values'][0]['OptionValue'] = $db->F('OptionValue');
        $r['values'][0]['OptionOrder'] = $db->F('OptionOrder');
        
        // Вытащим список подчиненных элементов
        $db->Query('select * from dwOptions where ID_Parent = ' . $id . ' order by OptionOrder');
        $i = 1;
        while ($db->NextRecord()) {
            $r['values'][$i]['OptionSubCode'] = $db->F('OptionSubCode');
            $r['values'][$i]['OptionValue'] = $db->F('OptionValue');
            $r['values'][$i]['OptionOrder'] = $db->F('OptionOrder');
            $i++;
        }
    }
    return $r;
}

?>