<?php


ini_set( 'display_errors', 'On' );


/*

    class StaticPageSurrogate.

*/


class StaticPageSurrogate
{
    var $m_pagecode = '';
    var $m_title = '';
    var $m_description = '';
    var $m_keywords = '';
    var $m_name = '';
    var $m_announce = '';
    var $m_text = '';

    function StaticPageSurrogate( $page, &$obj = NULL )
    {
        if( isset( $obj ) ) $this->set_empty_data_from( $obj );
        
        $this->m_pagecode = $page->page_code();
    
        $this->set_empty_data_from( $page );
        $this->m_announce = $page->data( $page->m_db->announce );
        $this->m_text = $page->data( $page->m_db->text );
    }

    function set_empty_data_from( &$obj )
    {
        if( !$this->m_title       && isset( $obj->m_db->title       ) ) $this->m_title = $obj->data( $obj->m_db->title );
        if( !$this->m_description && isset( $obj->m_db->description ) ) $this->m_description = $obj->data( $obj->m_db->description );
        if( !$this->m_keywords    && isset( $obj->m_db->keywords    ) ) $this->m_keywords = $obj->data( $obj->m_db->keywords );
        if( !$this->m_name        && isset( $obj->m_db->name        ) ) $this->m_name = $obj->title();
    }
        
    function set_title( $v ) { $this->m_title = $v; }
    function set_name( $v ) { $this->m_name = $v; }
    function set_announce( $v ) { $this->m_announce = $v; }
    function set_text( $v ) { $this->m_text = $v; }

    function page_code() { return $this->m_pagecode; }
    function page_title() { return $this->m_title; }
    function title() { return $this->m_name; }
        
    function place( &$tpl, $block )
    {
        $tpl->set_var( strtoupper( $block . 'Title' ), $this->m_title );
        $tpl->set_var( strtoupper( $block . 'Description' ), $this->m_description );
        $tpl->set_var( strtoupper( $block . 'Keywords' ), $this->m_keywords );
        $tpl->set_var( strtoupper( $block . 'Name' ), $this->m_name );
        $tpl->set_var( strtoupper( $block . 'Announce' ), $this->m_announce );
        $tpl->set_var( strtoupper( $block . 'Text' ), $this->m_text );
    }
}


/*

    class StaticPage.

*/


include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );


class StaticPageDB extends DBObjectDB
{
    var $code;
    var $title;
    var $description;
    var $keywords;
    var $name;
    var $announce;
    var $text;
    var $menu;
    var $menu_text;
    var $image;

    // Constructor
    function StaticPageDB( &$db, $host = HOST_CODE )
    {
        parent::DBObjectDB( $db, $host );

        $this->code         = new FieldCode( 'PageCode', 'Код страницы' );
        $this->title        = new FieldTitle();
        $this->description  = new FieldString( 'Description', 'Description' );
        $this->keywords     = new FieldString( 'Keywords', 'Keywords' );
        $this->name         = new FieldString( 'Name', 'Название страницы' );
        $this->announce     = new FieldText( 'Announce', 'Анонс', 'Basic' );
        $this->text         = new FieldText( 'Text', 'Текст' );
        $this->menu         = new FieldOption( 'Menu', 'Расположение в меню', 'menus' );
        $this->menu_text    = new FieldString( 'MenuText', 'Название меню' );
        $this->image        = new FieldImage( 'Image', 'Изображение', array( 146, 0 ), NULL );
        
        $edit = new EditFieldHidden( $this->image );
    }
    
    function fields_list()
    {
        return array_merge( parent::fields_list(), array(
            $this->code         ,
            $this->title        ,
            $this->description  ,
            $this->keywords     ,
            $this->name         ,
            $this->announce     ,
            $this->text         ,
            $this->menu         ,
            $this->menu_text    ,
            $this->image
        ) );
    }
    
    function table_name() { return 'dwStaticPage'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new StaticPage( NULL, $data );
    }

    function filter()
    {
        $f = new StaticPageFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
    
    // Возвращает идентификатор объекта с указанными полем PageCode.
    function id_by_page_code( $page_code = '' )
    {
        $query = 'select ID from ' . $this->table_name()
            . ' where ' . $this->code->name() . ' = ' . wrap_sql_type( $page_code )
            . ' and Host = ' . wrap_sql_type( $this->m_host_code );
        
        $this->m_db->Query( $query );
        return $this->m_db->NextRecord() ? $this->m_db->F( 0 ) : NULL;
    }
}


class StaticPageFilter
    extends DBObjectFilter
{
    var $m_id_in = NULL;
    var $m_pagecode = NULL;
    var $m_menu = NULL;
    var $m_full_text_search = NULL;
    
    function StaticPageFilter( &$db )
    {
        parent::DBObjectFilter( $db );
    }
    
    // Возвращает сформированную строку условий.    
    function where_array( $prefix = '' )
    {
        $where = parent::where_array( $prefix );
        
        if( isset( $this->m_id_in ) )
        {
            $where[] = $this->m_db->m_id->name() . ' in ( ' 
                . ( is_array( $this->m_id_in ) 
                    ? implode( ', ', $this->m_id_in ) 
                    : $this->m_id_in )
                . ' )';
        }
        
        if( isset( $this->m_pagecode ) )
        {
            $where[] = $this->m_db->code->name() . ' = ' 
                . wrap_sql_type( $this->m_pagecode );
        }
        
        if( isset( $this->m_menu ) )
        {
            $where[] = $this->m_db->menu->name() . ' = ' 
                . wrap_sql_type( $this->m_menu );
        }
        
        if( isset( $this->m_full_text_search ) )
        {
            $f = array( $this->m_db->title, $this->m_db->name, 
                $this->m_db->announce, $this->m_db->text );
            foreach( $f as $k => $v ) $f[ $k ] = $prefix . $v->name();
        
            $where[] = 'MATCH( ' . implode( $f, ', ' )
                . ' ) AGAINST ( ' 
                . wrap_sql_type( $this->m_full_text_search )
                . ' )';
        }
        
        return $where;
    }
    
    function id_in( $value ) { $this->m_id_in = $value; }
    function pagecode( $value ) { $this->m_pagecode = $value; }
    function menu( $value ) { $this->m_menu = $value; }
    function full_text_search( $v ) { $this->m_full_text_search = $v; }
}


class StaticPageImpl extends DBObject
{
    function class_title() { return 'страницу'; }
    function page_title() { return $this->data( $this->m_db->title ); }
    function title() { return $this->data( $this->m_db->name ); }
    function menu_title()
    { 
        $menu_text = $this->data( $this->m_db->menu_text );
        return $menu_text ? $menu_text : $this->title();
    }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;

        if( $this->data( $this->m_db->code ) == '' )
        {
            $errors[] = 'Не указан код страницы!';
        }

        if( $this->data( $this->m_db->menu ) == '' )
        {
            $errors[] = 'Не указано расположение страницы в меню!';
        }
        
        $errors = array_merge( $errors, $this->m_db->image->check() );
                                    
        return implode( '<br />', $errors );
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
        $rows[ $this->m_db->name->name() ] = new LinkField( 'Страница' );
        $rows[ $this->m_db->menu->name() ] = new Field( 'Меню', true, false );
        $rows[ $this->m_db->code->name() ] = new LinkField( 'Код' );
        
        $default_sort = $this->m_db->code->name();
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        global $g_options;
        $menus = $g_options->GetOptionList( 'menus' );
        
        parent::get_row_data( $data );
        $data[] = $this->title();
        $data[] = $menus[ $this->data( $this->m_db->menu ) ];
        $data[] = $this->data( $this->m_db->code );
            
        return $data;
    }

    function page_code() { return $this->data( $this->m_db->code ); }
    function menu() { return $this->data( $this->m_db->menu ); }
}


global $db;
$g_static_page_db = new StaticPageDB( $db, HOST_CODE );


class StaticPage extends StaticPageImpl
{
    function StaticPage( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_static_page_db;

        // Особое поведение: статические страницы можно создавать
        // по значению полю PageCode.        
        if( !is_numeric( $id ) && !is_null( $id ) )
        {
            $id = $g_static_page_db->id_by_page_code( $id );
            if( is_null( $id ) ) { unset( $this ); return; }
        }
        
        parent::StaticPageImpl( $g_static_page_db, $id, $data, $from_form );
    }
}


class StaticPageTree { function id() { return 0; } }


class FieldStaticPageFromMenu
    extends FieldCode
{
    var $m_menu;
    var $m_options;

    function FieldStaticPageFromMenu( $name, $title, $menu )
    {
        parent::FieldCode( $name, '' );
        $this->m_menu = $menu;
        $edit = new EditFieldOption( $this, $title );
    }

    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $value = $obj->data( $this );
        if( !$value ) return false;
        
        $page = new StaticPage( $value );
        $page->place( $tpl, strtolower( $this->tpl_name( $block ) . '_' ) );
        
        return true;
    }

    function get_options()
    {
        if( is_null( $this->m_options ) )
        {
            global $g_catalog;
            $this->m_options = $g_catalog->make_select_tree( $this->m_menu );
        }
        
        return $this->m_options;
    }
}


class FieldStaticPageHasArticles
    extends FieldCode
{
    function FieldStaticPageHasArticles( $name, $title = '', $default = '' )
    {
        parent::FieldCode( $name, $default );
        $edit = new EditFieldOption( $this, $title );
    }

    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $value = $obj->data( $this );
        if( !$value ) return false;
        
        $page = new StaticPage( $value );
        $page->place( $tpl, strtolower( $this->tpl_name( $block ) . '_' ) );
        
        return true;
    }

    function get_options()
    {
        global $g_static_page_db;
        $f = $g_static_page_db->filter();
        $f->has_articles();
        $list = array();
        $options = $g_static_page_db->load_list( $list, $f );

        $options = array();
        foreach( $list as $v ) $options[] = array( $v->id(), $v->title() );
        
        return $options;
    }
}


?>
