<?php


include_once( PATH_TO_ROOT . 'inc/image.class.php' );


define( 'DEFAULT_BIN_PATH', PATH_TO_ROOT . PATH_TO_BIN );


class BinaryFile
{
    var $m_file = '';
    var $m_errors = array();
    var $m_path = DEFAULT_BIN_PATH;

    // constructors
    
    // Создает объект изображения по имени файла в каталоге с изображениями.
    function BinaryFile( $file = '', $subdir = '' )
    {
        if( $subdir ) $this->m_path .= $subdir . '/';
        if( !file_exists( $this->m_path ) ) mkdir( $this->m_path, 0755 );
        
        if( $file && !file_exists( $this->m_path . $file ) )
        {
            $this->m_errors[] = 'Файл "' . $file
                . '" отсутствует в каталоге файлов!';
            return;
        }
        $this->m_file = $file;
    }
    
    // Создает изображение по загруженному пользователем файлу.
    // При необходимости изменяется размер изображения.
    // При необходимости остается исходный вариант изображения.
    function get_from_file( $temp, $orig )
    {
        // Переписать имена файлов латиницей.
        global $transliterate_from_chars, $transliterate_to_chars;
        $new_filename = str_replace( $transliterate_from_chars, 
            $transliterate_to_chars, $orig );
            
        // Сформировать при необходимости новое имя файла.
        $new_filename = $this->file_rename( $new_filename, $this->m_path );
    
        if( !move_uploaded_file( $temp, $this->m_path . $new_filename ) )
        {
            $this->m_errors[] = 'Ошибка при копировании файла "' 
                . $orig . '"!';
            return;
        }

        // Выставить права на доступ к файлу.
        chmod( $this->m_path . $new_filename, 0666 );

        $this->m_file = $new_filename;
        
        return false;
    }
    
    // functions

    // Возвращает название файла в каталоге изображений.
    function filename() { return $this->m_file; }
    
    // Возвращает полный путь к файлу с изображением.
    function path_to_file()
    {
        if( !$this->filename() ) return '';
        
        return $this->m_path . rawurlencode( $this->filename() );
    }
    
    // Возвращает имя файла с путем в файловой системе сервера.
    function realpath_to_file()
    {
        if( !$this->filename() ) return '';
        
        return realpath( $this->m_path . $this->filename() );
    }
    
    // Удаляет файлы изображений из каталога изображений.
    function delete()
    {
        $i = $this->path_to_file();
        if( file_exists( $i ) ) unlink( $i );
    }
            
    // Возвращает массив строк, содержащих текстовые описания ошибок
    // при работе с изображением. Пустой массив означает их отсутствие.
    function errors() { return $this->m_errors; }
    
    
    // class functions
    
    // Проверяет правильность закачки файла.
    // Возвращает массив с текстовыми строками ошибок.
    static
    function check_params( $index, $max_width = 1024, $max_height = 1024 )
    {
        $errors = array();
        
        switch( $_FILES[ $index ][ 'error' ] )
        {
            case UPLOAD_ERR_OK:
            case UPLOAD_ERR_NO_FILE:
            {
                break;
            }
        
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
            {
                $errors[] = 'Размер файла превышает максимально допустимый!';
                break;
            }
            
            case UPLOAD_ERR_PARTIAL:
            {
                $errors[] = 'Файл загрузился не до конца! Попробуйте ещё раз.';
                break;
            }
            
            default:
            {
                $errors[] = 'Ошибка при загрузке файла!';
                break;
            }
        }
                        
        if( !is_uploaded_file( $_FILES[ $index ][ 'tmp_name' ] ) )
        {
            $errors[] = 'Файл "' . $_FILES[ $index ][ 'name' ] 
                . '" не закачался на сервер!';
            return $errors;
        }
        
        return $errors;
    }

    
    // Проверяет, существует ли в каталоге $dir файл с указанным именем,
    // и при необходимости изменяет имя файла до уникального.
    static
    function file_rename( $filename, $dir )
    {
        if( file_exists( $dir . $filename ) )
        {
            $char = array( 
                'f', 'a', 't', 'h', 'o', 'm', '_', 'w', 
                'e', 'r', 's', 'd', 'q', 'g', 'z', 'x', 
                '1', '2', '3', '4', '5', '6', '7', '8', '9', '0' 
            );
            srand( ( double )microtime() * 1000000 );
            $unic = $char[ rand( 0, 15 ) ];
            $filename = $unic . $filename;
            return Image::file_rename( $filename, $dir );
        }
        else
        {
            return $filename;
        }
    }
}


?>
