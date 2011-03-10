<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/home/index.php                                                       #
#   Главная страница модуля администрирования.                                 #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../../');
define ('PATH_TO_ADMIN', '../');
define ('PAGE_TITLE',    'Главная');
define ('PAGE_CODE',     'home');

require (PATH_TO_ADMIN . 'inc/top.inc.php');

$tpl = new Template();
$tpl->set_file('main', 'index.ihtml');
$tpl->set_block('main', 'item', 'item_');
$tpl->set_block('main', 'subitem', 'subitem_');
$tpl->set_block('main', 'subsubitem', 'subsubitem_');
$tpl->set_var('PATH_TO_ROOT', PATH_TO_ADMIN);

// Покажем навигацию
$menu1 = $g_page->GetMenuOf('');
if (is_array($menu1)) foreach ($menu1 as $m1) {
    $tpl->set_var(array('FOLDER' => isset($m1['PagePath']) ? $m1['PagePath'] : '', 'NAME' => $m1['MenuName']));
    $tpl->parse('item_', 'item', true);
    
    $menu2 = $g_page->GetMenuOf($m1['MenuCode']);
    
    if (is_array($menu2)) foreach ($menu2 as $m2) {
        $tpl->set_var(array('FOLDER' => isset($m2['PagePath']) ? $m2['PagePath'] : '', 'NAME' => $m2['MenuName']));
        $tpl->parse('item_', 'subitem', true);
    
        $menu3 = $g_page->GetMenuOf($m2['MenuCode']);
        
        if (is_array($menu3)) foreach ($menu3 as $m3) {
            $tpl->set_var(array('FOLDER' => isset($m3['PagePath']) ? $m3['PagePath'] : '', 'NAME' => $m3['MenuName']));
            $tpl->parse('item_', 'subsubitem', true);
        }
    }
}

$tpl->set_var(array('subitem_' => '', 'subsubitem_' => ''));
$tpl->pparse('C', 'main', false);

require (PATH_TO_ADMIN . 'inc/bottom.inc.php');
?>