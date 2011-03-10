<?php


if( version_compare( PHP_VERSION, '5.1.0', '<') >= 0 )
{
    date_default_timezone_set( 'Europe/Moscow' );
}

// В пользовательской части сайта пользователи из библиотеки не нужны.
define( '_PHPSITELIB_USESESS', false );
define( '_PHPSITELIB_USE_USER', false );
require_once( PATH_TO_ROOT . 'lib/psl_start.inc.php' );


require_once( PATH_TO_ROOT . 'inc/const.php' );
require_once( PATH_TO_ROOT . 'inc/func.php' );

require_once( PATH_TO_ROOT . 'lib/utf8/Func.php' );
Func::add_include_path( PATH_TO_ROOT . 'lib/utf8/' );

session_start();


// Код текущей страницы.
$page_code = get_http_var( 'c', 'index' );
// Параметры страницы.
$p = get_http_var( 'p', array() );

// Введенные данные.
$form = array();
if( isset( $_GET[ 'form' ] ) ) $form = array_merge( $form, $_GET[ 'form' ] );
if( isset( $_POST[ 'form' ] ) ) $form = array_merge( $form, $_POST[ 'form' ] );


?>
