<?php

ini_set( 'display_errors', 'On' );


define( 'PATH_TO_ROOT', './' );
define( 'PATH_TO_ADMIN', './admin/' );


require( PATH_TO_ROOT . 'inc/init.php' );

define( 'PATH_TO_TPL', PATH_TO_ROOT . 'tpl/' );

include_once( PATH_TO_ROOT . 'inc/catalog.class.php' );

$g_catalog = new Catalog( $_SERVER[ 'REQUEST_URI' ] );


include_once( PATH_TO_ROOT . 'inc/settings.class.php' );
include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_feedback.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_menu.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_path.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_static_page.php' );


// Получаем содержимое текущей страницы.
$page = $g_catalog->page();

//if( !isset( $page->m_db ) )
//{
//    header( 'HTTP/1.1 301 Moved Permanently' ); 
//    header( 'Location: /' );
//    return;
//}

$body = '';

// Формируем тело страницы.
if( $page->page_code() == 'sitemap' )
{
    include_once( PATH_TO_ROOT . 'inc/tpl_sitemap.php' );
    $body .= proceed_sitemap( $page );
}
else if( $page->page_code() == 'index' )
{
    $body .= proceed_static_page( $page );
    
    include_once( PATH_TO_ROOT . 'inc/tpl_object.php' );
    $body .= proceed_object_index( $page->page_code() );
}
else if( $page->menu() == 'object' )
{
    include_once( PATH_TO_ROOT . 'inc/tpl_object.php' );
    $body .= proceed_object( $page );
}
else if( $page->page_code() == 'error404' )
{
    $body .= proceed_404( SITE_ROOT_PATH );
}
else
{
    $body .= proceed_static_page( $page );
}

// Если по результатам работы остались неразобранные параметры,
// нужно поругаться 404 кодом со ссылкой на успешно разобранный адрес.
if( !$g_catalog->param_list_empty() ) $body = proceed_404( $g_catalog->current_path_url() );


// Отображение полной страницы.
{
    $tpl = new Template();
    $tpl->set_file( 'set_', PATH_TO_TPL . 'body.tpl' );
    $tpl->set_block( 'set_', 'form_', 'form__' );

    $settings = new Settings( 1 );
    $settings->place( $tpl, 'set_' );
        
    $page->place( $tpl, 'page_' );
    
    $tpl->set_var( 'HOST_CODE', HOST_CODE );
    $tpl->set_var( 'HOST', $g_sites[ HOST_CODE ][ 'Host' ] );

    $tpl->set_var( 'TITLE', $page->page_title() );
    $tpl->set_var( 'BODY', $body );
    
    $tpl->set_var( 'MENU_TOP', create_menu( 
        $g_catalog->make_menu_tree( array( 'top' ) ),
        array( PATH_TO_TPL . 'menu_top_1.tpl' ) ) );
    
    $tpl->set_var( 'MENU_LEFT', create_menu( 
        $g_catalog->make_menu_tree( array( 'top', 'left', 'object' ) ),
        array( PATH_TO_TPL . 'menu_left_1.tpl', PATH_TO_TPL . 'menu_left_2.tpl' ) ) );
                        
    $tpl->set_var( 'PATH', $page->page_code() != 'index' ? make_path( true, 'Главная' ) : '' );
    
    $tpl->set_var( 'YEAR', date( 'Y' ) );
                
    $tpl->pparse( 'C', 'set_', false );
    unset( $tpl );
}


?>
