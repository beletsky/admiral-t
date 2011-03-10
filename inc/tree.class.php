<?php


class TreeDB
{
    var $db;
    var $fields_list;

    var $object_type    = 'ObjectType';
    var $object_id      = 'ObjectID';
    var $node_id        = 'NodeID';
    var $left           = 'Lft';
    var $right          = 'Rgt';
    
    var $tree_fields;

    // Constructor
    function TreeDB( &$db )
    {
        $this->db = &$db;
        unset( $this->fields_list );
        
        $this->tree_fields = 
        array(
            $this->object_type,
            $this->object_id  ,
            $this->node_id    ,
            $this->left       ,
            $this->right
        );
    }
    
    function table_name() { return 'dwTree'; }
    function fields() { return $this->tree_fields; }
    
    // Формирует список колонок.
    function fields_list()
    {
        if( !isset( $this->fields_list ) )
        {
            $this->fields_list = implode( $this->fields(), ', ' );
        }
        
        return $this->fields_list;
    }

    // Возвращает данные по умолчанию для пустого объекта.
    function field_default_data( &$data )
    {
        $data[ $this->object_type ] = '';
        $data[ $this->object_id   ] = '0';
        $data[ $this->node_id     ] = 0;
        $data[ $this->left        ] = 0;
        $data[ $this->right       ] = 0;
    }
    
    // Добавляет новую запись в таблицу.
    function insert( &$data )
    {
        foreach ( $this->fields() as $col_name )
        {
            $values[] = wrap_sql_type( $data[ $col_name ] );
        }
    
        $query = 'insert into ' . $this->table_name();
        if( count( $this->fields_list() ) )
        {
            $query .= ' ( Host,' . $this->fields_list()
                . ' ) values ( ' . wrap_sql_type( HOST_CODE ) 
                . ',' . implode( $values, ', ' ) . ' )';
        }
        $this->db->Query( $query );
    }

    function select( $filter = NULL, $fields = NULL )
    {
        if( isset( $filter ) ) return $filter->select();
    
        $filter_table = isset( $filter ) ? $filter->table_name() 
            : $this->table_name();
        $filter_where = isset( $filter ) ? $filter->where() : '';
        $filter_order = isset( $filter ) ? $filter->order() : '';
        
        return 'select '
            . ( !is_null( $fields ) 
                ? implode( ', ', $fields )
                : $this->id . ( count( $this->fields_list() ) 
                    ? ', ' . $this->fields_list() 
                    : '' )
              )
            . ' from ' . $filter_table
            . ' where 1=1'
//            . ' where Host = ' . wrap_sql_type( HOST_CODE )
            . ( isset( $filter_where ) ? ' and ' . $filter->where() : '' )
            . $filter_order
            ;
    }
        
    // Возвращает список рейтингов по указанному фильтру.
    function load_list( &$list, $filter = NULL )
    {
        $query = $this->select( $filter );
        $this->db->Query( $query );
        while( $obj = $this->db->FetchArray() ) $list[] = $obj;
    }
    
    // Возвращает массив значений по указанному списку полей.
    function load_fields( $filter )
    {
        $query = $this->select( $filter );
        $this->db->Query( $query );
        $exists = $this->db->NextRecord();
        
        $result = array();
        for( $i = 0; $i < $this->db->NumFields(); ++$i )
        {
            if( $exists ) $result[] = $this->db->F( $i );
            else $result[] = 0;
        }
        return $result;
    }

    // Возвращает общее количество объектов в БД.
    function count( $filter )
    {
        $query = 'select count( * ) from ( ' . $filter->select() . ' ) t'
            ;
        
        $this->db->Query( $query );
        return $this->db->NextRecord() ? $this->db->F( 0 ) : 0;
    }
    
    // Изменяет на 0 номер внутреннего объекта в указанном узле.
    function clear_node( &$data )
    {
        $query = 'update ' . $this->table_name()
            . ' set ' . $this->node_id . ' = 0'
            . ' where ' 
            . $this->object_type . ' = ' . $data[ $this->object_type ]
            . ' and '
            . $this->object_id . ' = ' . $data[ $this->object_id ]
            . ' and '
            . $this->left . ' = ' . $data[ $this->left ]
            . ' and '
            . $this->right . ' = ' . $data[ $this->right ]
            ;
        $this->db->Query( $query );
    }
    
    // Удаляет узлы в указанных пределах.
    function delete_nodes( $object_type, $object_id, $left, $right )
    {
        $query = 'delete from ' . $this->table_name()
            . ' where ' 
            . $this->object_type . ' = ' . wrap_sql_type( $object_type )
            . ' and '
            . $this->object_id . ' = ' . wrap_sql_type( $object_id )
            . ' and '
            . $this->left . ' between ' . $left . ' and ' . $right
            ;
        $this->db->Query( $query );
    }
    
    // Перенумеровывает индексы начиная с указанного на указанную величину.
    function renumber( $object_type, $object_id, $from_right, $count )
    {
        $query = 'update ' . $this->table_name() . ' set ' 
            . $this->left . ' = '
            . ' case when ' . $this->left . ' >= ' . $from_right
            . ' then ' . $this->left . ' + ( ' . $count . ' )'
            . ' else ' . $this->left . ' end'
            . ', '
            . $this->right . ' = '
            . ' case when ' . $this->right . ' >= ' . $from_right
            . ' then ' . $this->right . ' + ( ' . $count . ' )'
            . ' else ' . $this->right . ' end'
            . ' where ' 
            . $this->object_type . ' = ' . wrap_sql_type( $object_type )
            . ' and '
            . $this->object_id . ' = ' . wrap_sql_type( $object_id )
            . ' and '
            . $this->right . ' >= ' . $from_right
            ;
            
//        print_r( $query );
        
        $this->db->Query( $query );
    }
    
    // Перенумеровывает индексы начиная с указанного на указанную величину.
    function renumber_range( $object_type, $object_id, $left, $right, $count )
    {
        $query = 'update ' . $this->table_name() . ' set ' 
            . $this->left . ' = '
            . $this->left . ' + ( ' . $count . ' )'
            . ', '
            . $this->right . ' = '
            . $this->right . ' + ( ' . $count . ' )'
            . ' where ' 
            . $this->object_type . ' = ' . wrap_sql_type( $object_type )
            . ' and '
            . $this->object_id . ' = ' . wrap_sql_type( $object_id )
            . ' and '
            . $this->left . ' >= ' . $left
            . ' and '
            . $this->left . ' <= ' . $right
            . ' and '
            . $this->right . ' >= ' . $left
            . ' and '
            . $this->right . ' <= ' . $right
            ;

//        print_r( $query );
                    
        $this->db->Query( $query );
    }
}


class TreeFilter
{
    var $m_db;
    
    var $m_table;
    var $m_fields = array();
    var $m_group = array();
    var $m_having = array();
    var $m_limit = array();
    
    var $m_object_id;
    var $m_object_type;
    var $m_node_id = NULL;
    var $m_node_id_not = NULL;
    var $m_between_left = NULL;
    var $m_between_right = NULL;
    var $m_contains_left = NULL;
    var $m_contains_right = NULL;
    
    function TreeFilter( &$db, $object_type, $object_id )
    {
        $this->m_db = &$db;
        $this->m_object_type = $object_type;
        $this->m_object_id = $object_id;
        $this->m_table = $this->m_db->table_name();
    }

    function select_statement()
    {
        $where = $this->where();
        $group = $this->group();
        $having = $this->having_pred();
    
        return 'SELECT '
            . ( $this->m_fields ? $this->fields() : $this->m_db->fields_list() )
            . ' FROM '
            . $this->table_name()
            . ( $where  ? ' WHERE ' . $where : '' )
            . ( $group  ? ' GROUP BY ' . $group : '' )
            . ( $having ? ' HAVING ' . $having : '' )
        ;
    }

    function select()
    {
        $limit = ( $this->m_limit ? 
            ' LIMIT ' . $this->m_limit[ 0 ] . ', ' . $this->m_limit[ 1 ] 
            : '' );
    
        return $this->select_statement()
            . ' ORDER BY ' . $this->m_db->left
            . $limit
        ;
    }
        
    function table_name() { return $this->m_table; }
    function fields() { return implode( ', ', $this->m_fields ); }
    function group() { return implode( ', ', $this->m_group ); }
    function having_pred() { return implode( ', ', $this->m_having ); }

    // Возвращает сформированную строку условий.    
    function where( $prefix = '' )
    {
        $where = array();
        
        if( isset( $this->m_object_type ) )
        {
            $where[] = $prefix . $this->m_db->object_type 
                . ' = ' . wrap_sql_type( $this->m_object_type );
        }
        
        if( isset( $this->m_object_id ) )
        {
            $where[] = $prefix . $this->m_db->object_id 
                . ' = \'' . $this->m_object_id . '\'';
        }
        
        if( isset( $this->m_node_id ) )
        {
            $where[] = $prefix . $this->m_db->node_id 
                . ' = ' . $this->m_node_id;
        }
        
        if( isset( $this->m_node_id_not ) )
        {
            $where[] = $prefix . $this->m_db->node_id 
                . ' <> ' . $this->m_node_id_not;
        }
        
        if( isset( $this->m_between_left ) )
        {
            $where[] = $prefix . $this->m_db->left 
                . ' between ' . $this->m_between_left
                . ' and ' . $this->m_between_right;
        }
        
        if( isset( $this->m_contains_left ) )
        {
            $where[] = $prefix . $this->m_contains_left 
                . ' between ' . $this->m_db->left
                . ' and ' . $this->m_db->right
                ;
        }
        
        if( isset( $this->m_contains_right ) )
        {
            $where[] = $prefix . $this->m_contains_right 
                . ' between ' . $this->m_db->left
                . ' and ' . $this->m_db->right
                ;
        }
        
        return implode( ' and ', $where );
    }
    
    function node_id( $value ) { $this->m_node_id = $value; }
    function node_id_not( $value ) { $this->m_node_id_not = $value; }
    function between( $l, $r ) 
    { 
        $this->m_between_left = $l; 
        $this->m_between_right = $r;
    }
    function contains( $l, $r ) 
    { 
        $this->m_contains_left = $l; 
        $this->m_contains_right = $r;
    }
    
    function join_object_id_with( $filter )
    {
        $filter->id( '( ' . $this->table_name() 
            . '.' . $this->m_db->object_id . ' )' );
        $this->m_table .= ' INNER JOIN ' 
            . $filter->table_name() . ' OID ON '
            . $filter->where( 'OID.' );
    }

    function add_field( $value ) { $this->m_fields[] = $value; }
    function group_by( $value ) { $this->m_group[] = $value; }
    function having( $value ) { $this->m_having[] = $value; }
    function limit( $from, $count ) { $this->m_limit = array( $from, $count ); }
}


class Tree
{
    var $m_db;
    var $m_object_type;
    var $m_object_id;
    
    function Tree( $db, &$obj )
    {
        $this->m_db = $db;
        $this->m_object_type = strtolower( get_class( $obj ) );
        $this->m_object_id = $obj->id();
    }

    // Добавляет в дерево корневой узел.
    function add_root_node()
    {
        // Проверить наличие корневой узла.
        $f = $this->filter();
        $f->node_id( 0 );
        if( $this->m_db->count( $f ) ) return;

        $this->add_node_internal( 0, 0 );
    }
    
    // Добавляет в дерево узел к указанному родителю.
    function add_node( $node_id, $parent_id )
    {
        // Получить индекс места вставки.
        $f = $this->filter();
        $f->node_id( $parent_id );
        $f->add_field( $this->m_db->right );
        list( $right ) = $this->m_db->load_fields( $f );

        // Перенумеровать индексы для вставки одного элемента.
        $this->m_db->renumber( $this->m_object_type, $this->m_object_id, $right, 2 );

        // Добавить узел.
        $this->add_node_internal( $node_id, $right );
    }
    
    // Внутренняя функция добавления узла.
    function add_node_internal( $node_id, $from_right )
    {
        $data = array(
            $this->m_db->object_type => $this->m_object_type,
            $this->m_db->object_id   => $this->m_object_id,
            $this->m_db->node_id     => $node_id,
            $this->m_db->left        => $from_right,
            $this->m_db->right       => $from_right + 1
        );
        
        $this->m_db->insert( $data );
    }

    // Помещает ветку, начиная с указанного узла дерева внутрь указанного узла в конец списка.
    function move_node_inside_last( $move_node_id, $parent_node_id )
    {
        list( $move_left, $move_right ) = $this->get_node_left_right( $move_node_id );
        if( ( $move_right - $move_left ) <= 0 ) return false;
        
        list( $after_left, $after_right ) = $this->get_node_left_right( $parent_node_id );
        if( ( $after_right - $after_left ) <= 0 ) return false;

        // Перемещать дерево внутрь какого-то из собственных узлов --- нельзя.
        if( $move_left <= $after_right && $after_right <= $move_right ) return false;

        $gap_size = $move_right - $move_left + 1;
                
        // Высвободить место в конце принимающего узла.
        $this->m_db->renumber( $this->m_object_type, $this->m_object_id, 
            $after_right, $gap_size );

        // При необходимости двинуть индексы.
        if( $move_left >= $after_right )
        {
            $move_left += $gap_size;
            $move_right += $gap_size;
        }
            
        // Перенумеровать сдвигаемый участок.
        $this->m_db->renumber_range( $this->m_object_type, $this->m_object_id, 
            $move_left, $move_right, $after_right - $move_left );
                            
        // Удалить пустой промежуток.
        $this->m_db->renumber( $this->m_object_type, $this->m_object_id, $move_right, -$gap_size );
        
        return true;
    }

    // Помещает ветку, начиная с указанного узла дерева после указанного узла.
    function move_node_after( $move_node_id, $after_node_id )
    {
        list( $move_left, $move_right ) = $this->get_node_left_right( $move_node_id );
        if( ( $move_right - $move_left ) <= 0 ) return false;

        // Специальный случай. Если ветка перемещается "после корня" дерева,
        // переместить её на самом деле в самое начало.
        if( !$after_node_id ) $after_right = 0;
        else
        {
            list( $after_left, $after_right ) = $this->get_node_left_right( $after_node_id );
            if( ( $after_right - $after_left ) <= 0 ) return false;
        }

        // Перемещать дерево после какого-то из собственных узлов --- нельзя.
        if( $move_left <= $after_right && $after_right <= $move_right ) return false;

        $gap_size = $move_right - $move_left + 1;
                
        // Высвободить место после принимающего узла.
        $this->m_db->renumber( $this->m_object_type, $this->m_object_id, 
            $after_right + 1, $gap_size );

        // При необходимости двинуть индексы.
        if( $move_left >= $after_right )
        {
            $move_left += $gap_size;
            $move_right += $gap_size;
        }
            
        // Перенумеровать сдвигаемый участок.
        $this->m_db->renumber_range( $this->m_object_type, $this->m_object_id, 
            $move_left, $move_right, $after_right + 1 - $move_left );
                            
        // Удалить пустой промежуток.
        $this->m_db->renumber( $this->m_object_type, $this->m_object_id, $move_right, -$gap_size );
        
        return true;
    }

    // Удаляет указанный конечный узел дерева.
    // Конечный узел удаляется, для внутреннего возвращается ошибка.
    function delete_leaf_node( $node_id )
    {
        // Получить границы дерева для указанного узла.
        $f = $this->filter();
        $f->node_id( $node_id );
        $list = array();
        $this->m_db->load_list( $list, $f );

        // Если узла с таким номером нет или их больше двух --- ошибка.        
        if( count( $list ) != 1 ) return false;
        $node = $list[ 0 ];
        $left = $node[ $this->m_db->left ];
        $right = $node[ $this->m_db->right ];
        
        if( ( $right - $left ) != 1 ) return false;
        
        // Конечный узел. Удалить полностью.
        $this->m_db->delete_nodes( $this->m_object_type, $this->m_object_id, $left, $right );
        $this->m_db->renumber( $this->m_object_type, $this->m_object_id, $right, -2 );
        
        return true;
    }
    
    // Возвращает дерево от указанного узла.
    function get_tree( $from_node_id = 0, $limit = NULL )
    {
        // Получить границы дерева для указанного узла.
        $f = $this->filter();
        $f->node_id( $from_node_id );
        $f->add_field( $this->m_db->left );
        $f->add_field( $this->m_db->right );
        list( $left, $right ) = $this->m_db->load_fields( $f );
        
        // Загрузить узлы дерева в пределах указанного узла.
        $f = $this->filter();
        $f->between( $left, $right );
        if( isset( $limit ) )
        {
//            print_r( $limit );
            // Первый узел всегда является корнем дерева,
            // поэтому фактически нужно получить на единицу больше записей.
            $f->limit( 0, $limit + 1 );
//            print_r( $f->select() );
        }
        
        $nodes = array();
        $this->m_db->load_list( $nodes, $f );
        
        if( count( $nodes ) < 2 ) return array( array(), array() );
                
        // Преобразовать список узлов в дерево.
        // Первый узел является корнем дерева, начать разбор со второго.
        $pos = 1;
        $ids = array();
        $tree = $this->parse_list_into_tree( $nodes, $pos, 
            $nodes[ 0 ][ $this->m_db->right ], $ids );
        
        return array( $ids, $tree );
    }

    // Возвращает путь от корня дерева до указанного узла.
    function get_path_to( $node_id )
    {
        // Получить границы дерева для указанного узла.
        $f = $this->filter();
        $f->node_id( $node_id );
        $f->add_field( $this->m_db->left );
        $f->add_field( $this->m_db->right );
        list( $left, $right ) = $this->m_db->load_fields( $f );
                
        // Загрузить узлы дерева в пределах указанного узла.
        $f = $this->filter();
        $f->contains( $left, $right );
        $f->node_id_not( 0 );
                
        $nodes = array();
        $this->m_db->load_list( $nodes, $f );
        
        // Сформировать массив узлов в пути.
        $ids = array();
        foreach( $nodes as $v ) $ids[] = $v[ $this->m_db->node_id ];
                
        // Путь и массив номеров узлов являются одним и тем же массивом.
        return array( $ids, $ids );
    }
    
    // private
    function parse_list_into_tree( &$list, &$pos, $right, &$ids )
    {
        $tree = array();
        
        while(    $pos < count( $list ) 
               && $list[ $pos ][ $this->m_db->left ] < $right )
        {
            $node = $list[ $pos ];
            $ids[] = $node[ $this->m_db->node_id ];
            $tree[] = $node[ $this->m_db->node_id ];
            ++$pos;
            
            if( ( $node[ $this->m_db->right ] - $node[ $this->m_db->left ] ) > 1 )
            {
                // Есть потомки.
                $tree[] = $this->parse_list_into_tree( $list, $pos, 
                    $node[ $this->m_db->right ], $ids );
            }
        }
        
        return $tree;
    }
    
    // Возвращает готовый фильтр объектов.
    function filter()
    {
        $f = new TreeFilter( $this->m_db, $this->m_object_type, $this->m_object_id );
        return $f;
    }
    
    // private
    // Возвращает значения границ указанного узла.
    function get_node_left_right( $node_id )
    {
        // Получить границы дерева для указанного узла.
        $f = $this->filter();
        $f->node_id( $node_id );
        $list = array();
        $this->m_db->load_list( $list, $f );

        // Если узла с таким номером нет или их больше двух --- ошибка.        
        if( count( $list ) != 1 ) return array( 0, 0 );
        $node = $list[ 0 ];
        
        return array( $node[ $this->m_db->left ], $node[ $this->m_db->right ] );
    }
}


global $db;
$g_tree_db = new TreeDB( $db );


?>
