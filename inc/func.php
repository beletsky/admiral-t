<?
################################################################################
#                     
#   inc/func.inc.php  
#   Общие функции.    
#                     
################################################################################


function get_param_by_number($number, $default)
{
    global $p;
    if( !isset( $p[$number] ) ) return $default;
    return $p[$number];
}


// Вовращает следующий параметр из списка p[]=...,
// либо указанное значение, если такового не обнаружено.
function get_next_param( $default = NULL )
{
    global $p;
    $param = array_shift( $p );
    if( !isset( $param ) ) return $default;
    return $param;
}

// Устанавливает необходимое значение для переменной формы.
function set_form_var( &$var, $default )
{
    if( isset( $var ) )
    {
        $var = is_string( $var ) 
            ? htmlspecialchars( stripslashes( $var ) ) 
            : $var;
    }
    else $var = $default;
}

// Возвращает параметры GET HTTP запроса, которые должны сохраняться.
function kept_url_params()
{
    $url_params = array( 'id', 'email', 'check_code' );
    
    $params = array();
    foreach( $url_params as $v )
    {
        if( isset( $_GET[ $v ] ) ) $params[] = $v . '=' . $_GET[ $v ];
    }
    return ( $params ? '?' . implode( '&', $params ) : '' );
}

// Возвращает указанный редирект.
function redirect( $code, $url )
{
    if( $code == 301 ) header( 'HTTP/1.1 301 Moved Permanently' );
    else if( $code == 303 ) header( 'HTTP/1.1 303 See Other' ); 
    
    // TODO 
    // Надо выяснить, для чего перед $url в первой версии появился слеш,
    // указывающий на корень сайта. Его наличие сильно мешает сайту работать
    // в подкаталоге.
    header( 'Location: ' . $url . HTML_EXTENSION );
    exit();
}

// Возвращает ссылку на каптчу с опциональным суффиксом
// (для нескольких каптч на странице).
function get_captcha_url( $prefix )
{
    return 'kcaptcha/index.php?' . session_name() . '=' . session_id()
        . '&prefix=' . $prefix;
}

// Возвращает строку с номером версии СУБД.
function db_version_string()
{
    global $db;
    $db->Query( 'select version();' );
    return $db->NextRecord() ? $db->F( 0 ) : '';
}

// Убирает из адреса сайта префикс www, если он есть.
function strip_www_prefix( $url )
{
    return preg_replace( '/^(http\:\/\/)(www\.)?(.*)$/i', '$1$3', $url );
}

// Возвращает имя текущего хоста без префикса www.
function host_name()
{
    if( isset( $_SERVER[ 'HTTP_HOST' ] ) )
    {
        return preg_replace( '/^(www\.)?(.*)$/i', '$2', $_SERVER[ 'HTTP_HOST' ] );
    }
    
    global $g_sites;
    return $g_sites[ HOST_NAME ][ 'Host' ];
}

// Возвращает значение переменной в виде, пригодном для использования в запросе к БД.
function wrap_sql_type( $data )
{
    if( is_array( $data ) ) return (string)$data[ 0 ];
    if( is_numeric( $data ) ) return (string)$data;
    if( is_string( $data ) ) return "'" . /* mysql_escape_string */( $data ) . "'";
    
    return (string)$data;
}

// Преобразует запрос sql в набор значений через зяпятую, для эмуляции подзапросов.
function sql_subselect( $s )
{
    if( strpos( $s, 'select' ) === false ) return $s;
    
    global $db;
    $db->Query( $s );    
    $vals = array();
    while( $obj = $db->FetchArray() )
    {
        $vals[] = $obj[ 0 ];
    }

    if( $vals ) return implode( ',', $vals );
    return 'NULL';
}

// Возвращает значение переменной $name, переданное в HTTP запросе.
function get_http_var( $name, $default = '' )
{
    if( isset( $_GET [ $name ] ) ) return $_GET [ $name ];
    if( isset( $_POST[ $name ] ) ) return $_POST[ $name ];
    if( isset( $_SESSION[ $name ] ) ) return $_SESSION[ $name ];
    return $default;
}

// Возвращает true, если SQL timestamp является нулевым.
function sql_timestamp_is_null( $sql_stamp )
{
    if( !$sql_stamp ) return true;
    $stamp = array();
    preg_match( '/(\d{4})-?(\d{2})-?(\d{2})\s?(\d{2}):?(\d{2}):?(\d{2})/', 
        $sql_stamp, $stamp );
    return ( !(int)$stamp[ 4 ] && !(int)$stamp[ 5 ] 
        && !(int)$stamp[ 6 ] && !(int)$stamp[ 2 ] 
        && !(int)$stamp[ 3 ] && !(int)$stamp[ 1 ] );
}

// Преобразует SQL timestamp в значение unix timestamp.
function sql_timestamp_to_time( $sql_stamp )
{
    if( !$sql_stamp ) return mktime( 0 );
    $stamp = array();
    preg_match( '/(\d{4})-?(\d{2})-?(\d{2})\s?(\d{2}):?(\d{2}):?(\d{2})/', 
        $sql_stamp, $stamp );
    return mktime( $stamp[ 4 ], $stamp[ 5 ], $stamp[ 6 ], 
        $stamp[ 2 ], $stamp[ 3 ], $stamp[ 1 ] );
}

// Преобразует unix timestamp в SQL timestamp.
function time_to_sql_timestamp( $time )
{
    if( db_version_string() >= '4.1' ) return date( 'Y-m-d H:i:s', $time );
    
    return date( 'YmdHis', $time );
}

// Форматирует денежную величину.
function format_currency( $v ) { return sprintf( '%01.0f', $v ); }

// Форматирует URL сайта.
function format_http( $v )
{
    if( strpos( strtolower( $v ), 'http://' ) !== 0 ) return ( 'http://' . $v );
    
    return $v;
}

// Делает заглавной первую букву строки.
function upper_first_letter( $s )
{
    return strtoupper( $s[ 0 ] ) . substr( $s, 1 );
}


// Транслитерирует строку по ГОСТ 7.79-2000.
function transliterate( $s )
{
    static $transliterate_from_chars = array( 'а','б','в','г','д','е','ё' ,'ж' ,'з','и','й','к','л','м','н','о','п','р','с','т','у','ф','х','ц','ч' ,'ш' ,'щ'  ,'ъ' ,'ы' ,'ь','э' ,'ю' ,'я' ,'А','Б','В','Г','Д','Е','Ё' ,'Ж' ,'З','И','Й','К','Л','М','Н','О','П','Р','С','Т','У','Ф','Х','Ц','Ч' ,'Ш' ,'Щ'  ,'Ъ' ,'Ы' ,'Ь','Э' ,'Ю' ,'Я' ,' ', '&'   );
    static $transliterate_to_chars   = array( 'a','b','v','g','d','e','yo','zh','z','i','j','k','l','m','n','o','p','r','s','t','u','f','x','c','ch','sh','shh','``','y`','`','e`','yu','ya','A','B','V','G','D','E','YO','ZH','Z','I','J','K','L','M','N','O','P','R','S','T','U','F','X','C','CH','SH','SHH','``','Y`','`','E`','YU','YA','_', 'and' );
    return str_replace( $transliterate_from_chars, $transliterate_to_chars, $s );
}

/*
*   User input check functions.
*/

function check_email( $email )
{
    $email_regexp = '/^[a-zA-Z0-9][\w0-9\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w0-9\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/';
    return ( preg_match( $email_regexp, $email ) == 1 );
}

function check_phone( $phone, $digits = 11 )
{
    // Check present of disallowed chars.
    $disallowed_chars_regexp = '/[^\d\s\(\)\-]/isU';
    if( preg_match( $disallowed_chars_regexp, $phone ) > 0 ) return false;
    
    // Check count of digits.
    $matches = array();
    if( preg_match_all( '/\d/', $phone, $matches ) != $digits ) return false;
    
    return true;
}

function check_mate( $str )
{
    include_once( './lib/utf8/Func.php' );
    Func::add_include_path( './lib/utf8/' );

    $fragment = Func::call( 'censure', 
        iconv( 'cp1251', 'utf-8//IGNORE//TRANSLIT', $str ) );
    if( $fragment !== false && !is_numeric( $fragment ) )
    {
        return iconv( 'utf-8', 'cp1251//IGNORE//TRANSLIT', $fragment );
    }
    
    return false;
}

function check_password( $str )
{
    $regex = '/(\!|\@|\#|\$|\%|\^|\&|\*|\(|\)|\_|\+|\~|\-|\=|\\|\/|\?|\.|\,|\<|\>|\||A|B|C|D|E|F|G|H|I|J|K|L|M|N|O|P|Q|R|S|T|U|V|W|X|Y|Z|a|b|c|d|e|f|g|h|i|j|k|l|m|n|o|p|q|r|s|t|u|v|w|x|y|z|0|1|2|3|4|5|6|7|8|9)+/';
    if( preg_match( $regex, $str ) ) return true;
             
    return false;
}


/*
*   Menus.
*/

function get_menu_pages( $menu_code, $level = 1, $parent = NULL, 
    $include_childs = false, $id_in_select = '', $host = HOST_CODE )
{
    global $db;
    
    $ret = array();
    $addon = '';
    if( $level ) $addon = ' and DC.level ' . ( $include_childs ? '>=' : '=' ) . $level;
    
    if( isset( $parent ) && is_numeric( $parent ) ) $addon .= ' and DC.ID_Parent = ' . $parent;
    else if( isset( $parent ) && is_string( $parent ) ) $addon .= ' and DSP_Parent.PageCode = "' . $parent . '"';
    
    if( is_array( $menu_code ) )
    {
        $codes = array();
        foreach( $menu_code as $code ) $codes[] = wrap_sql_type( $code );
        $addon .= ' and DSP.Menu in ( ' . implode( ', ', $codes ) . ' )';
    }
    else if( is_string( $menu_code ) ) $addon .= ' and DSP.Menu = "' . $menu_code . '"';
    if( $id_in_select ) $addon .= ' and DSP.ID in (' . $id_in_select . ')';
    
    $q = 'select DC.*, DSP.ID, DSP.PageCode from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' left join dwCatPages DCP_Parent on DC.ID_Parent = DCP_Parent.IDCat'
        . ' left join dwStaticPage DSP_Parent on DCP_Parent.IDPage = DSP_Parent.ID'
        . ' where DC.level > 0'
        . ( isset( $host ) ? ' and DSP.Host = \'' . $host . '\'' : '' )
        . $addon .' order by DC.leftt';
                  
    $db->Query( $q );
    while( $arr = $db->FetchArray() ) $ret[] = $arr;
    return $ret;
}

// Возвращает ID_Cat под коду страницы.
function get_cat_id_by_pagecode( $page_code )
{
    global $db;
    
    $q = 'select DC.ID_Cat from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' where DC.level > 0 and DSP.Host = \'' . HOST_CODE . '\''
        . ' and DSP.PageCode="' . $page_code . '"'
        . ' order by DC.leftt';
                  
    $db->Query( $q );
    $arr = $db->FetchArray();
        
    return $arr[ 'ID_Cat' ];
}

// Возвращает код страницы по ID_Cat.
function get_pagecode_by_cat_id( $cat_id )
{
    global $db;
    
    $q = 'select DSP.PageCode from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' where DC.level > 0 and DSP.Host = \'' . HOST_CODE . '\''
        . ' and DC.ID_Cat="' . $cat_id . '"'
        . ' order by DC.leftt';
                  
    $db->Query( $q );
    $arr = $db->FetchArray();
        
    return $arr[ 'PageCode' ];
}

// Возвращает массив кодов страниц, соответствующих пути через меню
// к указанной странице.
function get_menu_path( &$path, $page_code )
{
    global $db;
    
    $q = 'select DC.*, DSP.ID, DSP.PageCode from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' where DC.level > 0 and DSP.Host = \'' . HOST_CODE . '\''
        . ' and DSP.PageCode="' . $page_code . '"'
        . ' order by DC.leftt';
                  
    $db->Query( $q );
    $arr = $db->FetchArray();
    if( !isset( $arr[ 'ID_Cat' ] ) )
    {
        // Если текущая страница не находится в каталоге.
//        print_r( $page_code );
        return false;
    }
        
    get_menu_path_by_cat_id( $path, $arr[ 'ID_Cat' ] );
    return true;
}

function get_menu_path_by_cat_id( &$path, $cat_id )
{
    global $db;
    
    $q = 'select DC.*, DSP.ID, DSP.PageCode from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' where DC.level > 0 and DSP.Host = \'' . HOST_CODE . '\''
        . ' and DC.ID_Cat=' . $cat_id
        . ' order by DC.leftt';
                          
    $db->Query( $q );
    $arr = $db->FetchArray();
    
    if( !is_null( $arr[ 'ID_Parent' ] ) )
    {
        get_menu_path_by_cat_id( $path, $arr[ 'ID_Parent' ] );
        $path[] = $arr;
    }
}

// Возвращает элемента каталога, соответствующего данной странице,
// либо пустую строку, если данной странице не соответствует ни один
// элемент каталога.
function get_catalog_item( $cat_id )
{
    global $db;
    
    $q = 'select DC.*, DSP.ID, DSP.PageCode from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' where DC.ID_Cat=' . $cat_id;
                  
    $db->Query( $q );
    return $db->FetchArray();
}

// Возвращает элемента каталога, соответствующего данной странице,
// либо пустую строку, если данной странице не соответствует ни один
// элемент каталога.
function get_catalog_item_for_page( $static_page_id )
{
    global $db;
    
    $q = 'select DC.*, DSP.ID, DSP.PageCode from dwCategories DC'
        . ' left join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' left join dwStaticPage DSP on DCP.IDPage = DSP.ID'
        . ' where DCP.IDPage=' . $static_page_id;
                  
    $db->Query( $q );
    return $db->FetchArray();
}

// Возвращает название элемента каталога, соответствующего данной странице,
// либо пустую строку, если данной странице не соответствует ни один
// элемент каталога.
function get_catalog_name( $static_page_id, $host = HOST_CODE )
{
    global $db;
    
    $q = 'select DC.CatName from dwCategories DC'
        . ' inner join dwCatPages DCP on DC.ID_Cat = DCP.IDCat'
        . ' where DCP.IDPage=' . $static_page_id;
                  
    $db->Query( $q );
    if( $db->NextRecord() ) return $db->F( 0 );
    
    return '';
}

// Возвращает название элемента каталога, соответствующего данной странице,
// либо пустую строку, если данной странице не соответствует ни один
// элемент каталога.
function get_catalog_name_by_cat_id( $cat_id )
{
    global $db;
    
    $q = 'select DC.CatName from dwCategories DC'
        . ' where DC.ID_Cat=' . $cat_id;
                  
    $db->Query( $q );
    if( $db->NextRecord() ) return $db->F( 0 );
    
    return '';
}

// Возвращает true, если $cat_id ID входа в каталог находится
// внутри входа $parent_cat_id или является им.
function cat_id_in_or_equal( $cat_id, $parent_cat_id )
{
    global $db;

    if( !$cat_id ) return false;
    if( !$parent_cat_id ) return false;
        
    $q = 'select * from dwCategories' . ' where ID_Cat = ' . $parent_cat_id;
    $db->Query( $q );
    if( !$db->NextRecord() ) return false;
    
    $leftt = $db->F( 'leftt' );
    $rightt = $db->F( 'rightt' );
        
    $q = 'select count( * ) from dwCategories' 
        . ' where ID_Cat = ' . $cat_id
        . ' and leftt >= ' . $leftt . ' and rightt <= ' . $rightt;
    $db->Query( $q );
    if( !$db->NextRecord() ) return false;
    
    return ( $db->F( 0 ) != 0 );
}

// Преобразует списочное представление каталога в дерево.
function parse_catalog_tree( &$list )
{
    if( !$list ) return array();
    $pos = 0;
    return parse_catalog_tree_internal( $list, $pos, $list[ $pos ][ 'level' ] );
}

function parse_catalog_tree_internal( &$list, &$pos, $level )
{
    $tree = array();

    while( $pos < count( $list ) )
    {
        $cur_level = $list[ $pos ][ 'level' ];
//        print_r( $level . ' ' . $cur_level . ' ' . $list[ $pos ][ 'CatName' ] . "\n" );
        if( $level == $cur_level )
        {
            $tree[] = array( $list[ $pos ][ 'ID_Cat' ] => $list[ $pos ][ 'CatName' ] );
        }
        else if( $level < $cur_level )
        {
            $tree[] = parse_catalog_tree_internal( $list, $pos, $cur_level );
            continue;
        }
        else
        {
            return $tree;
        }
        
        ++$pos;
    }

    return $tree;
}

// Преобразует списочное представление каталога в дерево.
function get_catalog_tree_page_select_options( &$list )
{
    if( !$list ) return array();
    $pos = 0;
    return get_catalog_tree_page_select_options_internal( $list, $pos, $list[ $pos ][ 'level' ] );
}

function get_catalog_tree_page_select_options_internal( &$list, &$pos, $level )
{
    $tree = array();

    while( $pos < count( $list ) )
    {
        $cur_level = $list[ $pos ][ 'level' ];
//        print_r( $level . ' ' . $cur_level . ' ' . $list[ $pos ][ 'CatName' ] . "\n" );
        if( $level == $cur_level )
        {
            $tree[] = array( $list[ $pos ][ 'ID' ], $list[ $pos ][ 'CatName' ] );
        }
        else if( $level < $cur_level )
        {
            $tree[ count( $tree ) - 1 ][ 2 ] = 
                get_catalog_tree_page_select_options_internal( $list, $pos, $cur_level );
            continue;
        }
        else
        {
            return $tree;
        }
        
        ++$pos;
    }
    
    return $tree;
}


/*
*
*   Class RichEditor.
*
*/

class RichEditor
{
    // Убирает из текста редактора всякий мусор.
    static 
    function clear_html( $html )
    {
        return str_replace( '<p>&nbsp;</p>', '', $html );
    }
}


/*
*
*
*
*/


function create_select_options( $options, $selected = array() )
{
    if( !is_array( $options ) ) return '';

    // $options есть массив строк элемента <select>.
    // Каждая строка является массивом из максимум трех элементов.
    // [ 0 ] == value
    // [ 1 ] == текстовое описание
    // [ 2 ] == array( вложенный массив точно такой же структуры ).
    
    // Когда [ 2 ] отсутствует, формируется элемент <option>.
    // Когда [ 2 ] имеется (даже пустой), формируется элемент <optgroup>.
        
    if( !is_array( $selected ) ) $selected = array( $selected );
    
    // Перевести все элементы массива выбранных элементов в строки.
    foreach( $selected as $k => $v ) $selected[ $k ] = (string)$v;

    $res = '';
    foreach( $options as $v )
    {
        if( isset( $v[ 2 ] ) )
        {
            // optgroup
            $res .= '<optgroup label="' . htmlspecialchars( $v[ 1 ] ) . '">' . "\r\n";
            $res .= create_select_options( $v[ 2 ], $selected );
            $res .= '</optgroup>' . "\r\n";
        }
        else
        {
            // option
            $res .= '<option value="' . $v[ 0 ] . '"';
            if( in_array( (string)$v[ 0 ], $selected ) ) $res .= ' selected';
            $res .= '>' . htmlspecialchars( $v[ 1 ] ) . '</option>' . "\r\n";
        }
    }
    
    return $res;
}

//  scandir для php4
if( !function_exists('scandir') ) {
    function scandir($directory, $sorting_order = 0) {
        $dh  = opendir($directory);
        while( false !== ($filename = readdir($dh)) ) {
            $files[] = $filename;
        }
        if( $sorting_order == 0 ) {
            sort($files);
        } else {
            rsort($files);
        }
        return($files);
    }
}


if( !function_exists( 'file_put_contents' ) && !defined( 'FILE_APPEND' ) )
{
    define('FILE_APPEND', 1);

    function file_put_contents( $n, $d, $flag = false )
    {
        $mode = ( $flag == FILE_APPEND || strtoupper( $flag ) == 'FILE_APPEND' ) ? 'a' : 'w';
        $f = @fopen( $n, $mode );
        if( $f === false) return 0;
        
        if( is_array( $d ) ) $d = implode( $d );
        
        $bytes_written = fwrite( $f, $d );
        fclose( $f );
        
        return $bytes_written;
    }
}


?>
