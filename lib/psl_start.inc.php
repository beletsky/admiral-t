<?php
################################################################################
#                                                                              #
#   PhpSiteLib. Библиотека для быстрой разработки сайтов                       #
#                                                                              #
#   Copyright (с) 2002, Ilya Blagorodov (blagorodov.ru)                        #
#                                                                              #
#   psl_start.inc.php                                                          #
#   Инициализация объекта доступа к БД, настройка обработки ошибок,            #
#   инициализация некоторых глобальных переменных, запуск сессии,              #
#   очистка входных переменных от лишних слэшей                                #
#                                                                              #
################################################################################


################################################################################
# Проверка наличия переменной $path_to_root
if (!defined('PATH_TO_ROOT') || PATH_TO_ROOT == '') die('Не определена константа PATH_TO_ROOT. Продолжение невозможно.');

# Установка режимов обработки скриптов
set_magic_quotes_runtime(0);

# DB: Путь до каталога с этой библиотекой
define ('_PHPSITELIB_PATH', PATH_TO_ROOT . 'lib/'); 

# Текущая страница
$this_page = $_SERVER['PHP_SELF'];

# Загружаем настройки
require (_PHPSITELIB_PATH . 'psl_config.inc.php');          

// Постфикс для сессионных переменных, хранящих авторизационные параметры (для объекта PslUser)
if (!isset($_PHPSITELIB_USER_POSTFIX)) $_PHPSITELIB_USER_POSTFIX = '';

                         

################################################################################
# Подключения необходимых файлов библиотеки
require (_PHPSITELIB_PATH . 'psl_mysql.inc.php');       
require (_PHPSITELIB_PATH . 'psl_utils.inc.php');           
if (_PHPSITELIB_USE_TEMPLATE) require (_PHPSITELIB_PATH . 'template.inc.php');
if (_PHPSITELIB_USE_USER)     require (_PHPSITELIB_PATH . 'psl_user.inc.php');
if (_PHPSITELIB_USE_PAGE)     require (_PHPSITELIB_PATH . 'psl_page.inc.php');
if (_PHPSITELIB_USE_SPY)      require (_PHPSITELIB_PATH . 'psl_spy.inc.php');
if (_PHPSITELIB_USE_GB)       require (_PHPSITELIB_PATH . 'psl_gb.inc.php');
if (_PHPSITELIB_USE_FORUM)    require (_PHPSITELIB_PATH . 'psl_forum.inc.php');
if (_PHPSITELIB_USE_VOTER)    require (_PHPSITELIB_PATH . 'psl_voter.inc.php');
if (_PHPSITELIB_USE_CART)     require (_PHPSITELIB_PATH . 'psl_cart.inc.php');
//if (_PHPSITELIB_USE_CONTENT)  require (_PHPSITELIB_PATH . 'psl_content.inc.php');
if (_PHPSITELIB_USE_OPTIONS)  require (_PHPSITELIB_PATH . 'psl_options.inc.php');
if (_PHPSITE_USE_PASSWORD_GND) require (_PHPSITELIB_PATH . 'psl_password.php');



################################################################################
# Если показывать время генерации страницы, запомним текущее время
if (_PHPSITELIB_GENTIME) $_phpsitelib_gen = get_micro_time();

# Если установлено добавление слэшей, то удаляем их
if (get_magic_quotes_gpc()) {
    remove_magic_quotes('_GET');
    remove_magic_quotes('_POST');
    remove_magic_quotes('HTTP_COOKIE_VARS');
}

function remove_magic_quotes($vars, $suffix = '') {
    eval("\$vars_val =& \$GLOBALS[\"$vars\"]$suffix;");
    if (is_array($vars_val)) {
        foreach ($vars_val as $key => $val)
            remove_magic_quotes($vars, $suffix."[\"$key\"]");
    } else {
        $vars_val = stripslashes($vars_val);
        eval("\$GLOBALS$suffix = \$vars_val;");
    }
}

# Вырежем индексный файл из имени текущей страницы
$this_page = preg_replace('/'._PHPSITELIB_CUTINDEX.'/', '', $this_page);

#set_error_handler('mail_bug_report');

error_reporting(_PHPSITELIB_ERRLVL);

cache_control(_PHPSITELIB_CACHE, _PHPSITELIB_CACHEEXP);



################################################################################
# Наследование классов для их кастомизации
class PslDb extends PslMySql {
  var $mHost        = _PHPSITELIB_DB_HOST;
  var $mDatabase    = _PHPSITELIB_DB_NAME;
  var $mUser        = _PHPSITELIB_DB_USER;
  var $mPassword    = _PHPSITELIB_DB_PWD;
  var $mCharset     = _PHPSITELIB_DB_CHARSET;
  var $mDebug       = _PHPSITELIB_DEBUG;
  var $mHaltOnError = _PHPSITELIB_DB_REPORT;
}

# Функция отсылки сообщения об ошибке в БД по email
function mail_bug_report_db($text) {
    global $this_page;
    if (_PHPSITELIB_ERRMAIL) {
        $text = "Страница: $this_page\n\n".$text;
        @mail(_PHPSITELIB_ERRMAIL, "Database bug report", $text);
    }
}

# Функция отсылки сообщения об ошибке по email
function mail_bug_report($errno, $errstr, $errfile, $errline) {
    switch ($errno) {
      case E_USER_ERROR:
        $text = "FATAL [$errno] $errstr<br>\n".
                "  Fatal error in line ".$errline." of file ".$errfile.
                ", PHP ".PHP_VERSION." (".PHP_OS.")\n".
                "Aborting...\n";
        break;
      case E_USER_WARNING:
        $text = "ERROR [$errno] $errstr in line $errline of file $errfile\n";
        break;
      case E_USER_NOTICE:
        $test = "WARNING [$errno] $errstr in line $errline of file $errfile\n";
        break;
      default:
        $errors = array(  1 => "E_ERROR",           2 => "E_WARNING",       4 => "E_PARSE", 
                          8 => "E_NOTICE",         16 => "E_CORE_ERROR",   32 => "E_CORE_WARNING", 
                         64 => "E_COMPILE_ERROR", 128 => "E_COMPILE_WARNING");
        if( !( $errno & error_reporting() ) ) return;
        $text = "[".$errors[$errno]."] $errstr in line $errline of file $errfile\n";
        break;
    }
    if (_PHPSITELIB_ERRMAIL) 
        @mail(_PHPSITELIB_ERRMAIL, 'Bug report', $text);
    else 
        print nl2br($text);
}

# Устанавливает уровень кеширования (честно содрано из PHPLib)
function cache_control($allowcache, $expire) {
    switch ($allowcache) {

      case 'passive':
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', getlastmod()) . ' GMT');
        # possibly ie5 needs the pre-check line. This needs testing.
        header('Cache-Control: post-check=0, pre-check=0');
      break;

      case 'public':
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expire * 60) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', getlastmod()) . ' GMT');
        header('Cache-Control: public');
        header('Cache-Control: max-age=' . $expire * 60);
      break;
 
      case 'private':
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', getlastmod()) . ' GMT');
        header('Cache-Control: private');
        header('Cache-Control: max-age=' . $expire * 60);
        header('Cache-Control: pre-check=' . $expire * 60);
      break;

      default:
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-cache');
        header('Cache-Control: post-check=0, pre-check=0');
        header('Pragma: no-cache');
      break;
    }
}
  


################################################################################
# Создание объектов
$db = new PslDb;
$db->mErrHandler = 'mail_bug_report_db';

if (_PHPSITELIB_USESESS) {
    session_cache_limiter(_PHPSITELIB_SESS_CACHE);
    session_cache_expire(_PHPSITELIB_SESS_EXPIRE);
    session_start();
}

if (_PHPSITELIB_USE_USER) {
    $g_user = new PslUser;
    $g_user->mDb = $db;
}
if (_PHPSITELIB_USE_PAGE && _PHPSITELIB_USE_USER) {
    $g_page = new PslPage;
    $g_page->mDb = $db;
    $g_page->mUser = $g_user;
}
if (_PHPSITELIB_USE_CONTENT) {
    $g_content = new PslContent;
    $g_content->mDb = $db;
}
if (_PHPSITELIB_USE_OPTIONS) {
    $g_options = new PslOptions;
    $g_options->mDb = $db;
}

if (_PHPSITE_USE_PASSWORD_GND){
    $g_password = new Text_Password;
}

?>