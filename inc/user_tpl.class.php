<?php


/*

    class UserTPL.

*/


include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/edit_object.class.php' );


class UserTPLDB 
    extends DBObjectDB
{
    var $code       = 'Code';
    var $name       = 'Name';
    var $text       = 'Text';

    var $user_tpl_fields;

    // Constructor
    function UserTPLDB( &$db, $host = HOST_CODE )
    {
        parent::DBObjectDB( $db, $host );

        $this->code = new FieldCode( 'Code', 'Код' );
        $this->name = new FieldString( 'Name', 'Название' );
        $this->text = new FieldText( 'Text', 'Текст' );
        $edit = new EditFieldTextArea( $this->text, 'Текст' );
    }
    
    function fields_list()
    {
        return array_merge( parent::fields_list(), array(
            $this->code,
            $this->name,
            $this->text,
        ) );
    }
    
    function table_name() { return 'dwUserTPL'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new UserTPL( NULL, $data );
    }

    function filter()
    {
        $f = new UserTPLFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
}


class UserTPLFilter
    extends DBObjectFilter
{
    var $m_code = NULL;
    
    function UserTPLFilter( &$db )
    {
        parent::DBObjectFilter( $db );
    }

    // Возвращает сформированную строку условий.    
    function where_array( $prefix = '' )
    {
        $where = parent::where_array( $prefix );
        
        if( isset( $this->m_code ) )
        {
            $where[] = $this->m_db->code->name() . ' = ' 
                . wrap_sql_type( $this->m_code );
        }
        
        return $where;
    }
    
    function code( $v ) { $this->m_code = $v; }
}


class UserTPLImpl extends DBObject
{
    function class_title() { return 'пользовательский шаблон'; }
    function title() { return $this->data( $this->m_db->name ); }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;

        if ( $this->data( $this->m_db->code ) == '' )
        {
            return 'Не указан код шаблона!';
        }
        
        if( $this->data( $this->m_db->name ) == '' )
        {
            return 'Не указано название шаблона!';
        }
                            
        return '';
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
        $rows[ $this->m_db->name->name() ] = new LinkField( 'Шаблон' );
        $rows[ $this->m_db->code->name() ] = new Field( 'Код', true, false );
        
        $default_sort = $this->m_db->name->name();
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        parent::get_row_data( $data );
        $data[] = $this->title();
        $data[] = $this->data( $this->m_db->code );
            
        return $data;
    }
    
    function set_template( &$tpl, $handle )
    {
        $tpl->set_template( $handle, $this->data( $this->m_db->text ) );

        global $g_sites, $g_options;
        $tpl->set_var( 'HOST', $g_sites[ HOST_CODE ][ 'Host' ] );
        $tpl->set_var( 'DATE', date( 'd.m.Y', time() ) );
        $tpl->set_var( 'TIME', date( 'H:i:s', time() ) );
        $tpl->set_var( 'EMAIL_MODERATOR', $g_options->GetOption( 'email_moderator' ) );
    }
}


global $db;
$g_user_tpl_db = new UserTPLDB( $db, HOST_CODE );


class UserTPL extends UserTPLImpl
{
    function UserTPL( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_user_tpl_db;

        // Особое поведение: статические страницы можно создавать
        // по значению полю Code.        
        if( !is_numeric( $id ) && !is_null( $id ) )
        {
            $filter = $g_user_tpl_db->filter();
            $filter->code( $id );
            $list = array();
            $g_user_tpl_db->load_list( $list, $filter );
            $obj = $list[ 0 ];
            $id = $obj->id();
        }
        
        parent::UserTPLImpl( $g_user_tpl_db, $id, $data, $from_form );
    }
}


?>
