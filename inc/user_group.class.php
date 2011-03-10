<?php


/*

    class UserGroup.

*/


include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/edit_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/user.class.php' );
include_once( PATH_TO_ROOT . 'inc/assoc_list.class.php' );


class UserGroupDB extends DBObjectDB
{
    var $name;
    var $group;

    var $users_list;
    
    // Constructor
    function UserGroupDB( &$db, $host = HOST_NAME )
    {
        parent::DBObjectDB( $db, $host );

        $this->name = new FieldString( 'Name', 'Название' );
        $this->group = new FieldNonDB( 'Group' );
        $edit = new EditFieldShowUsersInGroup
        
        $this->users_list = new AssociativeList( 'UserGroup', 'User' );
    }
    
    function fields_list()
    {
        return array_merge( parent::fields_list(), array(
            $this->name         ,
        ) );
    }
    
    function table_name() { return 'dwUserGroup'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new UserGroup( NULL, $data );
    }

    function filter()
    {
        $f = new UserGroupFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
}


class UserGroupFilter
    extends DBObjectFilter
{
    function UserGroupFilter( &$db )
    {
        parent::DBObjectFilter( $db );
    }
    
    // Возвращает сформированную строку условий.    
    function where_array( $prefix = '' )
    {
        $where = parent::where_array( $prefix );
        
        return $where;
    }
}


class UserGroupImpl extends DBObject
{
    function class_title() { return 'группу пользователей'; }
    function title() { return $this->data( $this->m_db->name ); }
    
    // Конструирует форму для исправления данных.
//    function get_form( $parent_page, $prefix, $action, $start_clean = false )
//    {
//        $tpl = new Template();
//        $tpl->set_file( 'm', PATH_TO_ROOT . 'inc/user_group.form.tpl' );
//        
//        $tpl->set_var( 'PREFIX', $prefix );
//        $tpl->set_var( 'BUTTON_CAPTION', 
//            ( $action == ACT_ADD ? 'Добавить' : 'Изменить' )
//            . ' ' . $this->class_title() );
//        $tpl->set_var( 'ACTION', $action );
//        $tpl->set_var( 'PARENT', $parent_page );
//        $tpl->set_var( 'PATH_TO_ADMIN', PATH_TO_ADMIN );

//        foreach( $this->m_db->fields() as $v )
//        {
//            $v->place_form( $this, $tpl, 'm' );
//        }

//        // Список пользователей.
//        $users = array();
//        $this->m_db->users_list->load_list( $users, $this->id() );
//        $block = 'users_item_';
//        $tpl->set_block( 'm', $block, $block . '_' );
//        if( $users )
//        {
//            foreach( $users as $k => $v )
//            {
//                $user = new User( $v );
//                $tpl->set_var( 'USER_NAME', $user->title() );
//                $tpl->parse( $block . '_', $block, true );
//            }
//        }
//        else $tpl->set_var( $block . '_', '' );
//                    
//        return $tpl->parse( 'C', 'm', false );
//    }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;
        
        if( $this->data( $this->m_db->name ) == '' )
        {
            return 'Не указано название группы пользователей!';
        }
                            
        return '';
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
        $rows[ $this->m_db->name->name() ] = new LinkField( 'Группа' );
        
        $default_sort = $this->m_db->name->name();
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        parent::get_row_data( $data );
        $data[] = $this->title();
            
        return $data;
    }
}


global $db;
$g_user_group_db = new UserGroupDB( $db, NULL );


class UserGroup extends UserGroupImpl
{
    function UserGroup( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_user_group_db;
        parent::UserGroupImpl( $g_user_group_db, $id, $data, $from_form );
    }
}


?>
