<?php


include_once( PATH_TO_ROOT . 'inc/func.php' );


class AssociativeList
{
    var $db;

    // Таблица списков.
    var $table = '';
    
    // Поля таблицы.
    var $list_id = '';
    var $object_id = '';
    
    function AssociativeList( $name1, $name2 )
    {
        global $db;
        $this->db = $db;
    
        $names = array( $name1, $name2 );
        sort( $names );
        $this->table = 'dwAssoc' . $names[ 0 ] . $names[ 1 ];
        $this->list_id = $name1 . 'ID';
        $this->object_id = $name2 . 'ID';
    }

    // Возвращает запрос на выборку списка объектов.
    function select( $ids )
    {
        return 'select ' . $this->object_id
            . ' from ' . $this->table
            . ' where ' . $this->list_id . ' in (' 
            . implode( ',', $ids ) . ')'
            ;
    }
        
    // Возвращает список ID объектов, соответствующих объекту с $id.
    function load_list( &$list, $id )
    {
        if( !isset( $id ) ) return;
    
        $query = $this->select( array( $id ) );
        $this->db->Query( $query );
        
        while( $obj = $this->db->FetchArray() )
        {
            $list[] = $obj[ $this->object_id ];
        }
    }
    
    // Записывает список объектов.
    function save_list( $id, $list )
    {
        $this->delete_list( $id );
        
        if( !$list ) return;
        
        $values = array();
        foreach( $list as $v )
        {
            $values[] = '(' 
                . wrap_sql_type( $id ) . ',' 
                . wrap_sql_type( $v ) . ')';
        }
        
        $query = 'insert into ' . $this->table
            . ' (' . $this->list_id . ',' . $this->object_id 
            . ') values ' . implode( ',', $values )
            ;
        
        $this->db->Query( $query );
    }
    
    // Удаляет список сервисов.
    function delete_list( $id )
    {
        $query = 'delete from ' . $this->table
            . ' where ' . $this->list_id . ' = ' 
            . wrap_sql_type( $id )
            ;
        
        $this->db->Query( $query );
    }
}


?>
