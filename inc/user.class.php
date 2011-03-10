<?php


/*

    class User.

*/

include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/edit_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/user_group.class.php' );
include_once( PATH_TO_ROOT . 'inc/assoc_list.class.php' );
include_once( PATH_TO_ROOT . 'inc/message.class.php' );
include_once( PATH_TO_ROOT . 'inc/image.class.php' );
include_once( PATH_TO_ROOT . 'inc/user_tpl.class.php' );



define( 'USER_LOGIN_COOKIE', 'login' );
define( 'USER_PASSWORD_COOKIE', 'password' );

// Эта константа определяет срок хранения куков в случае,
// если галочка "Запомнить меня" отмечена.
define( 'USER_COOKIE_TTL', 60 * 60 * 24 * 14 );


class UserDB extends DBObjectDB
{
    var $name;
    var $nick;
    var $login;
    var $password;
    var $email;
    var $birthday;
    var $gender;
    var $avatar;
    var $about;
    var $obsolete;
    var $insert_stamp;
    var $activated;
    var $activate_mail_stamp;
    var $activated_stamp;

    var $groups; // Поле не записывается в БД.
    
    var $groups_list;
    var $users_list;

    // Constructor
    function UserDB( &$db, $host = HOST_CODE )
    {
        parent::DBObjectDB( $db, $host );

        $this->name                 = new FieldString( 'Name', 'Имя' );
        $this->nick                 = new FieldString( 'Nick', 'Ник' );
        $this->login                = new FieldString( 'Login', 'Логин' );
        $this->password             = new FieldString( 'Password', 'Пароль' );
        $this->email                = new FieldURL( 'Email', 'E-mail' );
        $this->birthday             = new FieldTimestamp( 'Birthday', 'День рождения' );
        $this->gender               = new FieldString( 'Gender', 'Пол' );
        $this->avatar               = new FieldImage( 'Avatar', 'Аватар', array( 0, 0 ) );
        $this->about                = new FieldText( 'About', 'О себе' );
        $this->obsolete             = new FieldFlag( 'Obsolete', 'Запрещен' );
        $this->insert_stamp         = new FieldTimestamp( 'InsertStamp', '', time_to_sql_timestamp( time() ) );
        $this->activated            = new FieldFlag( 'Activated', 'Активирован' );
        $this->activate_mail_stamp  = new FieldTimestamp( 'ActivateMailStamp', 'Письмо отослано' );
        $this->activated_stamp      = new FieldTimestamp( 'ActivatedStamp', 'Дата активации' );
        
        $edit = new EditFieldDateTimeShow( $this->insert_stamp, 'Дата добавления' );
        $edit = new EditFieldDateTimeShow( $this->activate_mail_stamp, 'Письмо отослано' );
        $edit = new EditFieldDateTimeShow( $this->activated_stamp, 'Дата активации' );

        $this->groups = new FieldBase( 'Groups' );
                
        $this->groups_list = new AssociativeList( 'User', 'UserGroup' );
        $this->users_list = new AssociativeList( 'UserGroup', 'User' );
    }
    
    function fields_list()
    {
        return array_merge( parent::fields_list(), array(
            $this->name                 ,
            $this->nick                 ,
            $this->login                ,
            $this->password             ,
            $this->email                ,
            $this->birthday             ,
            $this->gender               ,
            $this->avatar               ,
            $this->about                ,
            $this->obsolete             ,
            $this->insert_stamp         ,
            $this->activated            ,
            $this->activate_mail_stamp  ,
            $this->activated_stamp      ,
        ) );
    }
    
    function table_name() { return 'dwUser'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new User( NULL, $data );
    }

    function filter()
    {
        $f = new UserFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
}


class UserFilter
    extends DBObjectFilter
{
    var $m_login = NULL;
    var $m_email = NULL;
    var $m_nick = NULL;
    var $m_in_groups = NULL;
    var $m_activated = NULL;
    
    function UserFilter( &$db )
    {
        parent::DBObjectFilter( $db );
    }

    // Возвращает сформированную строку условий.    
    function where_array( $prefix = '' )
    {
        $where = parent::where_array( $prefix );
        
        if( isset( $this->m_login ) )
        {
            $where[] = $this->m_db->login->name() . ' = ' 
                . wrap_sql_type( $this->m_login );
        }
        
        if( isset( $this->m_email ) )
        {
            $where[] = $this->m_db->email->name() . ' = ' 
                . wrap_sql_type( $this->m_email );
        }
        
        if( isset( $this->m_nick ) )
        {
            $where[] = $this->m_db->nick->name() . ' = ' 
                . wrap_sql_type( $this->m_nick );
        }
        
        if( isset( $this->m_in_groups ) )
        {
            $where[] = $this->m_db->m_id->name() . ' in (' 
                . $this->m_db->users_list->select( $this->m_in_groups )
                . ')';
        }
        
        if( isset( $this->m_activated ) )
        {
            $where[] = $this->m_db->activated->name() . ' = ' 
                . wrap_sql_type( $this->m_activated ? 1 : 0 );
        }
        
        return $where;
    }
    
    function id( $v ) { $this->m_id = $v; }
    function login( $v ) { $this->m_login = $v; }
    function email( $v ) { $this->m_email = $v; }
    function nick( $v ) { $this->m_nick = $v; }
    function in_groups( $v ) { $this->m_in_groups = $v; }
    function activated( $v = true ) { $this->m_activated = $v; }
}


class UserImpl
    extends DBObject
{

    // Constructor.
    function UserImpl( &$db, $id = NULL, $data = NULL, $from_form = false )
    {
        parent::DBObject( $db, $id, $data, $from_form );

        if(    !isset( $data ) 
            || ( !$from_form && isset( $data[ $this->m_db->m_id->name() ] ) && $data[ $this->m_db->m_id->name() ] ) )
        {
            // Если объект создается на основе номера,
            // загрузить список групп пользователя.
            // Для нового объекта ID нулевой, что позволяет при желании сделать
            // подобие списка групп "по умолчанию".
            $groups = array();
            $this->m_db->groups_list->load_list( $groups, $this->id() );
            $this->data( $this->m_db->groups, $groups );
        }
    }

    function save()
    {
        $result = parent::save();
        $this->m_db->groups_list->save_list( $this->id(), $this->data( $this->m_db->groups ) );
        return $result;
    }
        
    function class_title() { return 'пользователя'; }
    function title() { return $this->data( $this->m_db->nick ) 
        ? $this->data( $this->m_db->nick ) : $this->data( $this->m_db->name ); }
    
    // Конструирует форму для исправления данных.
//    function get_form( $parent_page, $prefix, $action, $start_clean = false )
//    {
//        $tpl = new Template();
//        $tpl->set_file( 'm', PATH_TO_ROOT . 'inc/user.form.tpl' );
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

//        // Отдельно отработать список групп.
//        global $g_user_group_db;
//        $groups = array();
//        $g_user_group_db->load_list( $groups );
//                            
//        // Отобразить группы.
//        $list = $this->data( $this->m_db->groups );
//        $block = 'groups_item';
//        $tpl->set_block( 'm', $block, $block . '_' );
//        foreach( $groups as $v )
//        {
//            $tpl->set_var( 'GROUP_NAME', $v->title() );
//            $tpl->set_var( 'GROUP_ID', $v->id() );
//            $tpl->set_var( 'GROUP_SELECTED',
//                in_array( $v->id(), $list ) ? 'checked' : '' );
//            $tpl->parse( $block . '_', $block, true );
//        }

//        // Отдельно вывести информацию об активации.
//        $null_stamp = mktime( 0,0,0,0,0,0 );
//        $activated = $this->data( $this->m_db->activated );
//        $mail_stamp = sql_timestamp_to_time( $this->data( 
//            $this->m_db->activate_mail_stamp ) );
//        $act_stamp = sql_timestamp_to_time( $this->data( 
//            $this->m_db->activated_stamp ) );
//            
//        $tpl->set_var( 'ACTIVATED_TEXT', $activated
//            ? ( 'активирован ' . ( $act_stamp != $null_stamp
//                ? date( 'd.m.Y H:i:s', $act_stamp ) 
//                : '(дата неизвестна)' ) )
//            : 'не активирован (письмо ' 
//                . ( $mail_stamp  != $null_stamp
//                ? 'отослано ' . date( 'd.m.Y H:i:s', $mail_stamp ) 
//                : 'не отсылалось' ) . ')'
//        );

//        $tpl->set_block( 'm', 'not_activated', 'not_activated_' );
//        if( $activated ) $tpl->set_var( 'not_activated_', '' );
//        else $tpl->parse( 'not_activated_', 'not_activated' );
//                            
//        return $tpl->parse( 'C', 'm', false );
//    }

    // Преобразует данные, возвращенные из формы, в форму,
    // используемую для хранения.
    function convert_from_form()
    {
        parent::convert_from_form();
    
        // Список групп.
        $list = array();
        if( $this->data( $this->m_db->groups ) != NULL )
        {
            foreach( $this->data( $this->m_db->groups ) as $k => $v )
            {
                $list[] = $k;
            }
        }

        $this->data( $this->m_db->groups, $list );
    }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;
        
        if( $this->data( $this->m_db->name ) == '' )
        {
            $errors[] = 'Не указано имя пользователя!';
        }
        
        if( $this->data( $this->m_db->login ) == '' )
        {
            $errors[] = 'Не указан логин пользователя!';
        }
        
        if( $this->data( $this->m_db->password ) == '' )
        {
            $errors[] = 'Не указан пароль пользователя!';
        }
        
        $errors = array_merge( $errors, $this->m_db->avatar->check() );
                            
        return implode( '<br />', $errors );
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
        $rows[ $this->m_db->nick->name()            ] = new LinkField( 'Ник' );
        $rows[ $this->m_db->email->name()           ] = new LinkField( 'E-mail (логин)' );
        $rows[ $this->m_db->password->name()        ] = new Field( 'Пароль', true );
        $rows[ $this->m_db->insert_stamp->name()    ] = new Field( 'Добавлен', true );
        $rows[ $this->m_db->activated_stamp->name() ] = new Field( 'Активирован', true );
        $rows[ $this->m_db->obsolete->name()        ] = new Field( 'Запрещен', true );
        
        $default_sort = $this->m_db->nick->name();
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        $email = $this->data( $this->m_db->email );
        $login = $this->data( $this->m_db->login );
    
        parent::get_row_data( $data );
        $data[] = $this->title();
        $data[] = $email . ( $email == $login ? ''
            : ' (' . $login . ')' );
        $data[] = $this->data( $this->m_db->password );

        $data[] = date( 'd.m.Y', sql_timestamp_to_time( 
            $this->data( $this->m_db->insert_stamp ) ) );
        $data[] = $this->data( $this->m_db->activated )
            ? date( 'd.m.Y', sql_timestamp_to_time( 
                $this->data( $this->m_db->activated_stamp ) ) )
            : '-';
        $data[] = $this->data( $this->m_db->obsolete ) ? 'да' : '';
        
        return $data;
    }

    function in_group( $group_id )
    {
        $groups = $this->data( $this->m_db->groups );
        foreach( $groups as $v ) if( $v == $group_id ) return true;
        return false;
    }

    // Возвращает true, если пользователь уже активирован,
    // или false в противном случае.
    function activated()
    {
        return $this->data( $this->m_db->activated );
    }
        
    // Отсылает сообщение об активации пользователя.
    // Возвращает true в случае успешной отправки, false при проблемах.
    function send_activation_message()
    {
        $msg = new UserTPL( 'message_user_registered' );
        $tpl = new Template();
        $msg->set_template( $tpl, 'm' );

        // Выставить время отсылки сообщения об активации.        
        $this->data( $this->m_db->activate_mail_stamp, 
            time_to_sql_timestamp( time() ) );

        $this->place( $tpl, '' );
        
        $tpl->set_var( 'HOST', host_name() );
        $tpl->set_var( 'CHECK_CODE', urlencode( $this->calc_activate_hash() ) );

        // Отослать сообщение.
        $msg = new Message( $tpl->parse( 'c', 'm' ) );
        if( !( $this->send_message( $msg ) ) ) return false;
                        
        // Поскольку мы изменили время отсылки сообщения об активации,
        // надо записаться в БД.
        $this->save();
        
        return true;
    }
    
    // Активирует пользователя, если передана правильная ссылка.
    function activate( $hash )
    {
        if( $this->calc_activate_hash() == urldecode( $hash ) )
        {
            $msg = new UserTPL( 'message_user_activated' );
            $tpl = new Template();
            $msg->set_template( $tpl, 'm' );

            $this->place( $tpl, '' );
            
            $tpl->set_var( 'HOST', host_name() );

            // Отослать сообщение.
            $msg = new Message( $tpl->parse( 'c', 'm' ) );
            if( !( $this->send_message( $msg ) ) ) return false;
                        
            $this->data( $this->m_db->activated, 1 );
            $this->data( $this->m_db->activated_stamp, 
                time_to_sql_timestamp( time() ) );
            $this->save();
            
            return true;
        }
        
        return false;
    }
        
    // Отсылает письмо для восстановления пароля.
    function send_password_message()
    {
        $msg = new UserTPL( 'message_user_password' );
        $tpl = new Template();
        $msg->set_template( $tpl, 'm' );

        $this->place( $tpl, '' );
        $tpl->set_var( 'HOST', host_name() );

        // Отослать сообщение.
        $msg = new Message( $tpl->parse( 'c', 'm' ) );
        if( !( $this->send_message( $msg ) ) ) return false;
                        
        return true;
    }

    function do_auto_login()
    {
        return $this->login( $this->data( $this->m_db->login ), 
            $this->data( $this->m_db->password ) );
    }
    
    // Проверяет, возможен ли вход пользователя с указанным логином и паролем,
    // или содержится ли в куках информация для автоматического логина.
    // Если пользователь может быть залогинен, возвращается соответствующий 
    // объект пользователя, иначе NULL.
    public static 
    function login( $login = NULL, $password = '', $cookie_ttl = USER_COOKIE_TTL )
    {
        if( !isset( $login ) )
        {
            // Попытаться получить информацию о логине из куков.
            if(    !isset( $_COOKIE[ USER_LOGIN_COOKIE ] ) 
                || !isset( $_COOKIE[ USER_PASSWORD_COOKIE ] ) )
            {
                // Пользователь явно не указан и взять его неоткуда.
                return NULL;
            }
            
            $login = $_COOKIE[ USER_LOGIN_COOKIE ];
            $password = $_COOKIE[ USER_PASSWORD_COOKIE ];
            
            // При входе по значению куков устанавливать куки
            // повторно не нужно, поскольку неизвестно,
            // установлены они на текущую сессию или нет.
            // Отрицательное время жизни куков означает,
            // что устанавливать куки не нужно.
            $cookie_ttl = -1;
        }
        else
        {
            // Пользователь указан явно, получить хэш пароля для проверки.
            $password = md5( $password );
            
            // Тут небольшой хак для быстрой реализации "Запомнить меня". 
            // Если галочка не отмечена, то ставим время хранения куки 0
            // (только на текущую сессию).
            global $form;
            if( !isset( $form[ 'Remember' ] ) ) $cookie_ttl = 0;
        }
        
        // Вытащить ID пользователя по логину.
        global $g_user_db;
        $filter = $g_user_db->filter();
        $filter->login( $login );
        $filter->activated();
                
        $list = array();
        $g_user_db->load_list( $list, $filter );
                
        // Пытаться залогинить каждого из пользователей с таким логином. 
        // added: а чо будет если два одинаковых пароля?
        foreach( $list as $v )
        {
            if( $v->do_login( $password, $cookie_ttl ) )
            {
                return $v;
            }
        }
        
        // Сбросить информацию о предыдущих входах.
        User::logout();
        // Подходящего пользователя не найдено.
        return NULL;
    }
    
    // Проверяет, возможен ли вход пользователя в указанным паролем.
    // Возвращает true при правильности пароля и false, если пароль неверный.
    // При правильном вводе пароля устанавливает куки на указанное время.
    function do_login( $password, $cookie_ttl = USER_COOKIE_TTL )
    {
        if( $password !== md5( $this->data( $this->m_db->password ) ) )
        {
            return false;
        }

        // При отрицательном значении времени жизни устанавливать куки не нужно.
        if( $cookie_ttl >= 0 )
        {
            $this->set_cookie( 
                $this->data( $this->m_db->login ), 
                md5( $this->data( $this->m_db->password ) ), 
                $cookie_ttl
            );
        }
            
        return true;
    }

    static
    // Устанавливает куки из указанных данных.
    function set_cookie( $u, $p, $cookie_ttl = USER_COOKIE_TTL )
    {
        if ($cookie_ttl > 0  ) {
            setcookie( USER_LOGIN_COOKIE, $u, time() + $cookie_ttl, '/' );
            setcookie( USER_PASSWORD_COOKIE, $p, time() + $cookie_ttl, '/' );
        } else {
            setcookie( USER_LOGIN_COOKIE, $u, 0, '/' );
            setcookie( USER_PASSWORD_COOKIE, $p, 0, '/' );            
        }
    }
        
    // Выполняет выход пользователя из системы.
    // Фактически просто удаляет куки.
    public static 
    function logout()
    {
        setcookie( USER_LOGIN_COOKIE, '', 0, '/');
        setcookie( USER_PASSWORD_COOKIE, '', 0, '/');
    }

    // private
    // Отправляет переданное сообщение на e-mail пользователя.
    // Возвращает результат отправки сообщения.
    function send_message( $msg )
    {
        $msg->to( $this->data( $this->m_db->email ) );
        return $msg->send();
    }
                
    // private
    // Вычисляет хэш-функцию пользователя.
    function calc_activate_hash()
    {
        return md5( $this->data( $this->m_db->name )
            . md5( $this->data( $this->m_db->password ) )
            . $this->data( $this->m_db->email )
            . $this->data( $this->m_db->insert_stamp )
            . $this->data( $this->m_db->activate_mail_stamp ) );
    }
    
    // private.
    // Удаляет в объекте файлы картинок.
    function delete_image_file( $file )
    {
        if( $file == $this->data( $this->m_db->avatar ) )
        {
            $image = new Image( $file );
            $image->delete();
            $this->data( $this->m_db->avatar, '' );
        }
    }
}


global $db;
$g_user_db = new UserDB( $db, NULL );


class User extends UserImpl
{
    function User( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_user_db;
        parent::UserImpl( $g_user_db, $id, $data, $from_form );
    }
}


?>
