<?php


ini_set( 'display_errors', 'On' );


include_once( PATH_TO_ROOT . 'inc/func.php' );


define( 'FULL_SIZE_IMAGE_SUBDIR', 'full/' );


$transliterate_from_chars = array( 'а','б','в','г','д','е','ё' ,'ж' ,'з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч' ,'ш' ,'щ'   ,'ъ' ,'ы','ь' ,'э','ю','я' ,'А','Б','В','Г','Д','Е','Ё' ,'Ж' ,'З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч' ,'Ш' ,'Щ'   ,'Ъ' ,'Ы','Ь' ,'Э','Ю','Я' ,' ', '&'   );
$transliterate_to_chars   = array( 'a','b','v','g','d','e','yo','zh','z','i','y','k','l','m','n','o','p','r','s','t','u','f','h','c','ch','sh','shch','\'','y','\'','e','u','ya','A','B','V','G','D','E','YO','ZH','Z','I','Y','K','L','M','N','O','P','R','S','T','U','F','H','C','CH','SH','SHCH','\'','Y','\'','E','U','YA','_', 'and' );


class Image
{ 
    var $m_file = '';
    var $m_errors = array();
//    var $m_path = PATH_TO_ROOT . PATH_TO_PIC;
    var $m_path = PATH_TO_PIC;

    // constructors
     
    // Создает объект изображения по имени файла в каталоге с изображениями.
    function Image( $file = '', $subdir = '' )
    {
        if( $subdir ) $this->m_path .= $subdir . '/';
        if( !file_exists( PATH_TO_ROOT . $this->m_path ) )
        {
            mkdir( PATH_TO_ROOT . $this->m_path, 0755 );
        }
        if( !file_exists( PATH_TO_ROOT . $this->m_path . FULL_SIZE_IMAGE_SUBDIR ) )
        {
            mkdir( PATH_TO_ROOT . $this->m_path . FULL_SIZE_IMAGE_SUBDIR, 0755 );
        }
    
        if( $file && !file_exists( PATH_TO_ROOT . $this->m_path . $file ) )
        {
            $this->m_errors[] = 'Изображение "' . $file
                . '" отсутствует в каталоге изображений!';
            return;
        }
        $this->m_file = $file;
    }
    
    // Создает изображение по загруженному пользователем файлу.
    // При необходимости изменяется размер изображения.
    // При необходимости остается исходный вариант изображения.
    function get_from_file( $temp_files, $orig_files, $limits = array(),
        $full_size_limits = array() )
    {
        if( is_array( $temp_files ) )
        {
            $temp = $temp_files[ 0 ];
            $temp_full = $temp_files[ 1 ];
        }
        else
        {
            $temp = $temp_files;
            $temp_full = $temp_files;
        }
    
        if( is_array( $orig_files ) )
        {
            $orig = $orig_files[ 0 ];
            $orig_full = $orig_files[ 1 ];
        }
        else
        {
            $orig = $orig_files;
            $orig_full = $orig_files;
        }
    
        $this->m_errors = Image::check_image_params( $temp, $orig );
        if( $this->m_errors ) return;

        if( $temp != $temp_full )
        {
            $this->m_errors = Image::check_image_params( $temp_full, $orig_full );
            if( $this->m_errors ) return;
        }
                
        // Переписать имена файлов латиницей.
        global $transliterate_from_chars, $transliterate_to_chars;
        $new_filename = str_replace( $transliterate_from_chars, 
            $transliterate_to_chars, $orig );

        // Для файлов формата BMP новое имя файла будет с расширением JPG.
        $size = getimagesize( $temp );
        if( $size[ 2 ] == 6 )
        {
            // Меняем расширение в имени файла на .jpg.
            $new_filename = preg_replace( '/(.*)\.\w+/', '$1.jpg', $new_filename );
        }
            
        // Сформировать при необходимости новое имя файла.
        $new_filename = $this->file_rename( $new_filename, PATH_TO_ROOT . $this->m_path );
        
        // Превью-версия.
        $this->copy_and_resize( $temp, $orig, 
            PATH_TO_ROOT . $this->m_path, $new_filename, $limits );
        if( $this->m_errors ) return;
        // Полноразмерная версия.
        $this->copy_and_resize( $temp_full, $orig_full, 
            PATH_TO_ROOT . $this->m_path . FULL_SIZE_IMAGE_SUBDIR, $new_filename, $full_size_limits );
        if( $this->m_errors ) return;
        
        $this->m_file = $new_filename;
        
        return false;
    }

    function copy_and_resize( $temp, $orig, $path, $new_filename, $limits )
    {
        // Файлы формата BMP сразу переконвертировать в JPEG.
        $size = getimagesize( $temp );
        if( $size[ 2 ] == 6 )
        {
            $err = $this->copy_bmp_to_jpeg( $temp, $path, $new_filename );
            if( $err )
            {
                $this->m_errors[] = $err;
                return;
            }
        }
        // Остальные два формата просто переместить в указанный каталог.
        else
        {
            if(    !is_uploaded_file( $temp )
                || !copy( $temp, $path . $new_filename ) )
            {
                $this->m_errors[] = 'Ошибка при копировании файла "' 
                    . $orig . '"!';
                return;
            }
        }
        
        // При необходимости изменить размеры.
        $ratio = $this->calc_ratio( $size, $limits );
        if( $ratio != 1 ) $this->resize( $path, $new_filename, $ratio );

        // Выставить права на доступ к файлу.
        chmod( $path . $new_filename, 0666 );
    }
            
    // functions

    // Возвращает название файла в каталоге изображений.
    function filename() { return $this->m_file; }
    
    // Возвращает полный путь к файлу с изображением.
    function path_to_image()
    {
        if( !$this->filename() ) return '';
        
        return SITE_ROOT_PATH . $this->m_path . $this->filename();
    }
    
    // Возвращает имя файла с путем в файловой системе сервера.
    function realpath_to_image()
    {
        if( !$this->filename() ) return '';
        
        return realpath( PATH_TO_ROOT . $this->m_path . $this->filename() );
    }
    
    // Возвращает полный путь к файлу с исходной (полноразмерной)
    // версией изображения.
    function path_to_full_size_image()
    {
        if( !$this->filename() ) return '';
        
        if( file_exists( PATH_TO_ROOT . $this->m_path 
            . FULL_SIZE_IMAGE_SUBDIR . $this->filename() ) ) 
        {
            return SITE_ROOT_PATH . $this->m_path . FULL_SIZE_IMAGE_SUBDIR 
                . $this->filename();
        }
        
        return $this->path_to_image();
    }
    
    // Возвращает имя файла с исходной (полноразмерной)
    // версией изображения с путем в файловой системе сервера.
    function realpath_to_full_size_image()
    {
        if( !$this->filename() ) return '';
        
        if( file_exists( PATH_TO_ROOT . $this->m_path 
            . FULL_SIZE_IMAGE_SUBDIR . $this->filename() ) ) 
        {
            return PATH_TO_ROOT . $this->m_path . FULL_SIZE_IMAGE_SUBDIR 
                . $this->filename();
        }
        
        return '';
    }
    
    // Удаляет файлы изображений из каталога изображений.
    function delete()
    {
        // При отсутствии изображения удалять нечего.
        print_r( $this->filename() );
        if( !$this->filename() ) return;
        
        $i = $this->realpath_to_image();
        if( file_exists( $i ) ) unlink( $i );
        $i = $this->realpath_to_full_size_image();
        if( file_exists( $i ) ) unlink( $i );
    }
            
    // Возвращает массив строк, содержащих текстовые описания ошибок
    // при работе с изображением. Пустой массив означает их отсутствие.
    function errors() { return $this->m_errors; }
    
    
    // class functions
    
    // Проверяет соответствие формата и размеров указанного файла требуемым.
    // Возвращает массив с текстовыми строками ошибок.
    static
    function check_image_params( $temp, $orig, $max_width = 1024, $max_height = 1024 )
    {
        $errors = array();
                
        if( !is_uploaded_file( $temp ) )
        {
            $errors[] = 'Файл "' . $orig 
                . '" не закачался на сервер!';
            return $errors;
        }
        
        $size = getimagesize( $temp );
        if( !$size )
        {
            $errors[] = 'Ошибка при определении размеров '
                . 'изображения в файле "' . $orig . '"! '
                . 'Возможно, файл не является изображением или закачался с ошибкой.';
            return $errors;
        }

        // Поддерживаются форматы JPG, GIF, BMP.
        if( !in_array( $size[ 2 ], array( 1, 2, 6 ) ) )
        {
            $errors[] = 'Формат файла "' . $orig 
                . '" не поддерживается!';
        }
                
        if( ( $size[ 0 ] > $max_width ) || ( $size[ 1 ] > $max_height ) ) 
        {
            $errors[] = 'Размер файла "' . $orig 
                . '" превышает максимальный размер ' . $max_width . ' x ' 
                . $max_height . ' точек!';
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

    // Функция вычисляет коэффицаент пропорционального масштабирования
    // изображения на основе его реальных размеров и указанных
    // предельно допустимых размеров.
    static
    function calc_ratio( $size, $limits )
    {
        // Получить ограничения на размеры.
        $limit_width = isset( $limits[ 0 ] ) ? $limits[ 0 ] : 0;
        $limit_height = isset( $limits[ 1 ] ) ? $limits[ 1 ] : 0;
        
        // Вычислить коэффициент преобразования в зависимости от указанных
        // требований к размерам.
        $ratio = ( $limit_width && $limit_height ) 
            ? min( $size[ 0 ] / $limit_width, $size[ 1 ] / $limit_height )
            : ( $limit_width  ? $size[ 0 ] / $limit_width
            : ( $limit_height ? $size[ 1 ] / $limit_height 
            : 1 ) );
            
        return $ratio;
    }

    // Изменяет размеры указанного файла.
    static
    function resize( $dir, $file, $ratio )
    {
        $file_path = $dir . $file;
        $data = getimagesize( $file_path );
        $w = $data[ 0 ] / $ratio;
        $h = $data[ 1 ] / $ratio;

        $resampled = imagecreatetruecolor( $w, $h );
            
        switch( $data[ 2 ] )
        {
            // GIF
            case 1: { $original = imagecreatefromgif( $file_path ); break; }
            
            // JPG
            case 2: { $original = imagecreatefromjpeg( $file_path ); break; }
            
            // BMP
            case 6: { $original = imagecreatefrombmp( $file_path ); break; }
            
            // Вообще непонятно, что мы делаем в этой функции.
            // Возвращаем неизмененное изображение.
            default: { return $file; }
        }

        // Если чтение изображения завершилось с ошибкой, вернуться.    
        if( !$original )
        {
            $bgc = imagecolorallocate( $resampled, 255, 255, 255 );
            $tc  = imagecolorallocate( $resampled, 0, 0, 0 );
            imagefilledrectangle( $resampled, 0, 0, $w, $h, $bgc );
            imagestring( $resampled, 1, 5, 5, 'Error loading ' . $file . '!', $tc );
        }
        else
        {
            // Преобразовать размеры изображения.    
            imagecopyresampled( $resampled, $original, 0, 0, 0, 0, $w, $h, 
                $data[ 0 ], $data[ 1 ] );
        }

        // Вывести результат преобразования в исходный файл.    
        switch( $data[ 2 ] )
        {
            // GIF
            case 1: { imagegif( $resampled, $file_path ); break; }
            
            // JPG
            case 2: { imagejpeg( $resampled, $file_path, 80 ); break; }
            
            // BMP
            // Изменить расширение файла на jpg.
            case 6:
            {
                $file = preg_replace( '/(.*)\.\w+/', '$1.jpg', $file );
                
                imagejpeg( $resampled, $dir . $file, 80 );
                break;
            }
        }
        
        return $file;
    }   


    // Переписывает содержимое изображения $bmp_file в формате BMP
    // в каталог $dir в формате JPEG.
    static
    function copy_bmp_to_jpeg( $bmp_file, $dir, &$file )
    {
        // Читаем изображение в формате BMP.
        $image = imagecreatefrombmp( $bmp_file );
        if( !$image ) return 'Ошибка при чтении BMP файла "' . $bmp_file . '"!';

        // !!!
        // Расширение файла меняется где-то снаружи.        
//        // Меняем расширение в имени файла на .jpg.
//        $file = preg_replace( '/(.*)\.\w+/', '$1.jpg', basename( $file ) );
//        
//        // На всякий случай проверяем наличие в указанном каталоге файлов
//        // с таким именем.
//        $file = $this->file_rename( $file, $dir );
        
        // Записываем результат в формате JPEG.
        if( !imagejpeg( $image, $dir . $file, 80 ) )
        {
            return 'Ошибка при записи JPEG файла "' . $dir . $file . '"!';
        }
        
        return '';
    }
}


// Чтение изображения из Windows BMP.
function imagecreatefrombmp( $filename )
{
    if( !( $f1 = fopen( $filename, "rb" ) ) ) return false;

    $file = unpack( 'vfile_type/Vfile_size/Vreserved/Vbitmap_offset', 
        fread( $f1, 14 ) );
    if( $file[ 'file_type' ] != 19778 ) return false;

    $bmp = unpack(
        'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'
        . '/Vcompression/Vsize_bitmap/Vhoriz_resolution'
        . '/Vvert_resolution/Vcolors_used/Vcolors_important', 
        fread( $f1, 40 ) );
        
    $bmp[ 'colors' ] = pow( 2, $bmp[ 'bits_per_pixel' ] );
    if( $bmp[ 'size_bitmap' ] == 0 )
    {
        $bmp[ 'size_bitmap' ] = $file[ 'file_size' ] - $file[ 'bitmap_offset' ];
    }
    $bmp[ 'bytes_per_pixel' ] = $bmp[ 'bits_per_pixel' ] / 8;
    $bmp[ 'bytes_per_pixel2' ] = ceil( $bmp[ 'bytes_per_pixel' ] );
    $bmp[ 'decal' ] = ( $bmp[ 'width' ] * $bmp[ 'bytes_per_pixel' ] / 4 );
    $bmp[ 'decal' ] -= floor( $bmp[ 'width' ] * $bmp[ 'bytes_per_pixel' ] / 4 );
    $bmp[ 'decal' ] = 4 - ( 4 * $bmp[ 'decal' ] );
    if( $bmp[ 'decal' ] == 4 ) $bmp[ 'decal' ] = 0;

    $palette = array();
    if( $bmp[ 'colors' ] < 16777216 )
    {
        $palette = unpack( 'V' . $bmp[ 'colors' ], 
            fread( $f1, $bmp[ 'colors' ] * 4 ) );
    }

    $img = fread( $f1, $bmp[ 'size_bitmap' ] );
    $vide = chr( 0 );
    $res = imagecreatetruecolor( $bmp[ 'width' ], $bmp[ 'height' ] );
    $p = 0;
    $y = $bmp[ 'height' ] - 1;
    while( $y >= 0 )
    {
        $x=0;
        while( $x < $bmp[ 'width' ] )
        {
            if( $bmp[ 'bits_per_pixel' ] == 24 )
            {
                $color = unpack( 'V', substr( $img, $p, 3) . $vide );
            }
            elseif( $bmp[ 'bits_per_pixel' ] == 16 )
            {  
                $color = unpack( 'n', substr( $img, $p, 2 ) );
                $color[ 1 ] = $palette[ $color[ 1 ] + 1 ];
            }
            elseif( $bmp[ 'bits_per_pixel' ] == 8 )
            {  
                $color = unpack( 'n', $vide . substr( $img, $p, 1 ) );
                $color[ 1 ] = $palette[ $color[ 1 ] + 1 ];
            }
            elseif( $bmp[ 'bits_per_pixel' ] == 4 )
            {
                $color = unpack( 'n', $vide . substr( $img, floor( $p ), 1 ) );
                $color[ 1 ] = ( ( $p * 2 ) % 2 == 0 )
                    ? ( $color[ 1 ] >> 4 ) 
                    : ( $color[ 1 ] & 0x0F );
               $color[ 1 ] = $palette[ $color[ 1 ] + 1 ];
            }
            elseif( $bmp[ 'bits_per_pixel' ] == 1 )
            {
               $color = unpack( 'n', $vide . substr( $img, floor( $p ), 1 ) );
               
               $pp = ( $p * 8 ) % 8;
               if    ( $pp == 0 ) $color[ 1 ] =   $color[ 1 ]          >> 7;
               elseif( $pp == 1 ) $color[ 1 ] = ( $color[ 1 ] & 0x40 ) >> 6;
               elseif( $pp == 2 ) $color[ 1 ] = ( $color[ 1 ] & 0x20 ) >> 5;
               elseif( $pp == 3 ) $color[ 1 ] = ( $color[ 1 ] & 0x10 ) >> 4;
               elseif( $pp == 4 ) $color[ 1 ] = ( $color[ 1 ] & 0x08 ) >> 3;
               elseif( $pp == 5 ) $color[ 1 ] = ( $color[ 1 ] & 0x04 ) >> 2;
               elseif( $pp == 6 ) $color[ 1 ] = ( $color[ 1 ] & 0x02 ) >> 1;
               elseif( $pp == 7 ) $color[ 1 ] = ( $color[ 1 ] & 0x01 );  
               
               $color[ 1 ] = $palette[ $color[ 1 ] + 1 ];
            }
            else return false;
            
            imagesetpixel( $res, $x, $y, $color[ 1 ] );
            
            $x++;
            $p += $bmp[ 'bytes_per_pixel' ];
        }
        
        --$y;
        $p += $bmp[ 'decal' ];
    }

    fclose( $f1 );
    
    return $res;
}
    

?>
