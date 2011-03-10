<?php


include_once( PATH_TO_ROOT . 'inc/func.php' );
include_once( PATH_TO_ROOT . 'lib/template.inc.php' );


function create_menu( $menu_tree, $tpl_files, $level = 0 )
{
    global $g_catalog;

    // Если нет ни одного пункта меню, не заморачиваться даже с шаблоном.
    if( !$menu_tree ) return '';
    
    $tpl = new Template();
    $tpl->set_file( 'm', $tpl_files[ $level ] );
    $tpl->set_block( 'm', 'item', 'item_' );
    $tpl->set_block( 'item', 'active', 'active_' );
    $tpl->set_block( 'item', 'inactive', 'inactive_' );
    $tpl->set_block( 'item', 'delimiter', 'delimiter_' );
    
    $tpl->set_var( 'SUBMENU', '' );

    $count = 0;
    $first = 1;
    foreach( $menu_tree as $v )
    {
        $url = $g_catalog->path_url( $v[ 'Path' ] );
        $tpl->set_var( '__EXT', $url == PATH_DELIMITER ? '' : HTML_EXTENSION );
        $tpl->set_var( 'URL', $url );
        $tpl->set_var( 'TEXT', $v[ 'Page' ]->menu_title() );
        $v[ 'Page' ]->place( $tpl, 'page_' );
        $tpl->set_var( 'COUNT', ++$count );
        
        $classes = array();
        if( $first ) $classes[] = 'first';
        if( $count == count( $menu_tree ) ) $classes[] = 'last';
        $tpl->set_var( 'CLASS', implode( ' ', $classes ) );
                
        if( $first ) $tpl->set_var( 'delimiter_', '' );
        else $tpl->parse( 'delimiter_', 'delimiter' );
        $first = 0;

        if(    isset( $tpl_files[ $level + 1 ] ) 
//            && $v[ 'Active' ]
          )
        {
            $submenu = '';
            
            if( $v[ 'Submenu' ] )
            {
                $submenu = create_menu( $v[ 'Submenu' ], $tpl_files, $level + 1 );
            }
            
            $tpl->set_var( 'SUBMENU', $submenu );
        }

        $tpl->set_var( 'active_', '' );
        $tpl->set_var( 'inactive_', '' );
        if( $v[ 'Active' ] ) $tpl->parse( 'active_', 'active' );
        else $tpl->parse( 'inactive_', 'inactive' );
        
        $tpl->parse( 'item_','item', true );
    }
        
    $menu = $tpl->parse( 'C', 'm' );
    unset( $tpl );
    
    return $menu;
}


?>
