<?php

/*
*
*   Создание пейджера.
*
*/


class Pager
{
    private $pages;
    private $page;
    private $page_size;
    private $around_count;

    public function __construct( $pages_count, $current_page, 
        $page_size, $around_count = NULL )
    {
        $this->pages = $pages_count;
        $this->page = $current_page;
        $this->page_size = $page_size;
        $this->around_count = $around_count;
    }

    function place( &$tpl, $block )
    {
        $tpl->set_block( $block, 'item_', 'item__' );
        $tpl->set_block( 'item_', 'active_', 'active__' );
        $tpl->set_block( 'item_', 'inactive_', 'inactive__' );
        $tpl->set_block( 'item_', 'delimiter_', 'delimiter__' );

        $tpl->set_var( 'PAGE_SIZE', $this->page_size );
        
        $first = 0;
        $tpl->set_var( 'URL_FIRST', $this->create_url( $first ) );
        $tpl->set_var( 'FIRST_0', $first );
        $tpl->set_var( 'FIRST', $first + 1 );
        
        $prev = ( $this->page - 1 <  0 ) ? 0 : ( $this->page - 1 );
        $tpl->set_var( 'URL_PREV', $this->create_url( $prev ) );
        $tpl->set_var( 'PREV_0', $prev );
        $tpl->set_var( 'PREV', $prev + 1 );
        
        $next = ( $this->page + 1 >= $this->pages ) ? ( $this->pages - 1 ) : ( $this->page + 1 );
        $tpl->set_var( 'URL_NEXT', $this->create_url( $next ) );
        $tpl->set_var( 'NEXT_0', $next );
        $tpl->set_var( 'NEXT', $next + 1 );
        
        $last = $this->pages - 1;
        $tpl->set_var( 'URL_LAST', $this->create_url( $last ) );
        $tpl->set_var( 'LAST_0', $last );
        $tpl->set_var( 'LAST', $last + 1 );
        
        $from = isset( $this->around_count ) ? $this->page - $this->around_count : 0;
        if( $from < 0 ) $from = 0;
        $to = isset( $this->around_count ) ? $this->page + $this->around_count + 1 : $this->pages;
        if( $to > $this->pages ) $to = $this->pages;
        
        for( $i = $from; $i < $to; $i++ )
        {
            if( $i == $from ) $tpl->set_var( 'delimiter__', '' );
            else $tpl->parse( 'delimiter__', 'delimiter_' );
            
            $tpl->set_var( 'URL_PAGE', $this->create_url( $i ) );
            $tpl->set_var( 'PAGE_0', $i );
            $tpl->set_var( 'PAGE', $i + 1 );
            
            $tpl->set_var( 'inactive__', '' );
            $tpl->set_var( 'active__', '' );
            if( $i == $this->page ) $tpl->parse( 'active__', 'active_', true );
            else $tpl->parse( 'inactive__', 'inactive_', true );
            
            $tpl->parse( 'item__', 'item_', true );
        }
    }

    public function get_html()
    {
        $tpl = new Template();
        $tpl->set_file( 'pager', PATH_TO_TPL . 'pager.tpl' );
        $this->place( $tpl, 'pager' );        
        return $tpl->parse( 'c', 'pager' );
    }
    

    private function create_url( $page_number )
    {
        global $g_catalog;
        if( !$page_number ) return $g_catalog->current_path_url();
        else return $g_catalog->current_path_url( array( $page_number ) );
    }
}

?>
