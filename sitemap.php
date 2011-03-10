<?php

ini_set( 'display_errors', 'On' );

define( 'PATH_TO_ROOT', './' );
define( 'PATH_TO_ADMIN', './admin/' );

require( PATH_TO_ROOT . 'inc/init.php' );

define( 'PATH_TO_TPL', PATH_TO_ROOT . 'tpl/' );

include_once( PATH_TO_ROOT . 'inc/catalog.class.php' );
include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_object.php' );
include_once( PATH_TO_ROOT . 'inc/xml_func.php' );


$g_catalog = new Catalog( $_SERVER[ 'REQUEST_URI' ] );


// Подготовить шаблон Sitemap.
$tpl = new Template();
$tpl->set_file( 'sitemap', PATH_TO_TPL . 'sitemap_xml.tpl' );
$tpl->set_block( 'sitemap', 'page_', 'page__' );

$tpl->set_var( 'HOST', $g_sites[ HOST_CODE ][ 'Host' ] );

// Заполнить список всех страниц сайта.
sitemap_place_tree( $tpl, $g_catalog->get_tree_from() );

//header( 'Content-Type: application/rss+xml; charset=UTF-8' );
//$tpl->pparse( 'c', 'sitemap' );

echo str_replace( array( "\r", ' ' ), array( '<br />', '&nbsp;' ), 
    htmlspecialchars( $tpl->parse( 'c', 'sitemap' ) ) );

exit();


function sitemap_place_tree( &$tpl, $tree )
{
    global $g_catalog;

    for( $i = 0; $i < count( $tree ); ++$i )
    {
        $page_id = $tree[ $i ];
        $page = $g_catalog->page( $page_id );

        // Index page goes alone with different parameters.
        if( $page->page_code() == 'index' ) continue;
        if( $page->menu() == 'hidden' ) continue;
        
//        $page->place( $tpl, 'page_' );
        $tpl->set_var( 'PAGE_URL', $g_catalog->path_url( $g_catalog->get_path_to( $page_id ) ) );
        $tpl->set_var( 'PAGE_TIME', date( 'Y-m-d' ) );
        $tpl->set_var( 'PAGE_PRIORITY', '0.7' );
        $tpl->parse( 'page__', 'page_', true );

        if( $page->menu() == 'object' ) sitemap_place_objects( $tpl, $page );
        
        if( isset( $tree[ $i + 1 ] ) && is_array( $tree[ $i + 1 ] ) )
        {
            sitemap_place_tree( $tpl, $tree[ $i + 1 ] );
            // Пропустить вложенный массив.
            ++$i;
        }
    }
}


function sitemap_place_objects( &$tpl, &$page )
{
    global $g_catalog, $g_object_db;
    
    $f = $g_object_db->filter();
    $f->page( $page->id() );
    
    $list_pages = ceil( $g_object_db->count( $f ) / OBJECT_ON_PAGE );
    for( $i = 1; $i < $list_pages; ++$i )
    {
        $tpl->set_var( 'PAGE_URL', $g_catalog->path_url( 
            $g_catalog->get_path_to( $page->id() ), array( $i ) ) );
        $tpl->set_var( 'PAGE_TIME', date( 'Y-m-d' ) );
        $tpl->set_var( 'PAGE_PRIORITY', '0.7' );
        $tpl->parse( 'page__', 'page_', true );
    }
    
    $list = array();
    $g_object_db->load_list( $list, $f );
    foreach( $list as $object )
    {
        $tpl->set_var( 'PAGE_URL', $g_catalog->path_url( 
            $g_catalog->get_path_to( $page->id() ), array( $object->code() ) ) );
        $tpl->set_var( 'PAGE_TIME', date( 'Y-m-d' ) );
        $tpl->set_var( 'PAGE_PRIORITY', '0.7' );
        $tpl->parse( 'page__', 'page_', true );
    }
}


?>
