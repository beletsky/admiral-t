<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/inc/top.inc.php                                                      #
#   Инициализация объектов и подключение необходимых на каждой странице        #
#   модулей, проверка прав доступа к этой странице                             #
#                                                                              #
################################################################################

require_once (PATH_TO_ADMIN . 'inc/init.inc.php');
require_once (PATH_TO_ADMIN . 'inc/func.inc.php');

// Если мы на внутренней странице, то проверим авторизован ли пользователь и имеет ли он право находиться на этой странице
$g_page->mThisPage = PAGE_CODE;

if (PAGE_CODE != 'login') {
    // Если не авторизован, то попытка логина с данными, взятыми из куков
    if (!$g_user->IsAuthorized() && isset($_COOKIE['admin_login'])) {

        if ($g_user->Login($_COOKIE['admin_login'], isset($_COOKIE['admin_pwd']) ? $_COOKIE['admin_pwd'] : '')) {

            $g_page->mUser = $g_user;

        }
        
    }
    
    if (!$g_user->IsAuthorized()) {
    
        header('Location: ' . PATH_TO_ADMIN . '?ret=' . urlencode($_SERVER['REQUEST_URI']));
        exit();
    
    } elseif (!$g_page->IsPageAccessible()) {

        header('Location: ' . PATH_TO_ADMIN);
        exit();
        
    } else {
    
        $tpl = new Template;
        $tpl->set_file('main', PATH_TO_ADMIN . 'inc/top_title.ihtml');
        $tpl->set_var(array('TITLE' => PAGE_TITLE, 'PATH_TO_ADMIN' => PATH_TO_ADMIN, 'PATH_TO_ROOT' => PATH_TO_ROOT));
        $tpl->pparse('OUT', 'main', false);
        require (PATH_TO_ADMIN . 'inc/menu.inc.php');
        
    }
}

?>