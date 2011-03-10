<?php


include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );


function proceed_static_page( &$page, $show_announces = true )
{
    $tpl = new Template();
    $tpl->set_file( 'item_', PATH_TO_TPL . 'static_page.tpl' );
    $page->place( $tpl, 'item_' );

    $body = $tpl->parse( 'c', 'item_' );

    if( !$show_announces ) return $body;
    
    global $g_catalog;
    
    // Отобразить анонсы вложенных страниц.
    $tree = $g_catalog->get_tree_from( $page->id() );
    if( $tree )
    {
        $tpl = new Template();
        $tpl->set_file( 'i', PATH_TO_TPL . 'static_page_list.tpl' );
        $tpl->set_block( 'i', 'item_', 'item__' );
        
        $tpl->set_var( 'URL', $g_catalog->current_path_url() );
        
        $count = 0;
        foreach( $tree as $id )
        {
            if( is_array( $id ) ) continue;
            
            $tpl->set_var( 'IMG_VARIANT', $count++ % 2 + 1 );
            $tpl->set_var( 'ITEM_URL', $g_catalog->path_url( $g_catalog->get_path_to( $id ) ) );
            
            $g_catalog->page( $id )->place( $tpl, 'item_' );
            $tpl->parse( 'item__', 'item_', true );
        }

        $body .= $tpl->parse( 'c', 'i' );
    }
    
    return $body;
}


function proceed_404( $suggest_url )
{
    global $g_catalog;

    $page = $g_catalog->page_by_code( 'error404' );
    
    $tpl = new Template();
    $tpl->set_template( 'page_', proceed_static_page( $page, false ) );
    
    $tpl->set_block( 'page_', 'suggest_', 'suggest__' );
    if( $suggest_url )
    {
        if( $suggest_url == SITE_ROOT_PATH )
        {
            $tpl->set_var( 'SUGGEST_URL', SITE_ROOT_PATH );
            $tpl->set_var( '__EXT', '' );
        }
        else
        {
            $tpl->set_var( 'SUGGEST_URL', $suggest_url );
        }
        $tpl->parse( 'suggest__', 'suggest_' );
    }

    $g_catalog->path_replace( $g_catalog->get_path_to( $page->id() ) );
        
    header( 'HTTP/1.1 404 Not Found' );
    
    return $tpl->parse( 'C', 'page_', false );
}


?>
