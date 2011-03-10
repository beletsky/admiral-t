<?php


ini_set( 'display_errors', 'On' );


/*

    class Object.

*/


include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );


class ObjectDB
    extends DBObjectDB
{
    var $code;
    var $page;
    var $title;
    var $description;
    var $keywords;
    var $name;
    var $hot_flag;
    var $region;
    var $address;
    var $cost;
    var $announce;
    var $text;
    var $image;
    var $insert_stamp;

    // Constructor
    function ObjectDB( &$db, $host = HOST_CODE )
    {
        parent::DBObjectDB( $db, $host );

        $this->code         = new FieldCode( 'Code', 'Код страницы' );
        $this->page         = new FieldStaticPageFromMenu( 'Page', 'Раздел', array( 'object' ) );
        $this->title        = new FieldTitle();
        $this->description  = new FieldString( 'Description', 'Description' );
        $this->keywords     = new FieldString( 'Keywords', 'Keywords' );
        $this->name         = new FieldString( 'Name', 'Название' );
        $this->hot_flag     = new FieldFlag( 'HotFlag', 'Горячее предложение' );
        $this->region       = new FieldString( 'Region', 'Местоположение' );
        $this->address      = new FieldString( 'Address', 'Точный адрес' );
        $this->cost         = new FieldString( 'Cost', 'Цена' );
        $this->announce     = new FieldText( 'Announce', 'Анонс', 'Basic' );
        $this->text         = new FieldText( 'Text', 'Текст' );
        $this->image        = new FieldImage( 'Image', 'Изображение', array( 296, 0 ), array() );
        $this->insert_stamp = new FieldTimestamp( 'InsertStamp', 'Добавлен', time_to_sql_timestamp( time() ) );
    }
    
    function fields_list()
    {
        return array_merge( parent::fields_list(), array(
            $this->code        ,
            $this->page        ,
            $this->title       ,
            $this->description ,
            $this->keywords    ,
            $this->name        ,
            $this->hot_flag    ,
            $this->region      ,
            $this->address     ,
            $this->cost        ,
            $this->announce    ,
            $this->text        ,
            $this->image       ,
            $this->insert_stamp
        ) );
    }
    
    function table_name() { return 'dwObject'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new Object( NULL, $data );
    }

    function filter()
    {
        $f = new ObjectFilter( $this );
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


class ObjectFilter
    extends DBObjectFilter
{
    var $code = NULL;
    var $page = NULL;
    var $hot = NULL;
    var $full_text_search = NULL;
    
    function ObjectDBFilter( &$db )
    {
        parent::DBObjectFilter( $db );
    }

    // Возвращает сформированную строку условий.    
    function where_array( $prefix = '' )
    {
        $where = parent::where_array( $prefix );
        
        if( isset( $this->code ) )
        {
            $where[] = $this->m_db->code->name() . ' = ' 
                . $this->m_db->code->value_sql( $this->code );
        }
        
        if( isset( $this->page ) )
        {
            $where[] = $this->m_db->page->name() . ' = ' 
                . $this->m_db->page->value_sql( $this->page );
        }
        
        if( isset( $this->hot ) )
        {
            $where[] = $this->m_db->hot_flag->name() . ' = ' 
                . ( $this->hot ? '1' : '0' );
        }
        
        if( isset( $this->full_text_search ) )
        {
            $f = array( $this->m_db->title, $this->m_db->description, 
                $this->m_db->keywords, $this->m_db->name, 
                $this->m_db->announce, $this->m_db->text );
            foreach( $f as $k => $v ) $f[ $k ] = $prefix . $v->name();
        
            $where[] = 'MATCH( ' . implode( $f, ', ' )
                . ' ) AGAINST ( ' 
                . wrap_sql_type( $this->full_text_search )
                . ' )';
        }
        
        return $where;
    }
    
    function code( $v ) { $this->code = $v; }
    function page( $v ) { $this->page = $v; }
    function hot( $v = true ) { $this->hot = $v; }
    function full_text_search( $v ) { $this->full_text_search = $v; }
}


class ObjectImpl
    extends DBObject
{
    function class_title() { return 'объект недвижимости'; }
    function page_title() { return $this->data( $this->m_db->title ); }
    function title() { return $this->data( $this->m_db->name ); }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;

        if ( $this->data( $this->m_db->code ) == '' )
        {
            $errors[] = 'Не указан код страницы!';
        }

        if ( $this->data( $this->m_db->page ) == '' )
        {
            $errors[] = 'Не выбран раздел объекта недвижимости!';
        }

        if ( $this->data( $this->m_db->name ) == '' )
        {
            $errors[] = 'Не указано название объекта недвижимости!';
        }
        
        $errors = array_merge( $errors, $this->m_db->image->check() );
                            
        return implode( '<br />', $errors );
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
        $rows[ $this->m_db->name->name() ] = new LinkField( 'Объект' );
        $rows[ $this->m_db->page->name() ] = new Field( 'Раздел', true, false );
        $rows[ $this->m_db->code->name() ] = new Field( 'Код', true, false );
        $rows[ $this->m_db->hot_flag->name() ] = new Field( 'Гор.', true, false );
        $rows[ $this->m_db->insert_stamp->name() ] = new Field( 'Добавлен', true, false );
        
        $default_sort = $this->m_db->name->name();
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        parent::get_row_data( $data );
        
        $data[] = htmlspecialchars( $this->data( $this->m_db->name ) );
                
        $obj = $this->page();
        $data[] = $obj->title();
        
        $data[] = htmlspecialchars( $this->data( $this->m_db->code ) );
        $data[] = $this->data( $this->m_db->hot_flag ) ? '<center><b>X</b></center>' : '';
        
        $data[] = date( 'd.m.Y', sql_timestamp_to_time( 
            $this->data( $this->m_db->insert_stamp ) ) );
            
        return $data;
    }
    
    function code() { return $this->data( $this->m_db->code ); }
    function page() { return new StaticPage( $this->data( $this->m_db->page ) ); }
}


global $db;
$g_object_db = new ObjectDB( $db );


class Object extends ObjectImpl
{
    function Object( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_object_db;

        // Особое поведение: статические страницы можно создавать
        // по значению полю PageCode.        
        if( !is_numeric( $id ) && !is_null( $id ) )
        {
            $id = $g_object_db->id_by_code( $id );
            if( is_null( $id ) ) { unset( $this ); return; }
        }
        
        parent::ObjectImpl( $g_object_db, $id, $data, $from_form );
    }
}


?>
