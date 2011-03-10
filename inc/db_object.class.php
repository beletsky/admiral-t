<?php


ini_set( 'display_errors', 'On' );


include_once( PATH_TO_ROOT . 'inc/field.class.php' );
//include_once( PATH_TO_ROOT . 'inc/edit_object.class.php' );


/*

    class DBObject.

*/


class DBObjectDB
{
    var $m_db;
    var $m_host_code = NULL;
    var $m_id;
    var $m_host;
    var $m_fields;
    var $m_non_db_fields;
    
    // Constructor
    function DBObjectDB( &$db, $host_code = HOST_CODE )
    {
        $this->m_db = &$db;
        $this->m_host_code = $host_code;
        unset( $this->m_fields );
        
        $this->m_id = new FieldID();
        $this->m_host = new FieldHost( $this->m_host_code );
    }
    
    function fields_list()
    {
        return array( $this->m_id, $this->m_host );
    }
    
    function non_db_fields_list()
    {
        return array();
    }

    function filter()
    {
        $f = new DBObjectFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
    
    // Возвращает запрос на выборку данных.
    function select( $filter = NULL )
    {
        if( isset( $filter ) ) return $filter->full_select();
        
        $f = $this->filter();
        return $f->full_select();
    }
        
    // Возвращает данные объекта с указанным id.
    function load( $id )
    {
        $f = $this->filter();
        $f->id( $id );
        $query = $this->select( $f );
            
        $this->m_db->query( $query );
        return $this->m_db->FetchArray();
    }
    
    // Заполняет список объектами.
    function load_list( &$list, $filter = NULL, $custom_order_limit = '' )
    {
        $query = $this->select( $filter ) . ' ' . $custom_order_limit;
        $r = $this->m_db->Query( $query );

        while( $obj = $this->m_db->FetchArray() )
        {   
            $list[] = $this->create_object( $obj );
            $this->m_db->SetQueryId( $r );
        }
    }
    
    // Удаляет объект из БД.
    function delete( $in_select )
    {
        $query = 'delete from ' . $this->table_name()
            . ' where ' . $this->m_id->name() 
            . ' in ( ' . sql_subselect( $in_select ) . ' )';
            
        $this->m_db->Query( $query );
    }
    
    // Добавляет новую запись в таблицу.
    function insert( &$data )
    {
        foreach( $this->fields() as $v )
        {
            $fields[] = $v->name();
            $values[] = $v->value_sql( $data[ $v->name() ] );
        }
    
        $query = 'insert into ' . $this->table_name()
            . ' ( ' 
            . implode( ', ', $fields )
            . ' ) values ( ' 
            . implode( ', ', $values ) 
            . ' )'
        ;
        $this->m_db->Query( $query );
                
        return $this->m_db->GetInsertId();
    }
    
    // Изменяет запись.
    function update( $data )
    {
        foreach ( $this->fields() as $v )
        {
            $values[] = $v->name() . ' = ' . $v->value_sql( $data[ $v->name() ] );
        }
        
        $query = 'update ' . $this->table_name()
            . ' set ' . implode( ', ', $values ) 
            . ' where ' . $this->m_id->name() . ' = ' . $data[ $this->m_id->name() ];
            
        $this->m_db->Query( $query );
    }
    
    // Проверяет наличие в БД объекта с указанным id.
    function exists( $filter )
    {
        $query = $filter->select();
            
        $this->m_db->Query( $query );
        if( $this->m_db->FetchArray() ) return true;
        
        return false;
    }
    
    // Проверяет наличие в БД объекта с указанным id.
    function exists_id( $id, $id_filter = NULL )
    {
        if( isset( $id_filter ) ) $filter = clone $id_filter;
        else $filter = $this->filter();
        
        $filter->id( $id );
        $query = $filter->select();
            
        $this->m_db->Query( $query );
        if( $this->m_db->FetchArray() ) return true;
        
        return false;
    }

    // Возвращает общее количество объектов в БД.
    function count( $filter )
    {
        $where = $filter->where();
    
        $query = 'select count( * ) from ' . $this->table_name()
            . ( $where ? ' where ' . $where : '' )
            ;
        
        $this->m_db->Query( $query );
        return $this->m_db->NextRecord() ? $this->m_db->F( 0 ) : 0;
    }
        
    // Проверяет наличие в переданных данных всех используемых в таблице колонок.
    function check_fields( $data )
    {
        foreach( $this->fields() as $v )
        {
            if( !isset( $data[ $v->name() ] ) )
            {
                return 'Отсутствует поле ' . $v->name() . '!';
            }
        }
    }
    
    // Формирует список колонок.
    function fields()
    {
        if( !isset( $this->fields_list ) )
        {
            $this->fields_list = $this->fields_list();
        }
        
        return $this->fields_list;
    }
    
    // Формирует список колонок.
    function non_db_fields()
    {
        if( !isset( $this->non_db_fields_list ) )
        {
            $this->non_db_fields_list = $this->non_db_fields_list();
        }
        
        return $this->non_db_fields_list;
    }

    // Дополняет неустановленные данные объекта данными по умолчанию.
    function set_needed_defaults( &$data )
    {
        $default_data = array();
        $this->field_default_data( $default_data );
        
        foreach( $default_data as $k => $v )
        {
            if( !isset( $data[ $k ] ) ) $data[ $k ] = $v;
        }
    }
        
    // Abstract.
    function table_name() { return 'dwObject'; }

    // Возвращает данные по умолчанию для пустого объекта.
    function field_default_data( &$data )
    {
        foreach( $this->fields() as $v )
        {
            $data[ $v->name() ] = $v->value_default();
        }
    }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
//        return new Object( NULL, $data );
    }
}


class DBObjectFilter
{
    var $m_db;
    
    var $m_table;
    var $m_fields = array();
    var $m_group = array();
    var $m_having = array();
    var $m_order_by = array();
    var $m_limit = array();
    
    var $m_id;
    var $m_host;
    
    function DBObjectFilter( &$db )
    {
        $this->m_db = &$db;
    }

    function select()
    {
        $fields = $this->m_fields;
        if( !$fields )
        {
            foreach( $this->m_db->fields() as $v ) $fields[] = $v->name();
        }
    
        $where = $this->where();
        $group = $this->group();
        $having = $this->having();
    
        return 'SELECT '
            . implode( ', ', $fields )
            . ' FROM '
            . $this->m_db->table_name()
            . ( $where  ? ' WHERE ' . $where : '' )
            . ( $group  ? ' GROUP BY ' . $group : '' )
            . ( $having ? ' HAVING ' . $having : '' )
        ;
    }

    function full_select()
    {
        $order_by = $this->order_by();
        $limit = ( $this->m_limit ? 
            ' LIMIT ' . $this->m_limit[ 0 ] . ', ' . $this->m_limit[ 1 ] 
            : '' );
    
        return $this->select()
            . ( $order_by ? ' ORDER BY ' . $order_by : '' )
            . $limit
        ;
    }
        
    function group() { return implode( ', ', $this->m_group ); }
    function order_by() { return implode( ', ', $this->m_order_by ); }
    function having() { return implode( ', ', $this->m_having ); }

    // Возвращает сформированную строку условий.    
    function where_array( $prefix = '' )
    {
        $where = array();
        
        if( isset( $this->m_id ) )
        {
            $where[] = $prefix . $this->m_db->m_id->name()
                . ' = ' . $this->m_db->m_id->value_sql( $this->m_id );
        }
        
        if( isset( $this->m_host ) )
        {
            $where[] = $prefix . $this->m_db->m_host->name()
                . ' = ' . $this->m_db->m_host->value_sql( $this->m_host );
        }
        
        return $where;
    }
    function where( $prefix = '' ) 
        { return implode( ' and ', $this->where_array( $prefix ) ); }
    
    function id( $v ) { $this->m_id = $v; }
    function host( $v ) { $this->m_host = $v; }
    
    function sort( &$field, $dir = '' )
    {
        $this->m_order_by[] = $field->name() . ( $dir ? ( ' ' . $dir ) : '' );
    }
    
    function limit( $from, $count )
    {
        $this->m_limit = array( 0 => $from, 1 => $count );
    }
}


class DBObject
{
    var $m_db;
    var $data_array = NULL;

    function DBObject( &$db, $id = NULL, $data = NULL, $from_form = false )
    {
        $this->m_db = &$db;
        $this->data_array = NULL;
        
        // Если переданы данные, используем только их.        
        if ( isset( $data ) )
        {
            if ( $from_form ) $this->convert_from_form( $data );
            else $this->data_array = $data;
        }
        // Иначе если указан номер, загружаем данные из БД.
        else if( isset( $id ) )
        {
            $f = $this->m_db->filter();
            $f->id( $id );
            if( $this->m_db->exists( $f ) )
            {
                $this->data_array = $this->m_db->load( $id );
            }
            else
            {
                return 'Constructor ' . $this->class_title() 
                    . ' : Объект ' . $id 
                    . ' отсутствует в базе данных!';
            }
        }
        // Иначе создается новый пустой объект.
        else $this->m_db->field_default_data( $this->data_array );
                
        return '';
    }

    function data( $field, $v = NULL ) 
    { 
        if( !is_null( $v ) )
        {
            $this->data_array[ $field->name() ] = $v;
        }
        
        if( !isset( $this->data_array[ $field->name() ] ) ) return NULL;
        
        return $field->value( $this->data_array[ $field->name() ] );
    }
        
    // Записывает текущее состояние объекта в БД.
    function save()
    {
        if( $this->id() ) return $this->m_db->update( $this->data_array );
        else
        {
            $this->data( $this->m_db->m_id, 
                $this->m_db->insert( $this->data_array ) );
            return 1;
        }
    }
    
    // Удаляет объект из БД.
    function delete()
    {
        // Если известен id объекта, удалить его из БД.
        if( $id = $this->id() )
        {
            return $this->m_db->delete( $id );
        }
        
        // Иначе объект ещё не был записан, и удалять ничего не надо.
        return '';
    }

    // Удаляет из указанного по имени поля файл с указанным именем.
    function delete_file( $field_name, $filename )
    {
        foreach( $this->m_db->fields() as $v )
        {
            if( $v->name() != $field_name ) continue;
            
            $this->data( $v, $v->delete_file( $this->data( $v ), $filename ) );
        }
    }
                
    // Возвращает id объекта или NULL, если данные об объекте отсутствуют.
    function id()
    {
        return $this->data( $this->m_db->m_id );
    }

    // Размещает данные объекта в шаблоне.
    function place( &$tpl, $block = '' )
    {
        $fields = array_merge( $this->m_db->fields(), $this->m_db->non_db_fields() );
    
        foreach( $fields as $v )
        {
            $key = $v->name();
            
            // Условные блоки --- открыть.
            $key_block = strtolower( $block . $key . '_' );
            if( $tpl->has_block( $block ) )
            {
                if( $tpl->has_block( $key_block ) )
                {
                    $tpl->set_var( $key_block . '_', '' );
                }
                else $tpl->set_block( $block, $key_block, $key_block . '_' );
            }
            
            $data_exists = $v->place( $this, $tpl, $block );
            
            // Условные блоки --- закрыть.
            if( $tpl->has_block( $key_block ) )
            {
                if( $data_exists ) $tpl->parse( $key_block . '_', $key_block );
                else $tpl->set_var( $key_block . '_', '' );
            }
        }
    }

    // Конструирует форму для исправления данных.
    function get_form( $parent_page, $prefix, $action, $start_clean = false )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_form.tpl' );

        // Добавить в форму содержимое полей.
        $fields = array();
        foreach( $this->m_db->fields() as $v )
        {
            $fields[] = $v->get_form( $this, $prefix );
        }
        
        foreach( $this->m_db->non_db_fields() as $v )
        {
            $fields[] = $v->get_form( $this, $prefix );
        }
        
        $tpl->set_var( 'FIELDS', implode( '', $fields ) );
        
        // Установить значения общих переменных.
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'BUTTON_CAPTION', 
            ( $action == ACT_ADD ? 'Добавить' : 'Изменить' )
            . ' ' . $this->class_title() );
        $tpl->set_var( 'ACTION', $action );
        $tpl->set_var( 'PARENT', $parent_page );
        $tpl->set_var( 'ID', $this->id() );
        $tpl->set_var( 'PATH_TO_ADMIN', PATH_TO_ADMIN );
                    
        return $tpl->parse( 'C', 'f' );
    }

    // Преобразует данные, возвращенные из формы, в форму,
    // используемую для хранения.
    function convert_from_form( $form )
    {
        foreach( $this->m_db->fields() as $v )
        {
            $this->data( $v, $v->convert_from_form( $form ) );
        }
        foreach( $this->m_db->non_db_fields() as $v )
        {
            $this->data( $v, $v->convert_from_form( $form ) );
        }
        
        $this->m_db->set_needed_defaults( $this->data_array );
    }

    // Добавляет в таблицу строку с данными объекта.
    function setup_row( &$table, $edit_tpl, $delete_tpl )
    {
        $data = array();
        $this->get_row_data( $data );
        // Ссылками на редактирование объекта являются два первых поля.
        for( $i = 0; $i < 2; ++$i )
        {
            $data[ $i ] = str_replace( array( '{ID}', '{TITLE}' ), 
                array( $this->id(), $data[ $i ] ), $edit_tpl );
        }
        // Последним полем всегда идет список действий с объектом.
        $data[] = str_replace( array( '{ID}' ), array( $this->id() ), 
            $delete_tpl );
    
        $table->SetRow( $data );
    }
    
    // Abstract.
    // Возвращает название класса объекта в винительном падеже.
    function class_title() { return 'объект'; }
    
    // Abstract.
    // Возвращает название объекта.
    function title() { return 'Object'; }
    
    // Abstract.
    // Возвращает title для объекта.
    function page_title() { return 'Object'; }

    // Abstract.    
    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        if ( ( $r = $this->m_db->check_fields( $this->data_array ) ) != '' )
        {
            return 'DBObject::check() : ' . $r;
        }
        
        return '';
    }
    
    // Static.
    // Abstract.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        $rows[ $this->m_db->m_id->name() ] = new Field( 'ID', true, true );
        
        $default_sort = $this->m_db->m_id->name();
    }
    
    // Abstract.
    // Возвращает массив данных об объекте для отображения в таблице.
    function get_row_data( &$data )
    {
        $data[] = $this->id();
    }
}


?>
