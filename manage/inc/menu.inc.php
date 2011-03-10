<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/inc/menu.inc.php                                                     #
#   Вывод меню. Включается в файл top.inc.php, поэтому не содержит             #
#   включение необходимых модулей.                                             #
#                                                                              #
################################################################################

$tpl = new Template();
$tpl->set_file('main', PATH_TO_ADMIN . 'inc/menu.ihtml');
$tpl->set_block('main', 'item', 'item_');
$tpl->set_block('main', 'sub', 'sub_');
$tpl->set_block('sub', 'subitem', 'subitem_');
$tpl->set_block('main', 'subsub', 'subsub_');
$tpl->set_block('subsub', 'subsubitem', 'subsubitem_');
$tpl->set_var('PATH_TO_ROOT', PATH_TO_ADMIN);
$tpl->set_var('item_', '');
$tpl->set_var('sub_', '');
$tpl->set_var('subsub_', '');

unset($submenu);
unset($subsubmenu);

// Выведем меню первого уровня
$menu = $g_page->GetMenuOf('');

foreach ($menu as $m) {
    $tpl->set_var('NAME', $m['MenuName']);
    $tpl->set_var('FOLDER', isset($m['PagePath']) ? $m['PagePath'] : '');
    $tpl->set_var('CLS', in_array(PAGE_CODE, $m['_codes']) ? ' class=selected' : '');
    $tpl->parse('item_', 'item', true);
    // Загрузим список ссылок второго уровня
    if (in_array(PAGE_CODE, $m['_codes'])) $submenu = $g_page->GetMenuOf($m['MenuCode']);
}

// Страницы второго уровня
if (isset($submenu) && is_array($submenu) && count($submenu)) {
    foreach ($submenu as $m) {
        $tpl->set_var('NAME', $m['MenuName']);
        $tpl->set_var('FOLDER', isset($m['PagePath']) ? $m['PagePath'] : '');
        $tpl->set_var('CLS', in_array(PAGE_CODE, $m['_codes']) ? ' class=selected' : '');
        $tpl->parse('subitem_', 'subitem', true);
        // Загрузим список ссылок третьего уровня
        if (in_array(PAGE_CODE, $m['_codes'])) $subsubmenu = $g_page->GetMenuOf($m['MenuCode']);
    }
    $tpl->parse('sub_', 'sub', false);
}
//                           print is_array($subsubmenu) ? 'true' : 'false';
// Страницы третьего уровня
if (isset($subsubmenu) && is_array($subsubmenu) && count($subsubmenu)) {
    foreach ($subsubmenu as $m) {
        $tpl->set_var('NAME', $m['MenuName']);
        $tpl->set_var('FOLDER', isset($m['PagePath']) ? $m['PagePath'] : '');
        $tpl->set_var('CLS', in_array(PAGE_CODE, $m['_codes']) ? ' class=selected' : '');
        $tpl->parse('subsubitem_', 'subsubitem', true);
    }
    $tpl->parse('subsub_', 'subsub', false);
}

$tpl->pparse('C', 'main', false);

?>