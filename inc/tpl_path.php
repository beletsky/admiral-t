<?php


function make_path( $show_index = false, $index_name = '' )
{
    global $g_catalog;
    
    $items = array();    
    if( $show_index ) $items[] = array( 'Name' => $index_name, 'URL' => SITE_ROOT_PATH );
    $items = array_merge( $items, $g_catalog->make_path_array() );
        
    $tpl = new Template();
    $tpl->set_file( 'path', PATH_TO_TPL . 'path.tpl' );
    $tpl->set_block( 'path', 'item', 'item_' );
    $tpl->set_block( 'item', 'delimiter', 'delimiter_' );
    $tpl->set_block( 'item', 'active', 'active_' );
    $tpl->set_block( 'item', 'inactive', 'inactive_' );

    $cur = 0;
    $html = array();            
    foreach( $items as $v )
    {
        if( $v[ 'URL' ] && ( $cur + 1 ) != count( $items ) )
        {
            $tpl->set_var( 'URL', ( $show_index && $cur == 0 ) ? SITE_ROOT_PATH : $v[ 'URL' ] . HTML_EXTENSION );
            $tpl->set_var( 'NAME', $v[ 'Name' ] );
            $tpl->set_var( 'active_', '' );
            $tpl->parse( 'inactive_', 'inactive' );
        }
        else
        {
            $tpl->set_var( 'URL', $v[ 'URL' ] );
            $tpl->set_var( 'NAME', $v[ 'Name' ] );
            $tpl->parse( 'active_', 'active' );
            $tpl->set_var( 'inactive_', '' );
        }

        if( $cur ) $tpl->parse( 'delimiter_', 'delimiter' );
        else $tpl->set_var( 'delimiter_', '' );
        
        ++$cur;
        
        $tpl->parse( 'item_', 'item', true );
    }
    
    return $tpl->parse( 'c', 'path' );
}


?>
