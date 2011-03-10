<?php


/*

    class Settings.

*/


include_once( PATH_TO_ROOT . 'inc/db_object.class.php' );
include_once( PATH_TO_ROOT . 'inc/assoc_list.class.php' );
include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );


class SettingsDB extends DBObjectDB
{
//    var $page;
//    var $page_list;
    var $header_title;
    var $header_slogan;
    var $header_contact;
    
    var $banner_image;
    var $banner_title;
    var $banner_url;
        
    var $footer_left;
    var $footer_center;
    var $footer_right;
    var $footer_counter;
    
    var $header_page1;
    var $header_page2;
    var $header_page3;

    // Constructor
    function SettingsDB( &$db, $host = HOST_CODE )
    {
        parent::DBObjectDB( $db, $host );

        $this->header_title   = new FieldString( 'HeaderTitle', 'Название сайта' );
        $this->header_slogan  = new FieldString( 'HeaderSlogan', 'Слоган' );
        $this->header_contact = new FieldString( 'HeaderContact', 'Контакты в шапке' );
        $this->banner_image   = new FieldImage( 'BannerImage', 'Баннер', array( 155, 0 ), NULL );
        $this->banner_title   = new FieldString( 'BannerTitle', 'Название баннера' );
        $this->banner_url     = new FieldString( 'BannerURL', 'href баннера' );
        $this->footer_left    = new FieldText( 'FooterLeft', 'Копирайты', 'Basic' );
        $this->footer_center  = new FieldText( 'FooterCenter', 'Адреса и телефоны', 'Basic' );
        $this->footer_right   = new FieldText( 'FooterRight', 'Контакты в подвале', 'Basic' );
        $this->footer_counter = new FieldString( 'FooterCounter', 'Коды счетчиков' );
        
        $this->header_page1 = new FieldStaticPageFromMenu( 'HeaderPage1', 'Страница для мужика', array( 'top', 'left' ) );
        $this->header_page2 = new FieldStaticPageFromMenu( 'HeaderPage2', 'Страница для телефона', array( 'top', 'left' ) );
        $this->header_page3 = new FieldStaticPageFromMenu( 'HeaderPage3', 'Страница для Петра 1', array( 'top', 'left' ) );

        $edit = new EditFieldTextArea( $this->footer_counter, 'Коды счетчиков' );
        
        $edit = new EditFieldHidden( $this->header_contact );
        $edit = new EditFieldHidden( $this->banner_image );
        $edit = new EditFieldHidden( $this->banner_title );
        $edit = new EditFieldHidden( $this->banner_url );
        $edit = new EditFieldHidden( $this->footer_right );
                
//        global $g_static_page_db;
//        $f = $g_static_page_db->filter();
//        $f->sort( $g_static_page_db->name );
//        $this->page = new FieldObjectsAssociativeList( 'Page', 'Горячие туры', $g_static_page_db, $f );
//        $this->page_list = new AssociativeList( 'Settings', 'StaticPage' );
    }
    
    function fields_list()
    {
        return array_merge( parent::fields_list(), array(
            $this->header_title  ,
            $this->header_slogan ,
            $this->header_contact,
            $this->header_page1  ,
            $this->header_page2  ,
            $this->header_page3  ,
            $this->banner_image  ,
            $this->banner_title  ,
            $this->banner_url    ,
            $this->footer_left   ,
            $this->footer_center ,
            $this->footer_right  ,
            $this->footer_counter,
        ) );
    }
    
    function non_db_fields_list()
    {
        return array_merge( parent::non_db_fields_list(), 
            /*array( $this->page )*/array() );
    }
    
    function table_name() { return 'dwSettings'; }
    
    // Создает новый объект на основании считаных из БД данных.
    function create_object( &$data )
    {
        return new Settings( NULL, $data );
    }

    function filter()
    {
        $f = new SettingsFilter( $this );
        $f->host( $this->m_host_code );
        return $f;
    }
}


class SettingsFilter
    extends DBObjectFilter
{
    function SettingsFilter( &$db )
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


class SettingsImpl extends DBObject
{
    function SettingsImpl( &$db, $id = NULL, $data = NULL, $from_form = false )
    {
        parent::DBObject( $db, $id, $data, $from_form );

        if(    !isset( $data ) 
            || ( !$from_form && isset( $data[ $this->m_db->m_id->name() ] ) && $data[ $this->m_db->m_id->name() ] ) )
        {
            // Если объект создается на основе номера, загрузить список.
            // Для нового объекта ID нулевой, что позволяет при желании
            // сделать подобие списка "по умолчанию".
//            $list = array();
//            $this->m_db->page_list->load_list( $list, $this->id() );
//            $this->data( $this->m_db->page, $list );
        }
    }

    function save()
    {
        $result = parent::save();
//        $this->m_db->page_list->save_list( $this->id(), $this->data( $this->m_db->page ) );
        return $result;
    }
    
    function delete()
    {
        // Если известен id объекта, удалить его из БД.
//        if( $id = $this->id() )
//        {
//            $this->m_db->page_list->delete_list( $id );
//        }
        
        return parent::delete();
    }
    
    function class_title() { return 'настройки'; }
    function title() { return 'Настройки'; }

    // Проверяет логическую правильность данных.
    // Возвращает текст ошибки или пустую строку для правильных данных.
    function check()
    {
        $errors = array();
        if( ( $r = parent::check() ) != '' ) $errors[] = $r;
                                    
        return implode( '<br />', $errors );
    }

    // Static.
    // Возвращает список отображаемых в таблице колонок.
    function get_row_config( &$rows, &$default_sort )
    {
        parent::get_row_config( $rows, $default_sort );
    }
    
    // Добавляет в таблицу строку с данными объекта.
    function get_row_data( &$data )
    {
        parent::get_row_data( $data );
        return $data;
    }

//    function pages() { return $this->data( $this->m_db->page ); }
}


global $db;
$g_settings_db = new SettingsDB( $db, HOST_CODE );


class Settings extends SettingsImpl
{
    function Settings( $id = NULL, $data = NULL, $from_form = false )
    {
        global $g_settings_db;
        parent::SettingsImpl( $g_settings_db, $id, $data, $from_form );
    }
}


?>
