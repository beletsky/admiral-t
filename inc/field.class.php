<?php


include_once( PATH_TO_ROOT . 'inc/image.class.php' );
include_once( PATH_TO_ROOT . 'inc/binary_file.class.php' );


define( 'PATH_TO_ADMIN_TPL', PATH_TO_ADMIN . 'tpl/' );


/*
*
*   Edits.
*
*/

class EditFieldBase
{
    var $m_form_suffix = '';
    var $m_field;
    var $m_name;
    
    function EditFieldBase( &$field, $name, $form_suffix = 'base' )
    {
        $this->m_form_suffix = $form_suffix;
        $this->m_field = $field;
        $this->m_name = $name;
        $field->edit( $this );
    }
    
    function check() { return array(); }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_' . $this->m_form_suffix . '.tpl' );
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', htmlspecialchars( $value ) );

        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        return $form[ $this->m_field->name() ];
    }
}


class EditFieldWithCaption
{
    private $m_caption;
    private $m_field;

    function EditFieldWithCaption( &$field, $caption )
    {
        $this->m_caption = $caption;
        $this->m_field = $field->edit( $this );
    }
    
    function check() { $this->m_field->check(); }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_caption.tpl' );
        $tpl->set_var( 'NAME', $this->m_caption );

        return $tpl->parse( 'c', 'f' ) . $this->m_field->get_form( $value, $prefix );
    }
    
    function convert( $form )
    {
        return $this->m_field->convert( $form );
    }
}


class EditFieldHidden
    extends EditFieldBase
{
    function EditFieldHidden( &$field )
    {
        parent::EditFieldBase( $field, '', 'hidden' );
    }
}


class EditFieldString
    extends EditFieldBase
{
    function EditFieldString( &$field, $name )
    {
        parent::EditFieldBase( $field, $name, 'string' );
    }
}


class EditFieldStringShow
    extends EditFieldBase
{
    function EditFieldStringShow( &$field, $name )
    {
        parent::EditFieldBase( $field, $name, 'string_show' );
    }
}


class EditFieldInteger
    extends EditFieldBase
{
    function EditFieldInteger( &$field, $name )
    {
        parent::EditFieldBase( $field, $name, 'integer' );
    }
}


class EditFieldCode
    extends EditFieldBase
{
    function EditFieldCode( &$field, $name )
    {
        parent::EditFieldBase( $field, $name, 'code' );
    }
    
    function convert( $form )
    {
        return str_replace( array( '\'', '`', '_', '/', '\\' ), array( '', '', '-', '-', '-' ), 
            strtolower( transliterate( $form[ $this->m_field->name() ] ) ) );
    }
}


class EditFieldTextArea
    extends EditFieldBase
{
    function EditFieldTextArea( &$field, $name )
    {
        parent::EditFieldBase( $field, $name, 'textarea' );
    }
}


class EditFieldOption
    extends EditFieldBase
{
    function EditFieldOption( &$field, $name )
    {
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_option.tpl' );
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'OPTIONS', create_select_options( 
            $this->m_field->get_options(), $value ) );

        return $tpl->parse( 'c', 'f' );
    }
}


class EditFieldRichEditor
    extends EditFieldBase
{
    var $m_style;
    var $m_css;
    
    function EditFieldRichEditor( &$field, $name, $style, $css = '' )
    {
        $this->m_style = $style;
        $this->m_css = $css ? $css : 'ck_styles';
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $editor = new CKeditor( PATH_TO_EDITOR );
        $editor->returnOutput = true;
        $editor->config['width'] = 600;
        $editor->config['height'] = $this->m_style == 'Basic' ? 100 : 350;
        $editor->config['toolbar'] = $this->m_style;
        $editor->config['skin'] = 'v2';
    
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_richeditor.tpl' );
        $tpl->set_var( 'PATH_TO_ADMIN', PATH_TO_ADMIN );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );
        $tpl->set_var( 'EDITOR', $editor->editor( $prefix . 'form[' . $this->m_field->name() . ']',
            $value ? $value : '', 
            array( 
                'stylesCombo_stylesSet' => 'my_styles:' . SITE_ROOT_PATH . 'css/' . $this->m_css . '.js',
                'contentsCss' => SITE_ROOT_PATH . 'css/' . $this->m_css . '.css'
            ), array(), $this->m_field->name() ) );
        $tpl->set_var( 'PATH_TO_ROOT', PATH_TO_ROOT );

        return $tpl->parse( 'c', 'f' );
    }
}


class EditFieldFile
    extends EditFieldBase
{
    function EditFieldFile( &$field, $name )
    {
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_file.tpl' );
        
        $tpl->set_block( 'f', 'file_', 'file__' );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );

        $file = $this->m_field->create_file( $value );
                                        
        $tpl->set_var( 'URL', $file->path_to_file() );
        $tpl->set_var( 'FILENAME', $file->filename() );
        
        if( $value ) $tpl->parse( 'file__', 'file_' );
        
        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        $index = $this->m_field->name();
        $old_filename = $form[ $index ];
            
        if(    isset( $_FILES[ $index ][ 'tmp_name' ] )
            && $_FILES[ $index ][ 'tmp_name' ] )
        {
            $file = $this->m_field->create_file();
            $file->get_from_file( $_FILES[ $index ][ 'tmp_name' ],
                $_FILES[ $index ][ 'name' ] );
                
            if( !$file->errors() )
            {
                if( $old_filename )
                {
                    // Загрузить старый объект из базы данных, и удалить из него картинки.
                    $old_file = $this->m_field->create_file( $old_filename );
                    $old_file->delete();
                }
                
                return $file->filename();
            }
        }
        
        return $old_filename;
    }
}


class EditFieldImage
    extends EditFieldBase
{
    var $m_limits;
    var $m_full_size_limits;
    
    function EditFieldImage( &$field, $name, 
        $limits = array(), $full_size_limits = array() )
    {
        $this->m_limits = $limits;
        $this->m_full_size_limits = $full_size_limits;
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . $this->form_tpl() );
        
        $tpl->set_block( 'f', 'image_', 'image__' );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );
        
        $tpl->set_var( 'SMALLWIDTH', isset( $this->m_limits[ 0 ] ) 
            ? $this->m_limits[ 0 ] : 0 );
        $tpl->set_var( 'LARGEWIDTH', isset( $this->m_full_size_limits[ 0 ] ) 
            ? $this->m_full_size_limits[ 0 ] : 0 );

        $image = $this->m_field->create_file( $value );
                                        
        $tpl->set_var( 'URL', $image->path_to_image() );
        $tpl->set_var( 'FULL_SIZE_URL', $image->path_to_full_size_image() );
        $tpl->set_var( 'FILENAME', $image->filename() );
        $tpl->set_var( 'ALT', $value );
        
        if( $value ) $tpl->parse( 'image__', 'image_' );
        
        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        $index = $this->m_field->name();
        $old_image_file = $form[ $index ];

        $limits = $this->m_limits;
        $full_size_limits = $this->m_full_size_limits;
        
        $resize = isset( $form[ $index . '_Resize' ] );
        if( $resize )
        {
            if( isset( $form[ $index . '_SmallWidth' ] ) )
            {
                $limits[ 0 ] = $form[ $index . '_SmallWidth' ];
            }
            
            if( isset( $form[ $index . '_LargeWidth' ] ) )
            {
                $full_size_limits[ 0 ] = $form[ $index . '_LargeWidth' ];
            }
        }
                    
        if(    isset( $_FILES[ $index ][ 'tmp_name' ] )
            && $_FILES[ $index ][ 'tmp_name' ] )
        {
            // Два разных изображения могут быть загружены только в случае,
            // когда не указана необходимость изменения размеров изображения.
            if( !$resize && isset( $_FILES[ $index . '_Small' ][ 'tmp_name' ] ) )
            {
                $temp = array( $_FILES[ $index . '_Small' ][ 'tmp_name' ], 
                    $_FILES[ $index ][ 'tmp_name' ] );
                $orig = array( $_FILES[ $index . '_Small' ][ 'name' ],
                    $_FILES[ $index ][ 'name' ] );
            }
            else
            {
                $temp = $_FILES[ $index ][ 'tmp_name' ];
                $orig = $_FILES[ $index ][ 'name' ];
            }
                
            $image = new Image();
            $image->get_from_file( $temp, $orig, $limits, $full_size_limits );
                
            if( !$image->errors() )
            {
                if( $old_image_file )
                {
                    // Загрузить старый объект из базы данных, и удалить из него картинки.
                    $old_image = $this->m_field->create_file( $old_image_file );
                    $old_image->delete();
                }
                
                return $image->filename();
            }
        }
        
        return $old_image_file;
    }
    
    function form_tpl() { return 'edit_field_image.tpl'; }
}


class EditFieldImagePair
    extends EditFieldImage
{
    function EditFieldImagePair( &$field, $name, 
        $limits = array(), $full_size_limits = array() )
    {
        parent::EditFieldImage( $field, $name, $limits, $full_size_limits );
    }
    
    function form_tpl() { return 'edit_field_imagepair.tpl'; }
}


class EditFieldImageList
    extends EditFieldBase
{
    var $m_limits;
    var $m_full_size_limits;
    
    function EditFieldImageList( &$field, $name, 
        $limits = array(), $full_size_limits = array() )
    {
        $this->m_limits = $limits;
        $this->m_full_size_limits = $full_size_limits;
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_imagelist.tpl' );
        $tpl->set_block( 'f', 'image_item_', 'image_item__' );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );

        $images = $this->m_field->get_files( $value );
                                        
        foreach( $images as $v )
        {
            if( !$v ) continue;

            $image = $this->m_field->create_file( $v );
            
            $tpl->set_var( 'URL', $image->path_to_image() );
            $tpl->set_var( 'FULL_SIZE_URL', $image->path_to_full_size_image() );
            $tpl->set_var( 'FILENAME', $image->filename() );
            $tpl->set_var( 'ALT', $value );
            
            $tpl->parse( 'image_item__', 'image_item_', true );
        }
        
        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        // Загрузить новые изображения.
        $index = $this->m_field->name();
        $value = $form[ $index ];
        
        if( isset( $_FILES[ $index ][ 'tmp_name' ] ) )
        {
            // Получить текущий список файлов.
            $files = $this->m_field->get_files( $value );
            
            // Пройтись по всем полям загрузки изображений.
            foreach( $_FILES[ $index ][ 'tmp_name' ] as $k => $v )
            {
                if( !$v ) continue;

                // Создать новое изображение.                
                $file = new Image();
                $file->get_from_file( $v,
                    $_FILES[ $index ][ 'name' ][ $k ],
                    $this->m_limits, $this->m_full_size_limits );
                    
                // Добавить новое изображение в список.
                if( !$file->errors() ) $files[] = $file->filename();
            }
            
            // Сформировать новый список изображений.
            return $this->m_field->set_files( $files );
        }
        
        return $value;
    }
}


class EditFieldImageTitleList
    extends EditFieldBase
{
    var $m_limits;
    var $m_full_size_limits;
    
    function EditFieldImageTitleList( &$field, $name, 
        $limits = array(), $full_size_limits = array() )
    {
        $this->m_limits = $limits;
        $this->m_full_size_limits = $full_size_limits;
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . $this->form_tpl() );
        $tpl->set_block( 'f', 'image_item_', 'image_item__' );
        $tpl->set_block( 'f', 'image_add_item_', 'image_add_item__' );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );

        $images = $this->m_field->get_files( $value );

        // Отобразить существующие файлы.
        $count = 0;
        foreach( $images as $v )
        {
            if( !$v[ 'File' ] ) continue;

            $image = $this->m_field->create_file( $v[ 'File' ] );
            
            $tpl->set_var( 'URL', $image->path_to_image() );
            $tpl->set_var( 'FULL_SIZE_URL', $image->path_to_full_size_image() );
            $tpl->set_var( 'FILENAME', $image->filename() );
            $tpl->set_var( 'ALT', $v[ 'Title' ] );
            $tpl->set_var( 'TITLE', $v[ 'Title' ] );
            $tpl->set_var( 'INDEX', $count );
            $tpl->set_var( 'INDEX_1', $count + 1 );
            
            $tpl->parse( 'image_item__', 'image_item_', true );
            
            ++$count;
        }
        
        // Отобразить форму для добавления новых файлов.
        for( $i = 0; $i < 5; ++$i )
        {
            $tpl->set_var( 'INDEX', $count );
            $tpl->set_var( 'INDEX_1', $count + 1 );
            $tpl->parse( 'image_add_item__', 'image_add_item_', true );
            ++$count;
        }
        
        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        // Загрузить новые изображения.
        $index = $this->m_field->name();
        $value = $form[ $index ];
        
        // Получить текущий список файлов.
        $files = $this->m_field->get_files( $value );

        // Добавить в список новые изображения.
        if( isset( $_FILES[ $index ][ 'tmp_name' ] ) )
        {
            // Пройтись по всем полям загрузки изображений.
            foreach( $_FILES[ $index ][ 'tmp_name' ] as $k => $v )
            {
                if( !$v ) continue;

                if(    isset( $_FILES[ $index . '_Full' ][ 'tmp_name' ][ $k ] )
                    && $_FILES[ $index . '_Full' ][ 'tmp_name' ][ $k ] )
                {
                    $temp = array( $v, $_FILES[ $index . '_Full' ][ 'tmp_name' ][ $k ] );
                    $orig = array( $_FILES[ $index ][ 'name' ][ $k ],
                        $_FILES[ $index . '_Full' ][ 'name' ][ $k ] );
                }
                else
                {
                    $temp = $v;
                    $orig = $_FILES[ $index ][ 'name' ][ $k ];
                }
                
                // Создать новое изображение.                
                $file = new Image();
                $file->get_from_file( $temp, $orig,
                    $this->m_limits, $this->m_full_size_limits );
                    
                // Добавить новое изображение в список.
                if( !$file->errors() ) $files[ $k ][ 'File' ] = $file->filename();
            }
        }

        // Обработать смену описаний изображений.
        $titles = $form[ $index . '_Notes' ];
        foreach( $titles as $k => $v )
        {
            // Описание для несуществующего файла сохранять не надо.
            if( !isset( $files[ $k ][ 'File' ] ) ) continue;
            $files[ $k ][ 'Title' ] = $v;
        }
                    
        // Сформировать новый список изображений.
        return $this->m_field->set_files( $files );
    }
    
    function form_tpl() { return 'edit_field_imagetitlelist.tpl'; }
}


class EditFieldImageFullTitleList
    extends EditFieldImageTitleList
{
    function EditFieldImageFullTitleList( &$field, $name, 
        $limits = array(), $full_size_limits = array() )
    {
        parent::EditFieldImageTitleList( $field, $name, $limits, $full_size_limits );
    }
    
    function form_tpl() { return 'edit_field_imagefulltitlelist.tpl'; }
}


class EditFieldFlag
    extends EditFieldBase
{
    function EditFieldFlag( &$field, $name )
    {
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_flag.tpl' );
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );
        $tpl->set_var( 'CHECKED', $value ? 'checked' : '' );

        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        return isset( $form[ $this->m_field->name() . '_CheckBox' ] ) ? 1 : 0;
    }
}


class EditFieldDateSelect
    extends EditFieldBase
{
    function EditFieldDateSelect( &$field, $name )
    {
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', $this->tpl_file() );
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );
        $tpl->set_var( 'CHECKED', $value ? 'checked' : '' );
        
        $time = sql_timestamp_to_time( $value );
        $tpl->set_var( 'TIME', date( 'H:i', $time ) );
        $tpl->set_var( 'DATE', date( 'd.m.Y', $time ) );

        return $tpl->parse( 'c', 'f' );
    }
    
    function tpl_file() { return PATH_TO_ADMIN_TPL . 'edit_field_date.tpl'; }
}


class EditFieldDateTimeSelect
    extends EditFieldDateSelect
{
    
    function tpl_file() { return PATH_TO_ADMIN_TPL . 'edit_field_datetime.tpl'; }
}


class EditFieldDateTimeShow
    extends EditFieldBase
{
    function EditFieldDateTimeShow( &$field, $name )
    {
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_date_time_show.tpl' );
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        $tpl->set_var( 'VALUE', $value );
        $tpl->set_var( 'CHECKED', $value ? 'checked' : '' );
        
        $time = sql_timestamp_to_time( $value );
        $tpl->set_var( 'TIME', date( 'H:i', $time ) );
        $tpl->set_var( 'DATE', date( 'd.m.Y', $time ) );
        
        global $g_month_names_gen;
        $tpl->set_var( 'DATESTR', date( 'd ', $time ) 
            . $g_month_names_gen[ date( 'n', $time ) ]
            . date( ' Y', $time ) );

        return $tpl->parse( 'c', 'f' );
    }
}


class EditFieldAssociativeListOptions
    extends  EditFieldBase
{
    var $m_options;
    
    function EditFieldAssociativeListOptions( &$field, $name, $options )
    {
        $this->m_options = $options;
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        global $g_options;
        $options = $g_options->GetOptionList( $this->m_options );
    
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_associative_list.tpl' );
        $tpl->set_block( 'f', 'item_', 'item__' );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        
        foreach( $options as $k => $v )
        {
            $tpl->set_var( 'ITEM_ID', $k );
            $tpl->set_var( 'ITEM_NAME', $v );
            $tpl->set_var( 'ITEM_CHECKED',
                in_array( $k, $value ) ? 'checked' : '' );
            $tpl->parse( 'item__', 'item_', true );
        }

        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        $list = array();
        if( isset( $form[ $this->m_field->name() ] ) )
        {
            foreach( $form[ $this->m_field->name() ] as $k => $v )
            {
                $list[] = $k;
            }
        }
        
        return $list;
    }
}


class EditFieldObjectsAssociativeList
    extends  EditFieldBase
{
    function EditFieldObjectsAssociativeList( &$field, $name )
    {
        parent::EditFieldBase( $field, $name );
    }
    
    function get_form( $value, $prefix = '' )
    {
        $tpl = new Template();
        $tpl->set_file( 'f', PATH_TO_ADMIN_TPL . 'edit_field_associative_list.tpl' );
        $tpl->set_block( 'f', 'item_', 'item__' );
        
        $tpl->set_var( 'NAME', $this->m_name );
        $tpl->set_var( 'PREFIX', $prefix );
        $tpl->set_var( 'FIELD', $this->m_field->name() );
        
        $options = $this->m_field->get_options();
        foreach( $options as $k => $v )
        {
            $tpl->set_var( 'ITEM_ID', htmlspecialchars( $v[ 0 ] ) );
            $tpl->set_var( 'ITEM_NAME', htmlspecialchars( $v[ 1 ] ) );
            $tpl->set_var( 'ITEM_CHECKED',
                in_array( $v[ 0 ], $value ) ? 'checked' : '' );
            $tpl->parse( 'item__', 'item_', true );
        }

        return $tpl->parse( 'c', 'f' );
    }
    
    function convert( $form )
    {
        $list = array();
        if( isset( $form[ $this->m_field->name() ] ) )
        {
            foreach( $form[ $this->m_field->name() ] as $k => $v )
            {
                $list[] = $k;
            }
        }
                
        return $list;
    }
}


/*
*
*   Fields.
*
*/

class FieldBase
{
    var $m_name = '';
    var $m_default = '';
    var $m_edit = NULL;
    
    function FieldBase( $name, $default = '' )
    {
        $this->m_name = $name;
        $this->m_default = $default;
    }
    
    function edit( &$edit ) { $prev_edit = $this->m_edit; $this->m_edit = $edit; return $prev_edit; }
    
    function name() { return $this->m_name; }
    function tpl_name( $block = '' ) { return strtoupper( $block . $this->name() ); }
        
    function place( &$obj, &$tpl, $block = '' )
    {
        $value = $obj->data( $this );
        $tpl->set_var( $this->tpl_name( $block ), $value );
        return $value ? true : false;
    }

    function get_form( &$obj, $prefix = '' )
    {
        $value = $obj->data( $this );
        return $this->m_edit->get_form( $value, $prefix );
    }
    
    function value( $raw_data ) { return $raw_data; }
    function value_sql( $raw_data ) { return mysql_real_escape_string( $raw_data ); }
    function value_default() { return $this->m_default; }
    
    function convert_from_form( $form )
    {
//        $value = isset( $form[ $this->name() ] ) 
//            ? $this->m_edit->convert( $form )
//            : $this->value_default();
        $value = $this->m_edit->convert( $form );
        return $value;
    }
}


class FieldCode
    extends FieldBase
{
    function FieldCode( $name, $title = '', $default = '' ) 
    {
        parent::FieldBase( $name, $default );
        $edit = new EditFieldCode( $this, $title );
    }
        
    function value_sql( $raw_data ) { return '\'' . parent::value_sql( $raw_data ) . '\''; }
}


class FieldHost
    extends FieldCode
{
    function FieldHost( $host )
    {
        parent::FieldCode( 'Host', '', $host );
        $edit = new EditFieldHidden( $this );
    }
}


class FieldURL
    extends FieldCode
{
    function FieldURL( $name, $title = '', $default = '' )
    {
        parent::FieldCode( $name, $default );
        $edit = new EditFieldString( $this, $title );
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        $value = $obj->data( $this );
        $tpl->set_var( $this->tpl_name( $block ), htmlspecialchars( $value ) );
        $tpl->set_var( $this->tpl_name( $block ) . '_URL', $value );
        return $value ? true : false;
    }
}


class FieldOption
    extends FieldCode
{
    var $m_code;

    function FieldOption( $name, $title, $code, $default = '' )
    {
        parent::FieldCode( $name, $default );
        $this->m_code = $code;
        $edit = new EditFieldOption( $this, $title );
    }

    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        global $g_options;
        $list = $g_options->GetOptionList( $this->m_code );
        $value = $obj->data( $this );
        $tpl->set_var( $this->tpl_name( $block ) . '_TEXT', 
            isset( $list[ $value ] ) ? $list[ $value ] : '' );
        return $value ? true : false;
    }

    function get_options()
    {
        global $g_options;
        $list = $g_options->GetOptionList( $this->m_code );
        $options = array();
        foreach( $list as $k => $v ) $options[] = array( $k, $v );
        return $options;
    }
}


class FieldInteger
    extends FieldBase
{
    function FieldInteger( $name, $title = '', $default = 0 ) 
    {
        parent::FieldBase( $name, $default );
        $edit = new EditFieldInteger( $this, $title );
    }
}


class FieldID
    extends FieldInteger
{ 
    function FieldID()
    {
        parent::FieldInteger( 'ID', 0 );
        $edit = new EditFieldHidden( $this );
    }
    
    function value_sql( $raw_data )
    { 
        if( $raw_data ) return parent::value_sql( $raw_data );
        return 'NULL';
    }
}


class FieldCurrency
    extends FieldInteger
{
    function FieldCurrency( $name, $title = '', $default = 0 ) 
        { parent::FieldInteger( $name, $title, $default ); }

    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $value = $obj->data( $this );
        $tpl->set_var( $this->tpl_name( $block ) . '_TEXT', 
            number_format( $value, 0, '', ' ' ) );
        return $value ? true : false;
    }
}


class FieldString
    extends FieldBase
{
    function FieldString( $name, $title = '', $default = '' ) 
    {
        parent::FieldBase( $name, $default );
        $edit = new EditFieldString( $this, $title );
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        $value = $obj->data( $this );
        $tpl->set_var( $this->tpl_name( $block ), htmlspecialchars( $value ) );
        $tpl->set_var( $this->tpl_name( $block ) . '_WITHOUTHTMLSPECIALCHARS', $value );
        return $value ? true : false;
    }
    
    function value_sql( $raw_data ) 
    {
        return '\'' . parent::value_sql( $raw_data ) . '\'';
    }
}


class FieldTitle
    extends FieldString
{
    function FieldTitle() { parent::FieldString( 'Title', 'Title', '' ); }
}


class FieldText
    extends FieldString
{
    function FieldText( $name, $title, $style = 'Limited', $default = '', $css = '' ) 
    {
        parent::FieldString( $name, $title, $default );
        $edit = new EditFieldRichEditor( $this, $title, $style, $css );
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        $value = RichEditor::clear_html( $obj->data( $this ) );
        $tpl->set_var( $this->tpl_name( $block ), $value );
        return $value ? true : false;
    }
}


class FieldFile
    extends FieldString
{
    function FieldFile( $name, $title = '' ) 
    {
        parent::FieldBase( $name );
        $edit = new EditFieldFile( $this, $title );
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $value = $obj->data( $this );
        
        if( $value )
        {
            $file = $this->create_file( $value );
                                    
            $tpl->set_var( $this->tpl_name( $block ) . '_URL',
                $file->path_to_file() );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME',
                $file->filename() );
        }
        else
        {
            $tpl->set_var( $this->tpl_name( $block ) . '_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME', '' );
        }
        
        return $value ? true : false;
    }

    function check()
    {
        $errors = array();
        $index = $this->name();
        if( isset( $_FILES[ $index ] ) )
        {
            // Если файла в этом месте уже нет, он был успешно
            // обработан при создании объекта.
            if(    !isset( $_FILES[ $index ][ 'tmp_name' ] )
                || 
                (
                   $_FILES[ $index ][ 'tmp_name' ] 
                && !file_exists( $_FILES[ $index ][ 'tmp_name' ] ) 
                ) 
              ) return $errors;
            
            // Для оставшихся файлов надо выдать сообщения об ошибках.
            $errors = array_merge( $errors, BinaryFile::check_params( $index ) );
        }
        
        return $errors;
    }
        
    function create_file( $name = NULL )
    {
        return new BinaryFile( $name );
    }
    
    function delete_file( $value, $filename )
    {
        if( $value == $filename )
        {
            $file = $this->create_file( $value );
            $file->delete();
            return '';
        }
        
        return $value;
    }
}


class FieldImage
    extends FieldString
{
    function FieldImage( $name, $title = '', 
        $limits = array(), $full_size_limits = array() ) 
    {
        parent::FieldBase( $name );
        $edit = new EditFieldImage( $this, $title, $limits, $full_size_limits );
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $value = $obj->data( $this );
        
        if( $value )
        {
            $image = $this->create_file( $value );
                                    
            $tpl->set_var( $this->tpl_name( $block ) . '_URL',
                $image->path_to_image() );
            $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL',
                $image->path_to_full_size_image() );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME',
                $image->filename() );
            $tpl->set_var( $this->tpl_name( $block ) . '_ALT',
                htmlspecialchars( $obj->title() ) );
        }
        else
        {
            $tpl->set_var( $this->tpl_name( $block ) . '_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_ALT', '' );
        }
        
        return $value ? true : false;
    }

    function check()
    {
        $index = $this->name();
        if(    isset( $_FILES[ $index ][ 'tmp_name' ] )
            && $_FILES[ $index ][ 'tmp_name' ] 
            && file_exists( $_FILES[ $index ][ 'tmp_name' ] ) )
        {
            // Иначе надо выдать сообщения об ошибках.
            return Image::check_image_params( $_FILES[ $index ][ 'tmp_name' ],
                    $_FILES[ $index ][ 'name' ] );
        }
        
        return array();
    }
        
    function create_file( $name )
    {
        return new Image( $name );
    }
    
    function delete_file( $value, $filename )
    {
        if( $value == $filename )
        {
            $file = $this->create_file( $value );
            $file->delete();
            return '';
        }
        
        return $value;
    }
}


class FieldImageList
    extends FieldString
{
    var $m_show_first_in_list = true;

    function FieldImageList( $name, $title = '', 
        $limits = array(), $full_size_limits = array(), $show_first_in_list = true ) 
    {
        parent::FieldBase( $name, '' );
        $edit = new EditFieldImageList( $this, $title, $limits, $full_size_limits );
        $this->m_show_first_in_list = $show_first_in_list;
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );

        $images = $this->get_files( $obj->data( $this ) );
            
        $inner = strtolower( $this->tpl_name( $block ) . '_item_' );
        if( $tpl->has_block( $inner )
            ||
            (    $tpl->has_block( $block ) 
              && $tpl->set_block( $block, $inner,  $inner . '_' ) 
            )                     )
        {
            $count = 0;
            foreach( $images as $v )
            {
                if( !$v ) continue;
                if( !$this->m_show_first_in_list && ++$count == 1 ) continue;
                
                $image = new Image( $v );
                                        
                $tpl->set_var( $this->tpl_name( $block ) . '_URL',
                    $image->path_to_image() );
                $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL',
                    $image->path_to_full_size_image() );
                $tpl->set_var( $this->tpl_name( $block ) . '_NAME',
                    $image->filename() );
                $tpl->set_var( $this->tpl_name( $block ) . '_ALT',
                    htmlspecialchars( $obj->title() ) );
                $tpl->parse( $inner . '_', $inner, true );
            }
        }
    
        // Первая картинка идет в случаях, когда нужно
        // только одно изображение.
        if( $images )
        {
            $image0 = new Image( $images[ 0 ] );
            
            $tpl->set_var( $this->tpl_name( $block ) . '_URL',
                $image0->path_to_image() );
            $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL',
                $image0->path_to_full_size_image() );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME',
                $image0->filename() );
        }
        else
        {
            $tpl->set_var( $this->tpl_name( $block ) . '_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME', '' );
        }
        $tpl->set_var( $this->tpl_name( $block ) . '_ALT',
            htmlspecialchars( $obj->title() ) );
        
        return $images ? true : false;
    }

    function check()
    {
        $errors = array();
        $index = $this->name();
        if( isset( $_FILES[ $index ][ 'tmp_name' ] ) )
        {
            foreach( $_FILES[ $index ][ 'tmp_name' ] as $k => $v )
            {
                // Если файла в этом месте уже нет, он был успешно
                // обработан при создании объекта.
                if( !$v || !file_exists( $v ) ) continue;
                
                // Для оставшихся файлов надо выдать сообщения об ошибках.
                $errors = array_merge( $errors, 
                    Image::check_image_params( $v,
                        $_FILES[ $index ][ 'name' ][ $k ] ) );
            }
        }
        
        return $errors;
    }
        
    function create_file( $name )
    {
        return new Image( $name );
    }
    
    // Возвращает массив изображений, разобранный из строки.
    function get_files( $value )
    {
        return $value ? explode( IMAGES_DELIMITER, $value ) : array();
    }
    
    // Собирает строку изображений из массива.
    function set_files( $files_array )
    {
        return implode( IMAGES_DELIMITER, $files_array );
    }
    
    // private.
    // Удаляет в объекте файлы картинок.
    function delete_file( $value, $filename )
    {
        $files = $this->get_files( $value );
                
        foreach( $files as $k => $v )
        {
            if( $v == $filename )
            {
                $file = $this->create_file( $v );
                $file->delete();
                unset( $files[ $k ] );
                
                break;
            }
        }
        
        return $this->set_files( $files );
    }
}


class FieldImageTitleList
    extends FieldString
{
    var $m_show_first_in_list = true;
    var $m_place_from = 0;
    var $m_place_to = NULL;

    function FieldImageTitleList( $name, $title = '', 
        $limits = array(), $full_size_limits = array(), 
        $show_first_in_list = true ) 
    {
        parent::FieldBase( $name, '' );
        $edit = new EditFieldImageTitleList( $this, $title, $limits, $full_size_limits );
        $this->m_show_first_in_list = $show_first_in_list;
    }
    
    function place_params( $from = 0, $to = NULL )
    {
        $this->m_place_from = $from;
        $this->m_place_to = $to;
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $images = $this->get_files( $obj->data( $this ) );
            
        $inner = strtolower( $this->tpl_name( $block ) . '_item_' );
        if( $tpl->has_block( $inner )
            ||
            (    $tpl->has_block( $block ) 
              && $tpl->set_block( $block, $inner,  $inner . '_' ) 
            )                     )
        {
            $count = -1;
            foreach( $images as $v )
            {
                if( !$v[ 'File' ] ) continue;
                
                ++$count;
                
                if( $count < $this->m_place_from ) continue;
                if( isset( $this->m_place_to ) && $count >= $this->m_place_to ) continue;
                if( !$this->m_show_first_in_list && $count == 0 ) continue;
                
                $image = new Image( $v[ 'File' ] );
                                        
                $tpl->set_var( $this->tpl_name( $block ) . '_URL',
                    $image->path_to_image() );
                $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL',
                    $image->path_to_full_size_image() );
                $tpl->set_var( $this->tpl_name( $block ) . '_NAME',
                    $image->filename() );
                $tpl->set_var( $this->tpl_name( $block ) . '_ALT',
                    htmlspecialchars( $obj->title() ) );
                $tpl->set_var( $this->tpl_name( $block ) . '_TITLE',
                    htmlspecialchars( $v[ 'Title' ] ) );
                $tpl->parse( $inner . '_', $inner, true );
            }
        }
    
        // Первая картинка идет в случаях, когда нужно
        // только одно изображение.
        if( $images && $images[ 0 ][ 'File' ] )
        {
            $image0 = new Image( $images[ 0 ][ 'File' ] );
            
            $tpl->set_var( $this->tpl_name( $block ) . '_URL',
                $image0->path_to_image() );
            $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL',
                $image0->path_to_full_size_image() );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME',
                $image0->filename() );
            $tpl->set_var( $this->tpl_name( $block ) . '_TITLE',
                $images[ 0 ][ 'Title' ] );
        }
        else
        {
            $tpl->set_var( $this->tpl_name( $block ) . '_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_FULL_SIZE_URL', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_NAME', '' );
            $tpl->set_var( $this->tpl_name( $block ) . '_TITLE', '' );
        }
        $tpl->set_var( $this->tpl_name( $block ) . '_ALT',
            htmlspecialchars( $obj->title() ) );
        
        return $images ? true : false;
    }

    function check()
    {
        $errors = array();
        $index = $this->name();
        if( isset( $_FILES[ $index ][ 'tmp_name' ] ) )
        {
            foreach( $_FILES[ $index ][ 'tmp_name' ] as $k => $v )
            {
                // Если файла в этом месте уже нет, он был успешно
                // обработан при создании объекта.
                if( !$v || !file_exists( $v ) ) continue;
                
                // Для оставшихся файлов надо выдать сообщения об ошибках.
                $errors = array_merge( $errors, 
                    Image::check_image_params( $v,
                        $_FILES[ $index ][ 'name' ][ $k ] ) );

                // Возможно наличие поля для полноразмерной версии файла.
                if( isset( $_FILES[ $index . '_Full' ][ 'tmp_name' ][ $k ] ) )
                {
                    $full = $_FILES[ $index . '_Full' ][ 'tmp_name' ][ $k ];
                    
                    // Если файла в этом месте уже нет, он был успешно
                    // обработан при создании объекта.
                    if( !$full || !file_exists( $full ) ) continue;
                    
                    // Для оставшихся файлов надо выдать сообщения об ошибках.
                    $errors = array_merge( $errors, 
                        Image::check_image_params( $full,
                            $_FILES[ $index . '_Full' ][ 'name' ][ $k ] ) );
                }
            }
        }
        
        return $errors;
    }
        
    function create_file( $name )
    {
        return new Image( $name );
    }
    
    // Возвращает массив изображений, разобранный из строки.
    function get_files( $value )
    {
        if( !$value ) return array();
        
        $data = explode( IMAGES_DELIMITER, $value );

        $images = array();
        for( $i = 0; $i < count( $data ); $i += 2 )
        {
            $images[] = array( 'File' => $data[ $i ], 
                'Title' => $data[ $i + 1 ] );
        }
                
        return $images;
    }
    
    // Собирает строку изображений из массива.
    function set_files( $files_array )
    {
        $data = array();
        foreach( $files_array as $v )
        {
            $data[] = $v[ 'File' ];
            $data[] = $v[ 'Title' ];
        }
        
        return implode( IMAGES_DELIMITER, $data );
    }
    
    // private.
    // Удаляет в объекте файлы картинок.
    function delete_file( $value, $filename )
    {
        $files = $this->get_files( $value );
                
        foreach( $files as $k => $v )
        {
            if( $v[ 'File' ] == $filename )
            {
                $file = $this->create_file( $filename );
                $file->delete();
                unset( $files[ $k ] );
                
                break;
            }
        }
        
        return $this->set_files( $files );
    }
}


class FieldFlag
    extends FieldBase
{
    function FieldFlag( $name, $title = '', $default = false ) 
    {
        parent::FieldBase( $name, $default );
        $edit = new EditFieldFlag( $this, $title );
    }
    
    function value( $raw_data ) { return $raw_data ? true : false; }
    function value_sql( $raw_data ) { return $raw_data ? 1 : 0; }
}


class FieldTimestamp
    extends FieldString
{
    function FieldTimestamp( $name, $title = '', $default = '' ) 
    {
        parent::FieldString( $name, $title, $default );
        $edit = new EditFieldDateSelect( $this, $title );
    }
    
    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
    
        $value = sql_timestamp_to_time( $obj->data( $this ) );
    
        $tpl->set_var( $this->tpl_name( $block ) . '_TIME', 
            date( 'H:i', $value ) );
            
        $tpl->set_var( $this->tpl_name( $block ) . '_DATE', 
            date( 'd.m.Y', $value ) );
            
        $tpl->set_var( $this->tpl_name( $block ) . '_DATEUSA', 
            date( 'M d, Y', $value ) );
            
        global $g_month_names_gen;
        $tpl->set_var( $this->tpl_name( $block ) . '_DATESTR', 
            date( 'd ', $value ) 
            . $g_month_names_gen[ date( 'n', $value ) ]
            . date( ' Y', $value ) );
            
        $tpl->set_var( $this->tpl_name( $block ) . '_RFC',
            date( 'r', $value ) );
        
        return true;
    }
        
    function value_sql( $raw_data ) { return '\'' . $raw_data . '\''; }
}


class FieldObject
    extends FieldInteger
{
    var $m_object_db;
    var $m_objects;

    function FieldObject( $name, $title, &$object_db, $filter = NULL, $nullable = false )
    {
        parent::FieldInteger( $name, 0 );

        $this->m_object_db = $object_db;
        $this->m_objects = $nullable ? array( array( 0, '- ( нет ) -' ) ) : array();
        $objects = array();
        $object_db->load_list( $objects, $filter );
        foreach( $objects as $v )
        {
            $this->m_objects[] = array( $v->id(), $v->title() );
        }
        
        $edit = new EditFieldOption( $this, $title );
    }

    function place( &$obj, &$tpl, $block = '' )
    {
        parent::place( $obj, $tpl, $block );
        
        $value = $obj->data( $this );
        if( !$value ) return false;
        
        $object = $this->m_object_db->create_object( 
            $this->m_object_db->load( $value ) );
        $object->place( $tpl, strtolower( $this->tpl_name( $block ) . '_' ) );
        
        return true;
    }

    function get_options()
    {
        return $this->m_objects;
    }
}


class FieldObjectsAssociativeList
    extends FieldInteger
{
    var $m_object_db;
    var $m_object_filter;
    var $m_objects = NULL;

    function FieldObjectsAssociativeList( $name, $title, &$object_db, $filter = NULL )
    {
        parent::FieldInteger( $name, 0 );

        $this->m_object_db = $object_db;
        $this->m_object_filter = $filter;
        
        $edit = new EditFieldObjectsAssociativeList( $this, $title );
    }

    function place( &$obj, &$tpl, $block = '' )
    {
        $value = $obj->data( $this );
            
        $inner = strtolower( $this->tpl_name( $block ) . '_item_' );
        if( $tpl->has_block( $inner )
            ||
            (    $tpl->has_block( $block ) 
              && $tpl->set_block( $block, $inner,  $inner . '_' ) 
            )                     )
        {
            $tpl->set_var( $inner . '_', '' );
            $count = 0;
            foreach( $value as $v )
            {
                $v_obj = $this->m_object_db->create_object( 
                    $this->m_object_db->load( $v ) );
                $v_obj->place( $tpl, $this->tpl_name( $block ) . '_' );
                $tpl->parse( $inner . '_', $inner, true );
            }
        }
    
        // Если нужен только один объект.
        if( $value )
        {
            $v_obj = $this->m_object_db->create_object( 
                $this->m_object_db->load( $value[ 0 ] ) );
            $v_obj->place( $tpl, $this->tpl_name( $block ) );
        }

        return $value ? true : false;
    }

    function get_options()
    {
        if( is_null( $this->m_objects ) )
        {
            $objects = array();
            $this->m_object_db->load_list( $objects, $this->m_object_filter );
            foreach( $objects as $v )
            {
                $this->m_objects[] = array( $v->id(), $v->title( true ) );
            }
        }
        
        return $this->m_objects;
    }
}


?>
