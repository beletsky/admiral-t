<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/adm/index.php                                                        #
#   Главная страница раздела Администрирование админской части.                #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../../');
define ('PATH_TO_ADMIN', '../');
define ('PAGE_TITLE',    'Пользователи');
define ('PAGE_CODE',     'user');

require (PATH_TO_ADMIN . 'inc/top.inc.php');

$tpl = new Template();
$tpl->set_file('main', 'index.ihtml');
$tpl->set_block('main', 'item', 'item_');
$tpl->set_var('PATH_TO_ROOT', PATH_TO_ADMIN);

// Покажем навигацию
$menu = $g_page->GetMenuOf($g_page->GetMenuCodeByPageCode(PAGE_CODE));
if (is_array($menu)) foreach ($menu as $m) {
    $tpl->set_var(array('FOLDER' => isset($m['PagePath']) ? $m['PagePath'] : '', 'NAME' => $m['MenuName']));
    $tpl->parse('item_', 'item', true);
}

$tpl->pparse('C', 'main', false);

require (PATH_TO_ADMIN . 'inc/bottom.inc.php');
?>