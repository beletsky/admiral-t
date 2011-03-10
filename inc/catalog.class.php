<?


include_once( PATH_TO_ROOT . 'inc/static_page.class.php' );
include_once( PATH_TO_ROOT . 'inc/tree.class.php' );


class Catalog
{
    // Дерево страниц сайта.
    private $m_page_tree = array();
    // Страницы сайта.
    private $m_pages = array();
    // Страницы сайта по коду.
    private $m_page_codes = array();
    
    // Текущая страница сайта.
    private $m_page_id = 0;
    // Путь к текущей странице через дерево страниц.
    private $m_path_id = array();
    // Главная страница сайта.
    private $m_index_page_id = 0;
    
    // При разборе url часть параметров формируют путь 
    // к статической странице, лежащей в основе страницы,
    // остальные уходят в параметры обработчика страницы.
    // По мере выборки параметров код забирает данные из $m_params
    // и вставляет их в $m_path_params, формируя эталонный путь.
    
    // Список ещё не разобранных параметров в url загружаемой страницы.
    private $m_params = array();
    // Список параметров, формирующих путь к текущей странице.
    private $m_path_params = array();
    // Название страниц, получающихся добавлением очередного параметра к пути.
    private $m_path_params_names = array();

    public function __construct( $url = NULL )
    {
        $this->load_page_tree();

        // Для админских целей текущий путь совершенно не нужен.
        if( is_null( $url ) ) return;
                
        // Разобрать путь к текущей странице.
        $url = preg_replace( '#^' . SITE_ROOT_PATH . '(.*)' . PATH_DELIMITER . '?$#is', 
            '$1', trim( $url ) );
        if( strtolower( substr( $url, -strlen( HTML_EXTENSION ) ) ) == HTML_EXTENSION )
        {
            $url = substr( $url, 0, -strlen( HTML_EXTENSION ) );
        }
        
        $path = $url ? explode( PATH_DELIMITER, $url ) : array();
        if( $path ) $this->proceed_full_path( $path, $this->m_page_tree );
                
        if( $this->m_path_id ) $this->m_page_id = $this->m_path_id[ count( $this->m_path_id ) - 1 ];
        else
        {
            // Если запрос не пуст, а страница не найдена, это хороший повод для 404 страницы.
            // Оставим в любом случае главную страницу, а затем отловим наличие неразобранных
            // параметров в основном коде.
            $this->m_page_id = $this->m_index_page_id;
        }
                        
        if( !$this->m_page_id )
        {
            die( 'Error parsing path: ' . $url );
        }

//        // Если текущий путь по глубине не соответствует заданному,
//        // выполнить редирект. Значит, чего-то не было найдено.
//        if( count( $path ) > count( $this->m_path_id ) )
//        {
//            redirect( 303, $this->path_url( $this->m_path_id ) );
//        }

        // Не соответствующие страницам параметры перенести в список параметров.
        $this->m_params = array_slice( $path, count( $this->m_path_id ) );
    }

    public function index_page() { return $this->m_index_page_id; }
        
    // Возвращает текущую статическую страницу или страницу с указанным номером.
    public function page( $id = null ) { return $this->m_pages[ is_null( $id ) ? $this->m_page_id : $id ]; }
    public function page_by_code( $code ) { return $this->m_page_codes[ $code ]; }

    // Возвращает полный путь к странице по указанному пути с указанными параметрами.
    public function path_url( $path_id, $params = array() )
    {
        $codes = array();
        foreach( $path_id as $id )
        {
            $codes[] = $this->m_pages[ $id ]->page_code();
        }

        // В случае главной страницы путь должен быть простой косой чертой.
        if( count( $codes ) == 1 && $codes[ 0 ] == 'index' ) $codes = array();
        
        return SITE_ROOT_PATH . implode( PATH_DELIMITER, 
            array_merge( $codes, $params ) );
    }
    
    public function current_path_url( $add_params = array() )
    {
        return $this->path_url( $this->m_path_id, array_merge( $this->m_path_params, $add_params ) );
    }
    
    public function page_url( $id ) { return $this->path_url( $this->get_path_to( $id ) ); }
    public function page_by_code_url( $code ) { return $this->page_url( $this->page_by_code( $code )->id() ); }

    public function redirect( $code )
    {
        if( $code == 301 ) header( 'HTTP/1.1 303 Moved Permanently' );
        else if( $code == 303 ) header( 'HTTP/1.1 303 See Other' ); 
        header( 'Location: ' . $this->current_path_url() . HTML_EXTENSION );
        exit();
    }

    // Возвращает дерево страниц, вложенных в указанную,
    // пустой массив, если в страницу ничего не вложено,
    // null - если страница не найдена.
    public function get_tree_from( $inside_page_id = 0, $tree = null )
    {
        if( !$inside_page_id ) return $this->m_page_tree;
        if( is_null( $tree ) ) $tree = $this->m_page_tree;
                
        for( $i = 0; $i < count( $tree ); ++$i )
        {
            $page_id = $tree[ $i ];
            $subtree = ( isset( $tree[ $i + 1 ] ) && is_array( $tree[ $i + 1 ] ) )
                ? $tree[ $i + 1 ] : array();
            
            if( $page_id == $inside_page_id ) return $subtree;
        
            // Обойти следующий уровень вложенности.
            if( $subtree )
            {
                $result = $this->get_tree_from( $inside_page_id, $subtree );
                if( isset( $result ) ) return $result;
                
                // Пропустить вложенный массив.
                ++$i;
            }
        }
        
        return null;
    }
        
    // Возвращает путь к указанной странице.
    public function get_path_to( $path_to_page_id, $tree = NULL, $path = array() )
    {
        if( is_null( $tree ) ) $tree = $this->m_page_tree;
                
        for( $i = 0; $i < count( $tree ); ++$i )
        {
            $page_id = $tree[ $i ];
            $page_path = $path;
            array_push( $page_path, $page_id );

            if( $page_id == $path_to_page_id ) return $page_path;
                    
            // Для следующего уровня вложенности попытаться получить путь.
            if( isset( $tree[ $i + 1 ] ) && is_array( $tree[ $i + 1 ] ) )
            {
                $result = $this->get_path_to( $path_to_page_id, $tree[ $i + 1 ], $page_path );
                if( isset( $result ) ) return $result;
                // Пропустить вложенный массив.
                ++$i;
            }
        }
        
        return null;
    }
        
    // Преобразовывает дерево страниц в дерево для формирования меню.
    public function make_menu_tree( $menu_array, $tree = NULL, $parent = NULL, $path = array() )
    {
        $menu_tree = array();

        if( is_null( $tree ) ) $tree = $this->m_page_tree;
                
        for( $i = 0; $i < count( $tree ); ++$i )
        {
            $page_id = $tree[ $i ];
            $page = $this->m_pages[ $page_id ];
            $page_path = $path;
            array_push( $page_path, $page_id );
        
            // Для следующего уровня вложенности попытаться получить подменю.
            $submenu = array();
            if( isset( $tree[ $i + 1 ] ) && is_array( $tree[ $i + 1 ] ) )
            {
                $submenu = $this->make_menu_tree( $menu_array, $tree[ $i + 1 ], $page,
                    $page_path );
                // Пропустить вложенный массив.
                ++$i;
            }
            
            // Учитывать только страницы с указанным видом меню,
            // либо у которых есть подменю с указанным типом.
            if( !$submenu && !in_array( $page->menu(), $menu_array ) ) continue;
            
            $menu_tree[] = array( 
                'Page' => $page,
                'Parent' => $parent,
                'Path' => $page_path,
                'Active' => ( in_array( $page_id, $this->m_path_id ) 
                    || ( $page_id == $this->m_page_id ) ),
                'Submenu' => $submenu
            );
        }
        
        return $menu_tree;
    }

    // Преобразовывает дерево страниц в дерево для списка выбора.
    public function make_select_tree( $menu_array, $tree = NULL )
    {
        $select_tree = array();

        if( is_null( $tree ) ) $tree = $this->m_page_tree;
                
        for( $i = 0; $i < count( $tree ); ++$i )
        {
            $page_id = $tree[ $i ];
            $page = $this->m_pages[ $page_id ];
        
            // Для следующего уровня вложенности попытаться получить подменю.
            $submenu = array();
            if( isset( $tree[ $i + 1 ] ) && is_array( $tree[ $i + 1 ] ) )
            {
                $submenu = $this->make_select_tree( $menu_array, $tree[ $i + 1 ] );
                // Пропустить вложенный массив.
                ++$i;
            }
            
            // Учитывать только страницы с указанным видом меню,
            // либо у которых есть подменю с указанным типом.
            if( !$submenu && !in_array( $page->menu(), $menu_array ) ) continue;
            
            $select_tree[] = array( $page_id, $page->title(), 
                $submenu ? $submenu : NULL );
        }
        
        return $select_tree;
    }

    // Работа с параметрами.
    
    // Возвращает параметр с указанным номером, для подглядывания вперед.
    public function get_param_by_number( $number = 0, $default = NULL )
    {
        if( !isset( $this->m_params[ $number ] ) ) return $default;
        return $this->m_params[ $number ];
    }


    // Выбирает из стека следующий параметр и возвращает его,
    // либо указанное значение, если такового не обнаружено.
    public function get_next_param( $default = NULL )
    {
        $param = array_shift( $this->m_params );
        if( !isset( $param ) ) return $default;
        return $param;
    }
    
    public function param_list_empty() { return !$this->m_params; }

    // Работа с путями параметров.
    public function path_add( $name, $param )
    {
        $this->m_path_params[] = $param;
        $this->m_path_params_names[] = $name;
    }

    public function path_remove_last()
    {
        if( is_null( array_pop( $this->m_path_params ) ) ) array_pop( $this->m_path_id );
        else array_pop( $this->m_path_params_names );
    }
    
    public function path_replace( $path_id )
    {
        $this->m_path_id = $path_id;
        $this->m_path_params = array();
        $this->m_path_params_names = array();
    }

    public function make_path_array()
    {
        $path = array();
        
        // Путь по страницам сайта.
        $path_id = array();
        foreach( $this->m_path_id as $id )
        {
            $path_id[] = $id;
            $page = $this->m_pages[ $id ];
            $path[] = array(
                'Name' => $page->menu_title(),
                'URL'  => $this->path_url( $path_id ),
            );
        }
        
        // Путь по параметрам.
        $params = array();
        foreach( $this->m_path_params as $k => $param )
        {
            $params[] = $param;
            $path[] = array(
                'Name' => $this->m_path_params_names[ $k ],
                'URL'  => $this->path_url( $path_id, $params ),
            );
        }
        
        return $path;
    }    
                    
    // private
    
    private function load_page_tree()
    {
        global $g_tree_db;
        $tree = new Tree( $g_tree_db, new StaticPageTree() );
        list( $ids, $this->m_page_tree ) = $tree->get_tree();
        
        // Пустое дерево.
        if( !$ids ) return;
            
        // Получить все страницы в дереве.
        global $g_static_page_db;
        $f = $g_static_page_db->filter();
        $f->id_in( $ids );
        
        $list = array();
        $g_static_page_db->load_list( $list, $f );
        
        // Сформировать ассоциативный массив.
        foreach( $list as $v )
        {
            $this->m_pages[ $v->id() ] = $v;
            $this->m_page_codes[ $v->page_code() ] = $v;
            if( $v->page_code() == 'index' ) $this->m_index_page_id = $v->id();
        }
    }
    
    private function find_path_to_page( $comparator, $tree = NULL )
    {
        $prev_id = 0;
        foreach( $tree as $v )
        {
            if( $is_array( $v ) )
            {
                if( $result = $this->find_path( $comparator, $v ) )
                {
                    return array_unshift( $result, $prev_id );
                }
            }
            else
            {
                if( $comparator( $this->pages[ $v ] ) ) return array( $v );
            }
            
            $prev_id = $v;
        }
        
        return array();
    }
    
    private function proceed_full_path( $path, $tree = NULL, $level = 0 )
    {
        $found = false;
        foreach( $tree as $v )
        {
            if( is_array( $v ) )
            {
                if( $found )
                {
                    if( isset( $path[ $level + 1 ] ) )
                    {
                        $this->proceed_full_path( $path, $v, $level + 1 );
                    }
                    return;
                }
            }
            else
            {
                if( $found ) return;
            
                if( $this->m_pages[ $v ]->page_code() == $path[ $level ] )
                {
                    $this->m_path_id[] = $v;
                    $found = true;
                }
            }
        }
    }
}


class CatalogComparatorPageWithCode
{
    private $m_code;
    public function __construct( $code ) { $this->m_code = $code; }
    
    static public function compare( $obj )
    {
        return $obj->page_code() == $this->m_code;
    }
}


?>
