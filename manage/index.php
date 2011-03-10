<?
################################################################################
#                                                                              #
#   Project name                                                               #
#                                                                              #
#   Copyright (с) 2003, Ilya Blagorodov, Sergey Efremov                        #
#                                                                              #
#   admin/index.php                                                            #
#   Авторизация раздела администрирования.                                     #
#                                                                              #
################################################################################

define ('PATH_TO_ROOT',  '../');
define ('PATH_TO_ADMIN', './');
define ('PAGE_TITLE',    'Авторизация');
define ('PAGE_CODE',     'login');

require_once (PATH_TO_ADMIN . 'inc/init.inc.php');
require_once (PATH_TO_ADMIN . 'inc/func.inc.php');

$auth_error = '';

define ('LOGIN_ERROR_ENTER_LOGIN',      'Пожалуйста введите логин');
define ('LOGIN_ERROR_LOGIN_NOT_VALID',  'Логин содержит недопустимые символы');
define ('LOGIN_ERROR_BAD_LOGIN_OR_PWD', 'Доступ запрещен');

// Сбросить куки при заходе на страницу.  
setcookie('admin_login', '', 0);
setcookie('admin_pwd', '', 0);
  
$login = '';
$pwd = '';
if (isset($_POST['form']['login'])) $login = $_POST['form']['login'];
if (isset($_POST['form']['pwd']))   $pwd   = $_POST['form']['pwd'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') { 
    if ($login == '') {
        $auth_error = LOGIN_ERROR_ENTER_LOGIN;
    } elseif (!string_is_login($login)) {
        $auth_error = LOGIN_ERROR_LOGIN_NOT_VALID;
    } elseif (!$g_user->Login($login, $pwd)) {
        $auth_error = LOGIN_ERROR_BAD_LOGIN_OR_PWD;
    } else {
        setcookie('admin_login', $login, time() + 31536000);
        setcookie('admin_pwd', $pwd, time() + 31536000);
    }
}
  
require (PATH_TO_ADMIN . 'inc/top.inc.php');

if ((isset($auth_error) && $auth_error != '') || $_SERVER['REQUEST_METHOD'] != 'POST') $g_user->Logout();

$ret = '';
if (isset($_GET['ret'])) $ret = $_GET['ret'];

if ($g_user->IsAuthorized()) {
    $goto = 'Location: ' . ($ret != '' ? 'http://' . $_SERVER['HTTP_HOST'] . $ret : PATH_TO_ROOT . "manage/home/\n");
    header($goto);
    exit();
}
  

#  ВЫВОДИМ СТРАНИЦУ
  
$tpl = new Template;
$tpl->set_file('main', 'index.ihtml');
$tpl->set_var(array('TITLE'         => PAGE_TITLE,
                    'THIS_PAGE'     => $ret != '' ? $this_page."?ret=" . urlencode($ret) : $this_page,
                    'PATH_TO_ADMIN' => PATH_TO_ADMIN,
                    'LOGIN_ERROR'   => get_formatted_error($auth_error),
                    'OLD_LOGIN'     => $login));
$tpl->pparse('OUT', 'main');
  
require (PATH_TO_ADMIN . 'inc/bottom.inc.php');

?>