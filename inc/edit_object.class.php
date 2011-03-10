<?php


ini_set( 'display_errors', 'On' );


/*
*
*   Редактирование объектов, отображаемых в форме списка.
*
*/


require_once( PATH_TO_ROOT . 'inc/func.php' );


// Константы.        
define( 'ACT_ADD'           , 'add' );
define( 'ACT_EDIT'          , 'edit' );
define( 'ACT_SELECT_PROC'   , 'select' );
define( 'ACT_ADD_PROC'      , 'add_proc' );
define( 'ACT_EDIT_PROC'     , 'edit_proc' );
define( 'ACT_DEL_PROC'      , 'delete_proc' );
define( 'SAVE_AND_NEW'      , '' );
define( 'SAVE_AND_EDIT'     , 'save_and_edit' );


class Field
{
    var $m_title;
    var $m_sort = true;
    var $m_link = true;
    
    function Field( $title, $sort = true, $link = false )
    {
        $this->m_title = $title;
        $this->m_sort = $sort;
        $this->m_link = $link;
    }

    function title() { return $this->m_title; }    
    function sortable() { return $this->m_sort; }
    function linkable() { return $this->m_link; }
}

class LinkField
    extends Field
{
    function LinkField( $title, $sort = true )
    {
        parent::Field( $title, $sort, true );
    }
}

class TableField extends Field 
{
    function TableField($title)
    {
        parent::Field( $title, false, false );
    }
}


class EditObject
{
    var $action;
    var $prefix;
    var $list_first;
    var $err;
    var $obj;
    var $page;
    var $parent_page;
    var $form;
    // Отображать ли форму и список.
    var $show_form = true;
    var $show_list = true;
    // Флаг открытия вложенных форм в "девственном состоянии".
    var $start_clean;

    // Constructor.
    function EditObject( $parent_page, $prefix = '', $start_clean = false, 
        $list_first = false, $show = '' )
    {
        $this->prefix = $prefix; // Дополнение к именам действий и форм.
        $this->err = ''; // Сообщение об ошибке при редактировании объекта.
        $this->list_first = $list_first;
        
        if( $show == 'list' ) $this->show_form = false;
        if( $show == 'form' ) $this->show_list = false;
        
        if( !$start_clean )
        {
            // Текущее действие. По умолчанию добавление объекта.
            $this->action = get_http_var( $this->prefix . 'a', ACT_ADD );
            // ID текущего объекта. По умолчанию отсутствует.
            $this->id = get_http_var( $this->prefix . 'id', NULL );
            // Объект с нулевым или пустым номером не существует.
            if( !$this->id ) $this->id = NULL;
        }
        else
        {
            $this->action = ACT_ADD;
            $this->id = NULL;
        }
        
        // Признак создания вложенных формы без учета переданных переменных.
        $this->start_clean = $start_clean;

        // Форма с данными.
        $this->form = get_http_var( $this->prefix . 'form', NULL );
                                        
        // Текущий объект. Создается в порядке очередности:
        // 1) на основе формы, если производилась правка или добавление,
        // 2) по номеру, если он указан,
        // 3) или новый пустой объект для добавления.
        if(    ( $this->action == ACT_ADD_PROC ) 
            || ( $this->action == ACT_EDIT_PROC )
            || !is_null( $this->form ) )
        {
            $this->obj = $this->create_object( $this->id, 
                $this->form, true );
        }
        else
        {
            $this->form = NULL;
            $this->obj = $this->create_object( $this->id );
        }
        // Реакция на действия пользователя.
        if( strpos( $this->action, '_proc' ) )
        {
            $f = 'do_' . str_replace( '_proc', '', $this->action );
            $this->$f();
        }
        
        // Если текущий объект отсутствует, можно только добавить объект.
        if( !isset( $this->id ) ) $this->action = ACT_ADD;

        // Адрес родительской страницы.
        $this->parent_page = $parent_page;
        // Адрес текущего состояния страницы.
        $this->page = $this->parent_page 
            . '&' . $this->prefix . 'a=' . $this->action;
        if( $this->action == ACT_EDIT )
        {
            $this->page .= '&' . $this->prefix . 'id=' . $this->id;
        }
    }
    /**
     * Возвращает сформированный код правки объекта.
     *
     * @return HTML
     */
    function show()
    {
        $html = '';
        
        if( $this->show_form )
        {
            // Вспомогательные скрипты.
            $html .= $this->get_delete_script();
    
            if( $this->show_list )
            {
                $list_title = $this->get_list_title();
                if( $list_title ) $html .= get_header( $list_title );
            }
            
            if( $this->list_first )
            {
                // Отображаем список объектов.
                if( $this->show_list ) $html .= $this->show_list();
                // Отображаем форму редактирования объекта.
                $html .= $this->show_form();
            }
            else
            {
                // Отображаем форму редактирования объекта.
                $html .= $this->show_form();
                // Отображаем список объектов.
                if( $this->show_list ) $html .= $this->show_list();
            }
        }
        else if( $this->show_list )
        {
            $list_title = $this->get_list_title();
            if( $list_title ) $html .= get_header( $list_title );
            
            // Выводим только список
            $html .= $this->show_list();
        }
        
        return $html;
    }
    
    // private
    function show_list()
    {
        $html = '';
        
        if( !$this->prefix )
        {
            $html .= '<form action="' . $this->parent_page 
                . '" name="' . $this->prefix . 'count"' 
                . ' method="post" enctype="multipart/form-data">';
        }
        
        $html .= $this->create_objects_list( $this->parent_page, 
            $this->prefix, ACT_EDIT );
            
        if( !$this->prefix ) $html .= '</form>';
        
        return $html;
    }
    
    // private
    function show_form()
    {
        $html = '';
        
        if( !$this->prefix )
        {
            $html .= '<form action="' . $this->parent_page 
                . '" name="' . $this->prefix . 'form"' 
                . ' method="post" enctype="multipart/form-data">';
        }

        $html .= $this->get_form( $this->page, $this->prefix, 
            $this->action, $this->start_clean );
            
        if( !$this->prefix ) $html .= '</form>';
        
        return $html;
    }

    // private
    function do_select()
    {
        // По умолчанию выбор объекта означает открытие его в режиме правки,
        // соответственно вложенные формы открываются в "девственном состоянии".
        $this->start_clean = true;
        $this->action = ACT_EDIT;
    }
    
    // private
    function do_add()
    {
        // Добавление нового объекта на основе введенных данных.
        $this->err = $this->obj->check();
        
        if( $this->err == '' )
        {
            // Если ошибок не было, сохраняем объект.
            $result = $this->obj->save();
                        
            $this->do_after_add( $this->obj->id() );
            
            if( $result == SAVE_AND_EDIT )
            {
                $this->action = ACT_EDIT;
                $this->id = $this->obj->id();
            }
            else
            {
                // И заводим новый для возможного добавления.
                $this->obj = $this->create_object();
                $this->action = ACT_ADD;
            }
        }
        else $this->action = ACT_ADD;
    }
    
    // private
    function do_edit()
    {
        // Исправление существующего объекта.
        $this->err = $this->obj->check();
        
        // Если были ошибки при редактировании, остаемся в редакторе.
        if( $this->err ) $this->action = ACT_EDIT;
        else 
        {
            // Иначе сохраняем объект.
            $this->obj->save();
            
            $this->do_after_edit( $this->obj->id() );
            
            // Заводим новый для возможного добавления.
            $this->obj = $this->create_object();
            // И переходим в режим добавления объекта.
            $this->action = ACT_ADD;
        }
        
    }
    
    // private
    function do_delete()
    {
        // Удаление существующего объекта.
        $this->err = $this->obj->delete();

        $this->do_after_delete( $this->obj->id() );
                
        // Переходим в режим добавления объекта.
        $this->obj = $this->create_object();
        $this->action = ACT_ADD;
    }

    // private    
    function do_delete_file()
    {
        $field = get_http_var( $this->prefix . 'delete_field', '' );
        $filename = get_http_var( $this->prefix . 'delete_name', '' );
        
        if( $field && $filename )
        {
            // Удаляем указанное изображение.
            $this->obj->delete_file( $field, $filename );
            // Записываем изменения.        
            $this->obj->save();
        }
        
        // Продолжаем в режиме правки.
        $this->action = ACT_EDIT;
    }
        
    // protected
    function create_objects_list( $parent, $prefix, $action )
    {
        $html = '';
        
        // В режиме редактирования объекта можно перейти в режим добавления.
        if( $this->action != ACT_ADD )
        {
            $html .= get_link( 'Добавить ' . $this->get_object_title(), 
                $parent . '&' . $prefix . 'a=' . ACT_ADD );
        }
                
        $table = new PslAdmTbl();
        $table->mSessionPrefix = $prefix . '_a_a_u';
        $table->mDownImg       = PATH_TO_ADMIN . 'img/down.gif';
        $table->mUpImg         = PATH_TO_ADMIN . 'img/up.gif';
        $table->mSortParam     = $prefix . 'sort';
        $table->mSortTypeParam = $prefix . 'sort_dir';
        $table->mPageParam     = $prefix . 'page';
        $table->mInPageParam   = $prefix . 'count';
        $table->mRecCntShow    = true;
        $table->SetInPageOptions( get_inpage_array() );
        
        // Настроить отображаемые в таблице поля.
        $default_sort = '';
        $rows = array();
        $this->get_row_config( $rows, $default_sort );
        
        $sort = array();
        $titles = array();
        foreach( $rows as $k => $v )
        {
            $sort[] = $v->sortable() ? $k : '';
            $titles[] = $v->title();
        }
        if ($this->show_form) {
            $titles[] = 'Действия';
        }
        global $this_page;
    
        $table->mSortDefault = $default_sort;
        $table->mSortFields = $sort;
        $table->mRecordsCnt = $this->get_count();
        $table->SetHead( $parent, $titles, array() );
        
        $list = array();
        $this->get_list( $list, $table->GetOrderByClause() 
            . $table->GetLimitClause() );

        foreach( $list as $obj )
        {
            $this->setup_row( $table, $rows, $obj,
                    '<a href="' . $parent 
                    . '&' . $prefix . 'a=' . $action 
                    . '&' . $prefix . 'id={ID}">{TITLE}</a>',
                    '<a href="javascript:' . $prefix 
                    . 'delete_record( {ID} )"><img src="' 
                    . PATH_TO_ADMIN . 'img/del.gif" border=0 alt="Удалить"></a>' );
        }
         
        return $html . $table->GetTable();
    }
    
    // protected
    function setup_row( &$table, $rows, $obj, $edit_tpl, $delete_tpl )
    {
        $data = array();
        $this->get_row_data( $obj, $data );
        
        // Ссылками на редактирование объекта являются два первых поля.
        $i = 0;
        foreach( $rows as $k => $v )
        {
            if( $v->linkable() )
            {
                $data[ $i ] = str_replace( array( '{ID}', '{TITLE}' ), 
                    array( $obj->id(), $data[ $i ] ), $edit_tpl );
            }
                
            ++$i;
        }
        // Последним полем всегда идет список действий с объектом.
        // Нет, не всегда. Только если для это объекта была определена форма. 
        if ($this->show_form) {
            $data[] = str_replace( array( '{ID}' ), array( $obj->id() ), 
                '<center>' . $delete_tpl . '</center>' );
        }
        $table->SetRow( $data );
    }
    
    // protected    
    function get_row_config( &$rows, &$default_sort )
    {
        $this->obj->get_row_config( $rows, $default_sort );
    }
    
    // protected
    function get_row_data( $obj, &$data )
    {
        $obj->get_row_data( $data );
    }

    // protected
    function get_form( $parent, $prefix, $action, $start_clean = false )
    {
        $html = '';
    
        // Заголовок.
        $html .= get_subheader( $action == ACT_ADD 
                ? 'Добавить ' . $this->get_object_title()
                : 'Редактировать ' . $this->get_object_title() );
                        
        // Возможные ошибки при редактировании.
        $html .= get_formatted_error( $this->err );
            
        return $html . $this->obj->get_form( $parent, $prefix, $action, 
            $start_clean );
    }
    
    // private
    function get_delete_script()
    {
        $msg = 'Удалить ' . $this->get_object_title() . '?';
        $funcName = $this->prefix . 'delete_record';
        $r = "\n<script language=javascript>\n<!--\n" 
            . "function " . $funcName . "( id ) { if( confirm( '" . $msg 
            . "' ) ) location.href = '" 
            . $this->parent_page . '&' 
            . $this->prefix . 'a=' . ACT_DEL_PROC . '&'
            . $this->prefix . 'id='
            . "' + id; }\n" 
            . "//-->\n"
            ."</script>\n";
        return $r;
    }
    
    // Hook. Вызывается сразу после добавления объекта в базу данных.
    function do_after_add( $id )
    {}
    
    // Hook. Вызывается сразу после правки сведений об объекте.
    function do_after_edit( $id )
    {}
    
    // Hook. Вызывается сразу после удаления объекта из базы данных.
    function do_after_delete( $id )
    {}
        
    // Override this function.
    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
//        return new Object($id, $form, $from_form);
    }
    
    // Override this function.
    function get_list( &$list, $order_limit )
    {
//        global $g_object_db;
//        return $g_object_db->load_list( $list, NULL, $order_limit );
    }
    
    // Override this function.
    function get_count()
    {
        $list = array();
        $this->get_list( $list, '' );
        return count( $list );
//        global $g_object_db;
//        return $g_object_db->count();
    }
    
    // Override this function.
    function get_list_title()
    {
//        return "Object List Title";
    }
    
    // Override this function.
    function get_object_title()
    {
        // Lower case! Винительный падеж!
        // Используется в контексте с глаголом:
        // "Добавить статью".
        return $this->obj->class_title();
    }
}


class TrueEditObject
{
    var $action;
    var $prefix;
    var $err;
    var $obj;
    var $page;
    var $parent_page;
    var $form;

    // Constructor.
    function TrueEditObject( $obj, $parent_page, $prefix = '' )
    {
        $this->prefix = $prefix; // Дополнение к именам действий и форм.
        $this->err = ''; // Сообщение об ошибке при редактировании объекта.
                            
        // ID текущего объекта. По умолчанию отсутствует.
        $this->id = $obj->id();
        // Объект с нулевым или пустым номером не существует.
        if( !$this->id ) $this->id = NULL;
        // Текущее действие. По умолчанию добавление объекта.
        $this->action = get_http_var( $this->prefix . 'a', 
            isset( $this->id ) ? ACT_EDIT : ACT_ADD );

        // Форма с данными.
        $this->form = get_http_var( $this->prefix . 'form', NULL );
                                        
        // Текущий объект. Создается в порядке очередности:
        // 1) на основе формы, если производилась правка или добавление,
        // 2) по номеру, если он указан,
        // 3) или новый пустой объект для добавления.
        if(    ( $this->action == ACT_ADD_PROC ) 
            || ( $this->action == ACT_EDIT_PROC )
            || !is_null( $this->form ) )
        {
            $this->obj = $this->create_object( $this->id, 
                $this->form, true );
        }
        else
        {
            $this->form = NULL;
            $this->obj = $obj;
        }
        
        // Реакция на действия пользователя.
        if( strpos( $this->action, '_proc' ) )
        {
            $f = 'do_' . str_replace( '_proc', '', $this->action );
            $this->$f();
        }
        
        // Если текущий объект отсутствует, можно только добавить объект.
        if( !isset( $this->id ) ) $this->action = ACT_ADD;

        // Адрес родительской страницы.
        $this->parent_page = $parent_page;
        // Адрес текущего состояния страницы.
        $this->page = $this->parent_page 
            . '&' . $this->prefix . 'a=' . $this->action;
    }
    
    // Возвращает сформированный код правки объекта.
    function show()
    {
        // Отображаем форму редактирования объекта.
        return $this->show_form();
    }
    
    // private
    function show_form()
    {
        $html = '';
        
        if( !$this->prefix )
        {
            $html .= '<form action="' . $this->parent_page 
                . '" name="' . $this->prefix . 'form"' 
                . ' method="post" enctype="multipart/form-data">';
        }

        $html .= $this->get_form( $this->page, $this->prefix, 
            $this->action );
            
        if( !$this->prefix ) $html .= '</form>';
        
        return $html;
    }
    
    // private
    function do_add()
    {
        // Добавление нового объекта на основе введенных данных.
        $this->err = $this->obj->check();
        
        if( $this->err == '' )
        {
            // Если ошибок не было, сохраняем объект.
            $result = $this->obj->save();
            
            $this->do_after_add( $this->obj->id() );
            
            $this->action = ACT_EDIT;
            $this->id = $this->obj->id();
        }
        else $this->action = ACT_ADD;
    }
    
    // private
    function do_edit()
    {
        // Исправление существующего объекта.
        $this->err = $this->obj->check();
        
        // Если были ошибки при редактировании, остаемся в редакторе.
        if( $this->err ) $this->action = ACT_EDIT;
        else 
        {
            // Иначе сохраняем объект.
            $this->obj->save();
            
            $this->do_after_edit( $this->obj->id() );
        }
        
    }

    // private    
    function do_delete_file()
    {
        $field = get_http_var( $this->prefix . 'delete_field', '' );
        $filename = get_http_var( $this->prefix . 'delete_name', '' );
        
        if( $field && $filename )
        {
            // Удаляем указанное изображение.
            $this->obj->delete_file( $field, $filename );
            // Записываем изменения.        
            $this->obj->save();
        }
        
        // Продолжаем в режиме правки.
        $this->action = ACT_EDIT;
    }

    // protected
    function get_form( $parent, $prefix, $action )
    {
        $html = '';
    
        // Заголовок.
//        $html .= get_subheader( $action == ACT_ADD 
//                ? 'Добавить ' . $this->get_object_title()
//                : 'Редактировать ' . $this->get_object_title() );
                        
        // Возможные ошибки при редактировании.
        $html .= get_formatted_error( $this->err );
            
        return $html . $this->obj->get_form( $parent, $prefix, $action );
    }
    
    // Hook. Вызывается сразу после добавления объекта в базу данных.
    function do_after_add( $id )
    {}
    
    // Hook. Вызывается сразу после правки сведений об объекте.
    function do_after_edit( $id )
    {}
    
    // Hook. Вызывается сразу после удаления объекта из базы данных.
    function do_after_delete( $id )
    {}
        
    // Override this function.
    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
//        return new Object($id, $form, $from_form);
    }
    
    // Override this function.
    function get_object_title()
    {
        // Lower case! Винительный падеж!
        // Используется в контексте с глаголом:
        // "Добавить статью".
        return $this->obj->class_title();
    }
}


class TreeEditObject
{
    var $tree;
    var $action;
    var $prefix;
    var $list_first;
    var $err;
    var $obj;
    var $page;
    var $parent_page;
    var $form;
    // нужна ли нам форма
    var $show_form = true;
    // Флаг открытия вложенных форм в "девственном состоянии".
    var $start_clean;

    // Constructor.
    function TreeEditObject( &$tree, $parent_page, $prefix = '', $start_clean = false, 
        $list_first = false, $show_form = true )
    {
        $this->tree = $tree;
        $this->prefix = $prefix; // Дополнение к именам действий и форм.
        $this->err = ''; // Сообщение об ошибке при редактировании объекта.
        $this->list_first = $list_first;
        $this->show_form = $show_form;                    
        if( !$start_clean )
        {
            // Текущее действие. По умолчанию добавление объекта.
            $this->action = get_http_var( $this->prefix . 'a', ACT_ADD );
            // ID текущего объекта. По умолчанию отсутствует.
            $this->id = get_http_var( $this->prefix . 'id', NULL );
            // Объект с нулевым или пустым номером не существует.
            if( !$this->id ) $this->id = NULL;
        }
        else
        {
            $this->action = ACT_ADD;
            $this->id = NULL;
        }
        
        // Признак создания вложенных формы без учета переданных переменных.
        $this->start_clean = $start_clean;

        // Форма с данными.
        $this->form = get_http_var( $this->prefix . 'form', NULL );
                                        
        // Текущий объект. Создается в порядке очередности:
        // 1) на основе формы, если производилась правка или добавление,
        // 2) по номеру, если он указан,
        // 3) или новый пустой объект для добавления.
        if(    ( $this->action == ACT_ADD_PROC ) 
            || ( $this->action == ACT_EDIT_PROC )
            || !is_null( $this->form ) )
        {
            $this->obj = $this->create_object( $this->id, 
                $this->form, true );
        }
        else
        {
            $this->form = NULL;
            $this->obj = $this->create_object( $this->id );
        }
        // Реакция на действия пользователя.
        if( strpos( $this->action, '_proc' ) )
        {
            $f = 'do_' . str_replace( '_proc', '', $this->action );
            $this->$f();
        }
        
        // Если текущий объект отсутствует, можно только добавить объект.
        if( !isset( $this->id ) ) $this->action = ACT_ADD;

        // Адрес родительской страницы.
        $this->parent_page = $parent_page;
        // Адрес текущего состояния страницы.
        $this->page = $this->parent_page 
            . '&' . $this->prefix . 'a=' . $this->action;
        if( $this->action == ACT_EDIT )
        {
            $this->page .= '&' . $this->prefix . 'id=' . $this->id;
        }
    }
    
    /**
     * Возвращает сформированный код правки объекта.
     *
     * @return HTML
     */
    function show()
    {
        $html = '';
        if ($this->show_form) {
            // Вспомогательные скрипты.
            $html .= $this->get_delete_script();
    
            $list_title = $this->get_list_title();
            if( $list_title ) $html .= get_header( $list_title );
        }
        if (!$this->show_form) {
            // Выводим только список
            $html .= $this->show_list();
        } elseif ( $this->list_first ) {
            // Отображаем список объектов.
            $html .= $this->show_list();
            // Отображаем форму редактирования объекта.
            $html .= $this->show_form();
        } else {
            // Отображаем форму редактирования объекта.
            $html .= $this->show_form();
            // Отображаем список объектов.
            $html .= $this->show_list();
        }
        
        return $html;
    }
    
    // private
    function show_list()
    {
        $html = '';
        
        if( !$this->prefix )
        {
            $html .= '<form action="' . $this->parent_page 
                . '" name="' . $this->prefix . 'count"' 
                . ' method="post" enctype="multipart/form-data">';
        }
        
        $html .= $this->create_objects_list( $this->parent_page, 
            $this->prefix, ACT_EDIT );
            
        if( !$this->prefix ) $html .= '</form>';
        
        return $html;
    }
    
    // private
    function show_form()
    {
        $html = '';
        
        if( !$this->prefix )
        {
            $html .= '<form action="' . $this->parent_page 
                . '" name="' . $this->prefix . 'form"' 
                . ' method="post" enctype="multipart/form-data">';
        }

        $html .= $this->show_tree_actions();
        
        $html .= $this->get_form( $this->page, $this->prefix, 
            $this->action, $this->start_clean );
                        
                        
        if( !$this->prefix ) $html .= '</form>';
        
        return $html;
    }

    function show_tree_actions()
    {
        $html = '';
    
        $tpl = new Template();
        $tpl->set_file( 'a', PATH_TO_ADMIN_TPL . 'edit_object_tree_action.tpl' );
        $tpl->set_block( 'a', 'add_', 'add__' );
        $tpl->set_block( 'a', 'edit_', 'edit__' );
        $tpl->set_var( 'PREFIX', $this->prefix );
        
        list( $ids, $tree ) = $this->tree->get_tree();
        if( $ids )
        {
//            $f = $this->create_filter();
//            $f->id_in( $ids );
//            $list = array();
//            $this->get_list( $list, '', $f );

            $options = array( 0 => 'Корневой элемент' );
//            foreach( $tree as $v )
//            {
//                $obj = $this->create_object( $v[ 'NodeID' ] );
//                $options[ $v ] = $obj->title();
//            }
            $this->create_tree_select_options( $options, $tree );
        
            $tpl->set_var( 'PARENT_OPTIONS', get_select_options( 0, $options, false ) );
            $options[ 0 ] = 'В самом начале';
            $tpl->set_var( 'AFTER_OPTIONS', get_select_options( 0, $options, false ) );
        }
        else
        {
            $tpl->set_var( 'PARENT_OPTIONS', '' );
            $tpl->set_var( 'AFTER_OPTIONS', '' );
        }

        if( $this->action == ACT_ADD ) $tpl->parse( 'add__', 'add_' );
        else
        {
            // Добавить блок для изменения родительского элемента и местоположения.
            $html .= get_subheader( 'Переместить ' . $this->get_object_title() );
        
            $tpl->parse( 'edit__', 'edit_' );
        }
                
        return $html . $tpl->parse( 'c', 'a' );
    }

    // private
    function create_tree_select_options( &$options, &$tree, $level = 0 )
    {
        // Сдвиг элемента дерева.                
        $pre = '';
        for( $i = 0; $i < $level * 5; ++$i ) $pre .= '&nbsp;';
        
        foreach( $tree as $v )
        {
            if( is_array( $v ) )
            {
                $this->create_tree_select_options( $options, $v, $level + 1 );
                continue;
            }
            
            $obj = $this->create_object( $v );
            $options[ $v ] = $pre . htmlspecialchars( $obj->title() );
        }
    }
        
    // private
    function do_select()
    {
        // По умолчанию выбор объекта означает открытие его в режиме правки,
        // соответственно вложенные формы открываются в "девственном состоянии".
        $this->start_clean = true;
        $this->action = ACT_EDIT;
    }
    
    // private
    function do_add()
    {
        // Добавление нового объекта на основе введенных данных.
        $this->err = $this->obj->check();
                
        if( $this->err == '' )
        {
            // Если ошибок не было, сохраняем объект.
            $result = $this->obj->save();

            // Сразу после добавления вставляем объект в дерево.
            $parent = isset( $this->form[ '__Parent' ] ) ? $this->form[ '__Parent' ] : 0;
            $this->tree->add_node( $this->obj->id(), $parent );
                        
            $this->do_after_add( $this->obj->id() );
            
            if( $result == SAVE_AND_EDIT )
            {
                $this->action = ACT_EDIT;
                $this->id = $this->obj->id();
            }
            else
            {
                // И заводим новый для возможного добавления.
                $this->obj = $this->create_object();
                $this->action = ACT_ADD;
            }
        }
        else $this->action = ACT_ADD;
    }
    
    // private
    function do_edit()
    {
        // Исправление существующего объекта.
        $this->err = $this->obj->check();
        
        // Если были ошибки при редактировании, остаемся в редакторе.
        if( $this->err ) $this->action = ACT_EDIT;
        else 
        {
            // Иначе сохраняем объект.
            $this->obj->save();
            
            $this->do_after_edit( $this->obj->id() );
            
            // Заводим новый для возможного добавления.
            $this->obj = $this->create_object();
            // И переходим в режим добавления объекта.
            $this->action = ACT_ADD;
        }
        
    }
    
    // private
    function do_delete()
    {
        // Удалить объект из дерева. Если удаляется не корневой узел,
        // ничего не делать.
        if( $this->tree->delete_leaf_node( $this->obj->id() ) )
        {
            // Удаление существующего объекта.
            $this->err = $this->obj->delete();

            $this->do_after_delete( $this->obj->id() );
        }
                
        // Переходим в режим добавления объекта.
        $this->obj = $this->create_object();
        $this->action = ACT_ADD;
    }
    
    // private
    function do_parent()
    {
        // Переместить ветку в указанное место.
        if( isset( $this->form[ '__Parent' ] )
            && $this->tree->move_node_inside_last( $this->obj->id(), $this->form[ '__Parent' ] ) )
        {                
            // Переходим в режим добавления объекта.
            $this->obj = $this->create_object();
            $this->action = ACT_ADD;
        }
        else
        {
            $this->err = 'Нельзя перемещать элемент внутрь самого себя!';
            $this->action = ACT_EDIT;
        }
    }
    
    // private
    function do_move()
    {
        // Переместить ветку в указанное место.
        if( isset( $this->form[ '__After' ] )
            && $this->tree->move_node_after( $this->obj->id(), $this->form[ '__After' ] ) )
        {                
            // Переходим в режим добавления объекта.
            $this->obj = $this->create_object();
            $this->action = ACT_ADD;
        }
        else $this->action = ACT_EDIT;
    }
        
    // protected
    function create_objects_list( $parent, $prefix, $action )
    {
        $html = '';
        
        // В режиме редактирования объекта можно перейти в режим добавления.
        if( $this->action != ACT_ADD )
        {
            $html .= get_link( 'Добавить ' . $this->get_object_title(), 
                $parent . '&' . $prefix . 'a=' . ACT_ADD );
        }
                
        $table = new PslAdmTbl();
        $table->mSessionPrefix = $prefix . '_a_a_u';
        $table->mDownImg       = PATH_TO_ADMIN . 'img/down.gif';
        $table->mUpImg         = PATH_TO_ADMIN . 'img/up.gif';
        $table->mSortParam     = $prefix . 'sort';
        $table->mSortTypeParam = $prefix . 'sort_dir';
        $table->mPageParam     = $prefix . 'page';
        $table->mInPageParam   = $prefix . 'count';
        $table->mRecCntShow    = true;
        $table->SetInPageOptions( get_inpage_array() );
        
        // Настроить отображаемые в таблице поля.
        $default_sort = '';
        $rows = array();
        $this->get_row_config( $rows, $default_sort );
        
        $sort = array();
        $titles = array();
        foreach( $rows as $k => $v )
        {
            $sort[] = $v->sortable() ? $k : '';
            $titles[] = $v->title();
        }
        if ($this->show_form) {
            $titles[] = 'Действия';
        }
        global $this_page;
    
        $table->mSortDefault = $default_sort;
        $table->mSortFields = $sort;
        $table->mRecordsCnt = $this->get_count();
        $table->SetHead( $parent, $titles, array() );
        
        list( $ids, $tree ) = $this->tree->get_tree();

        $this->create_objects_tree( $tree, 0, 
            $table, $rows, $parent, $prefix, $action );
                     
        return $html . $table->GetTable();
    }

    function create_objects_tree( &$tree, $level, 
        &$table, &$rows, $parent, $prefix, $action )
    {
        foreach( $tree as $v )
        {
            if( is_array( $v ) )
            {
                $this->create_objects_tree( $v, $level + 1, 
                    $table, $rows, $parent, $prefix, $action );
                continue;
            }

            $obj = $this->create_object( $v );
                        
            $this->setup_row( $level, $table, $rows, $obj,
                    '<a href="' . $parent 
                    . '&' . $prefix . 'a=' . $action 
                    . '&' . $prefix . 'id={ID}">{TITLE}</a>',
                    '<a href="javascript:' . $prefix 
                    . 'delete_record( {ID} )"><img src="' 
                    . PATH_TO_ADMIN . 'img/del.gif" border=0 alt="Удалить"></a>' );
        }
    }
        
    // protected
    function setup_row( $level, &$table, $rows, $obj, $edit_tpl, $delete_tpl )
    {
        $data = array();
        $this->get_row_data( $obj, $data );

        // Сдвиг элемента дерева.                
        $pre = '';
        for( $i = 0; $i < $level * 5; ++$i ) $pre .= '&nbsp;';
        
        $i = 0;
        foreach( $rows as $k => $v )
        {
            if( $v->linkable() )
            {
                $data[ $i ] = ( $k == 'ID' ? '' : $pre ) 
                    . str_replace( array( '{ID}', '{TITLE}' ), 
                    array( $obj->id(), $data[ $i ] ), $edit_tpl );
            }
                
            ++$i;
        }
        // Последним полем всегда идет список действий с объектом.
        // Нет, не всегда. Только если для это объекта была определена форма. 
        if ($this->show_form) {
            $data[] = str_replace( array( '{ID}' ), array( $obj->id() ), 
                '<center>' . $delete_tpl . '</center>' );
        }
        $table->SetRow( $data );
    }
    
    // protected    
    function get_row_config( &$rows, &$default_sort )
    {
        $this->obj->get_row_config( $rows, $default_sort );
    }
    
    // protected
    function get_row_data( $obj, &$data )
    {
        $obj->get_row_data( $data );
    }

    // protected
    function get_form( $parent, $prefix, $action, $start_clean = false )
    {
        $html = '';
    
        // Заголовок.
        $html .= get_subheader( $action == ACT_ADD 
                ? 'Добавить ' . $this->get_object_title()
                : 'Редактировать ' . $this->get_object_title() );
                        
        // Возможные ошибки при редактировании.
        $html .= get_formatted_error( $this->err );
            
        return $html . $this->obj->get_form( $parent, $prefix, $action, 
            $start_clean );
    }
    
    // private
    function get_delete_script()
    {
        $msg = 'Удалить ' . $this->get_object_title() . '?';
        $funcName = $this->prefix . 'delete_record';
        $r = "\n<script language=javascript>\n<!--\n" 
            . "function " . $funcName . "( id ) { if( confirm( '" . $msg 
            . "' ) ) location.href = '" 
            . $this->parent_page . '&' 
            . $this->prefix . 'a=' . ACT_DEL_PROC . '&'
            . $this->prefix . 'id='
            . "' + id; }\n" 
            . "//-->\n"
            ."</script>\n";
        return $r;
    }
    
    // Hook. Вызывается сразу после добавления объекта в базу данных.
    function do_after_add( $id )
    {}
    
    // Hook. Вызывается сразу после правки сведений об объекте.
    function do_after_edit( $id )
    {}
    
    // Hook. Вызывается сразу после удаления объекта из базы данных.
    function do_after_delete( $id )
    {}
        
    // Override this function.
    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
//        return new Object($id, $form, $from_form);
    }
    
    // Override this function.
    function get_list( &$list, $order_limit, $filter = NULL )
    {
//        global $g_object_db;
//        return $g_object_db->load_list( $list, $filter, $order_limit );
    }
    
    // Override this function.
    function get_count()
    {
        $list = array();
        $this->get_list( $list, '' );
        return count( $list );
//        global $g_object_db;
//        return $g_object_db->count();
    }
    
    // Override this function.
    function get_list_title()
    {
//        return "Object List Title";
    }
    
    // Override this function.
    function get_object_title()
    {
        // Lower case! Винительный падеж!
        // Используется в контексте с глаголом:
        // "Добавить статью".
        return $this->obj->class_title();
    }
}


?>
