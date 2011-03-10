<?php


include_once( PATH_TO_ROOT . 'inc/object.class.php' );
include_once( PATH_TO_ROOT . 'inc/pager.class.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_static_page.php' );


define( 'OBJECT_ON_PAGE', 6 );
define( 'OBJECT_ON_INDEX', 4 );


function proceed_object( &$page )
{
    global $g_catalog, $g_object_db;

    $body = '';

    $id = $g_catalog->get_next_param( '' );
    
    $f = $g_object_db->filter();
    $f->page( $page->id() );
    $f->code( $id );

    if( $id && $g_object_db->count( $f ) )
    {
        $obj = new Object( $id );
        
        $tpl = new Template();
        $tpl->set_file( 'item_', PATH_TO_TPL . 'object_one.tpl' );
        
        $tpl->set_var( 'URL', $g_catalog->current_path_url() );
        $page->place( $tpl, 'page_' );
        $obj->place( $tpl, 'item_' );
        
        $body = $tpl->parse( 'c', 'item_' );
        
        $g_catalog->path_add( $obj->title(), $id );
        
        $page = new StaticPageSurrogate( $page, $obj );
    }
    else if( !$id || is_numeric( $id ) )
    {
        $body = proceed_static_page( $page, false );
        
        // Отобразить список.
        $tpl = new Template();
        $tpl->set_file( 'page_', PATH_TO_TPL . 'object_list.tpl' );
        $tpl->set_block( 'page_', 'item_', 'item__' );
        
        $page_url = $g_catalog->current_path_url();
        $tpl->set_var( 'URL', $page_url );
        $page->place( $tpl, 'page_' );

        $f = $g_object_db->filter();
        $f->page( $page->id() );
        
        $count = $g_object_db->count( $f );
        
        $page_number = $id;
        // Нулевая страница не должна быть указана явно.
        if( $page_number === '0' ) return proceed_404( $page_url );
        // Неуказанная страница соответствует нулевой.
        if( $page_number === '' ) $page_number = 0;
        if( is_numeric( $page_number ) )
        {
            if( 0 > $page_number || ( $count && $page_number > ( ceil( $count / OBJECT_ON_PAGE ) - 1 ) ) )
            {
                return proceed_404( $page_url );
            }
        }
    
        $list = array();
        $g_object_db->load_list( $list, $f, ' order by InsertStamp desc'
            . ' limit ' . $page_number * OBJECT_ON_PAGE . ', ' . OBJECT_ON_PAGE );

        foreach( $list as $v )            
        {
            $tpl->set_var( 'ITEM_URL', $g_catalog->current_path_url( array( $v->code() ) ) );
            $v->place( $tpl, 'item_' );
            $tpl->parse( 'item__', 'item_', true );
        }
        
        $body .= $tpl->parse( 'c', 'page_' );

        // Добавить листалку.
        if( $count > OBJECT_ON_PAGE )
        {
            $pager = new Pager( ceil( $count / OBJECT_ON_PAGE ), 
                $page_number, OBJECT_ON_PAGE );
            $body .= $pager->get_html();
        }
    }
    else $body = proceed_404( $g_catalog->current_path_url() );
    
    return $body;
}


function proceed_object_index( $page_code )
{
    global $g_catalog, $g_object_db;

    $body = '';
    
    $page = $g_catalog->page_by_code( $page_code );
    $path_to_object = $g_catalog->get_path_to( $page->id() );

    // Горячие предложения.        
    $tpl = new Template();
    $tpl->set_file( 'page_', PATH_TO_TPL . 'object_list_special.tpl' );
    $tpl->set_block( 'page_', 'item_', 'item__' );

//    $tpl->set_var( 'OBJECT_URL', $g_catalog->path_url( $path_to_object ) );

    $f = $g_object_db->filter();
    $f->hot();
    $list = array();
    $g_object_db->load_list( $list, $f, ' order by InsertStamp' );

    foreach( $list as $v )            
    {
        $path_to_object = $g_catalog->get_path_to( $v->page()->id() );
        
        $tpl->set_var( 'ITEM_URL', $g_catalog->path_url( $path_to_object, array( $v->code() ) ) );
        $v->place( $tpl, 'item_' );
        $tpl->parse( 'item__', 'item_', true );
    }
    
    $body .= $tpl->parse( 'c', 'page_' );

    // Новые предложения.        
    $tpl = new Template();
    $tpl->set_file( 'page_', PATH_TO_TPL . 'object_list_new.tpl' );
    $tpl->set_block( 'page_', 'item_', 'item__' );

//    $tpl->set_var( 'OBJECT_URL', $g_catalog->path_url( $path_to_object ) );

    $f = $g_object_db->filter();
    $list = array();
    $g_object_db->load_list( $list, $f, ' order by InsertStamp desc limit ' . OBJECT_ON_INDEX );

    foreach( $list as $v )            
    {
        $path_to_object = $g_catalog->get_path_to( $v->page()->id() );
        
        $tpl->set_var( 'ITEM_URL', $g_catalog->path_url( $path_to_object, array( $v->code() ) ) );
        $v->place( $tpl, 'item_' );
        $tpl->parse( 'item__', 'item_', true );
    }
    
    $body .= $tpl->parse( 'c', 'page_' );
    
    return $body;
}


?>
