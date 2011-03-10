<?php


/*

    class News.

*/


include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );
include_once( PATH_TO_ROOT . 'inc/catalog.class.php' );


class NewsDB 
    extends DBObjectDB
{
    var $code;
    var $title;
    var $description;
    var $keywords;
    var $name;
    var $announce;
    var $text;
    var $image;
    var $news_stamp;

    // Constructor
    function NewsDB( &$db, $host = HOST_CODE )
    {
        parent::DBObjectDB( $db, $host );

        $this->code         = new FieldCode( 'Code', 'Код страницы' );
        $this->title        = new FieldTitle();
        $this->description  = new FieldString( 'Description', 'Description' );
        $this->keywords     = new FieldString( 'Keywords', 'Keywords' );
        $this->name         = new FieldString( 'Name', 'Ззаголовок новости' );
        $this->announce     = new FieldText( 'Announce', 'Анонс', 'Basic' );
        $this->text         = new FieldText( 'Text', 'Полный текст' );
        $this->image        = new FieldImage( 'Image', 'Изображение', array( 0, 0 ), NULL );
        $this->news_stamp   = new FieldTimestamp( 'NewsStamp', 'Дата новости' );
        
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
            $this->image        ,
            $this->news_stamp   ,
        ) );
    }
    
    function table_name() { return 'dwNews'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new News( NULL, $data );
    }

    function filter()
    {
        $f = new NewsFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
    
    // Возвращает идентификатор объекта с указанными полем PageCode.
    function id_by_code( $page_code = '' )
    {
        $query = 'select ID from ' . $this->table_name()
            . ' where ' . $this->code->name() . ' = ' . wrap_sql_type( $page_code )
            . ' and Host = ' . wrap_sql_type( $this->m_host_code );
        
        $this->m_db->Query( $query );
        return $this->m_db->NextRecord() ? $this->m_db->F( 0 ) : NULL;
    }
}


class NewsFilter
    extends DBObjectFilter
{
    var $m_id_in = NULL;
    var $m_code = NULL;
    var $m_menu = NULL;
    var $m_full_text_search = NULL;
    
    function NewsFilter( &$db )
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
        
        if( isset( $this->m_code ) )
        {
            $where[] = $this->m_db->code->name() . ' = ' 
                . wrap_sql_type( $this->m_code );
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
    function code( $value ) { $this->m_code = $value; }
    function menu( $value ) { $this->m_menu = $value; }
    function full_text_search( $v ) { $this->m_full_text_search = $v; }
}


class NewsImpl extends DBObject
{
    function class_title() { return 'новость'; }
    function page_title() { return $this->data( $this->m_db->title ); }
    function title() { return $this->data( $this->m_db->name ); }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;

        if( $this->data( $this->m_db->code ) == '' )
        {
            $errors[] = 'Не указан код страницы новости!';
        }
        
        $errors = array_merge( $errors, $this->m_db->image->check() );
                                    
        return implode( '<br />', $errors );
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
        $rows[ $this->m_db->name->name() ] = new LinkField( 'Название' );
        $rows[ $this->m_db->code->name() ] = new LinkField( 'Код' );
        $rows[ $this->m_db->news_stamp->name() ] = new Field( 'Дата' );
        
        $default_sort = $this->m_db->code->name();
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        parent::get_row_data( $data );
        $data[] = $this->title();
        $data[] = $this->data( $this->m_db->code );
        $data[] = date( 'd.m.Y', sql_timestamp_to_time( $this->data( $this->m_db->news_stamp ) ) );
            
        return $data;
    }

    function code() { return $this->data( $this->m_db->code ); }
}


global $db;
$g_news_db = new NewsDB( $db, HOST_CODE );


class News extends NewsImpl
{
    function News( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_news_db;

        // Особое поведение: статические страницы можно создавать
        // по значению полю PageCode.        
        if( !is_numeric( $id ) && !is_null( $id ) )
        {
            $id = $g_news_db->id_by_code( $id );
            if( is_null( $id ) ) { unset( $this ); return; }
        }
        
        parent::NewsImpl( $g_news_db, $id, $data, $from_form );
    }
}


?>
