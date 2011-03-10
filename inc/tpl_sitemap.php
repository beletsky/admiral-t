<?php


include_once( PATH_TO_ROOT . 'inc/news.class.php' );


function proceed_sitemap( &$page )
{
    return proceed_static_page( $page, false )
        . proceed_sitemap_internal( $page );
}
        

function proceed_sitemap_internal( &$current_page, $level = 1, $tree = null )
{
    global $g_catalog, $g_good_db, $g_news_db;
    
    if( is_null( $tree ) ) $tree = $g_catalog->get_tree_from();

    $tpl = new Template();
    $tpl->set_file( 'i', PATH_TO_TPL . 'sitemap_' . $level . '.tpl' );
    $tpl->set_block( 'i', 'item_', 'item__' );
    
    $current_page->place( $tpl, 'page_' );

    if( $current_page->page_code() == 'novosti' )
    {
        $f = $g_news_db->filter();
        $list = array();
        $g_news_db->load_list( $list, $f, ' order by NewsStamp desc' );

        foreach( $list as $v )            
        {
            $v->place( $tpl, 'item_' );
            $url = $g_catalog->path_url( $g_catalog->get_path_to( $current_page->id() ), array( $v->code() ) );
            $tpl->set_var( 'ITEM_URL', $url );
            $tpl->set_var( 'ITEM_SUBMAP', '' );
            $tpl->parse( 'item__', 'item_', true );
        }
    }
        
    for( $i = 0; $i < count( $tree ); ++$i )
    {
        $page_id = $tree[ $i ];
        $page = $g_catalog->page( $page_id );
        if( $page->menu() == 'hidden' ) continue;
        
        $page->place( $tpl, 'item_' );
        $tpl->set_var( 'ITEM_NAME', $page->menu_title() );
        $url = $g_catalog->path_url( $g_catalog->get_path_to( $page_id ) );
        $tpl->set_var( 'ITEM_URL', $url );
        $tpl->set_var( '__EXT', $url == PATH_DELIMITER ? '' : HTML_EXTENSION );
        
        $sub_map = '';
        if( isset( $tree[ $i + 1 ] ) && is_array( $tree[ $i + 1 ] ) )
        {
            $sub_map = proceed_sitemap_internal( $page, $level + 1, $tree[ $i + 1 ] );
            // Пропустить вложенный массив.
            ++$i;
        }
        else $sub_map = proceed_sitemap_internal( $page, $level + 1, array() );
        
        $tpl->set_var( 'ITEM_SUBMAP', $sub_map );
        
        $tpl->parse( 'item__', 'item_', true );
    }

    if( !$tpl->get_var( 'item__' ) ) return '';

    return $tpl->parse( 'c', 'i' );
}


?>
