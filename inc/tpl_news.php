<?php


include_once( PATH_TO_ROOT . 'inc/news.class.php' );
include_once( PATH_TO_ROOT . 'inc/tpl_static_page.php' );


define( 'NEWS_ON_INDEX', 5 );


function proceed_news( &$page )
{
    global $g_catalog, $g_news_db;

    $body = '';

    $id = $g_catalog->get_next_param( '' );
    
    $f = $g_news_db->filter();
    $f->code( $id );

    if( $id && $g_news_db->count( $f ) )
    {
        $obj = new News( $id );
        
        $tpl = new Template();
        $tpl->set_file( 'item_', PATH_TO_TPL . 'news_one.tpl' );
        
        $tpl->set_var( 'URL', $g_catalog->current_path_url() );
        $page->place( $tpl, 'page_' );
        $obj->place( $tpl, 'item_' );
        
        $body = $tpl->parse( 'c', 'item_' );
        
        $g_catalog->path_add( $obj->title(), $id );
        
        $page = new StaticPageSurrogate( $page, $obj );
    }
    else if( !$id )
    {
        $body = proceed_static_page( $page, false );
        
        // Отобразить список.
        $tpl = new Template();
        $tpl->set_file( 'page_', PATH_TO_TPL . 'news_list.tpl' );
        $tpl->set_block( 'page_', 'item_', 'item__' );
        
        $tpl->set_var( 'URL', $g_catalog->current_path_url() );
        $page->place( $tpl, 'page_' );

        $f = $g_news_db->filter();
        $list = array();
        $g_news_db->load_list( $list, $f, ' order by NewsStamp desc' );

        foreach( $list as $v )            
        {
            $v->place( $tpl, 'item_' );
            $tpl->set_var( 'ITEM_URL', $g_catalog->path_url( 
                $g_catalog->get_path_to( $page->id() ), array( $v->code() ) ) );
            $tpl->parse( 'item__', 'item_', true );
        }
        
        $body .= $tpl->parse( 'c', 'page_' );
    }
    else $body = proceed_404( $g_catalog->current_path_url() );
    
    return $body;
}


function proceed_news_index( $page_code )
{
    global $g_catalog, $g_news_db;

    $body = '';
    
    $page = $g_catalog->page_by_code( $page_code );
        
    $tpl = new Template();
    $tpl->set_file( 'page_', PATH_TO_TPL . 'news_list_index.tpl' );
    $tpl->set_block( 'page_', 'item_', 'item__' );

    $path_to_news = $g_catalog->get_path_to( $page->id() );
    $tpl->set_var( 'NEWS_URL', $g_catalog->path_url( $path_to_news ) );

    $f = $g_news_db->filter();
    $list = array();
    $g_news_db->load_list( $list, $f, ' order by NewsStamp desc limit ' . NEWS_ON_INDEX );

    foreach( $list as $v )            
    {
        $v->place( $tpl, 'item_' );
        $tpl->set_var( 'ITEM_URL', $g_catalog->path_url( $path_to_news, array( $v->code() ) ) );
        $tpl->parse( 'item__', 'item_', true );
    }
    
    $body .= $tpl->parse( 'c', 'page_' );
    
    return $body;
}


?>
