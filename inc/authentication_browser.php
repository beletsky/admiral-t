<?


require_once( PATH_TO_ROOT . 'inc/client.class.php' );


// Понятия не имею, что это за переменная...
$realm = 'ARTtech_position_analyze_area';


// Получить список пользователей.
//user => password
$users = client_users();

// Если авторизации никакой не было,
// потребовать у пользователя ввести данные.
if( empty( $_SERVER[ 'PHP_AUTH_DIGEST' ] ) )
{
    header( 'HTTP/1.1 401 Unauthorized' );
    header( 'WWW-Authenticate: Digest realm="' . $realm
        . '",qop="auth",nonce="' . uniqid() . '",opaque="' 
        . md5( $realm ) . '"' );

    die( 'Login and password required!' );
}

// Разобрать ответ браузера по составным частям.
$data = http_digest_parse( $_SERVER[ 'PHP_AUTH_DIGEST' ] );

// Проверить наличие имени пользователя.
if( !$data || !isset( $users[ $data[ 'username' ] ] ) )
{
   header( 'WWW-Authenticate: Digest realm="' . $realm
       . '",qop="auth",nonce="' . uniqid() . '",opaque="' 
       . md5( $realm ) . '"' );
   header( 'HTTP/1.0 401 Unauthorized' );
   echo 'You must enter a valid login ID and password to access this resource.';
   exit;
}

// Создать ожидаемую версию ответа браузера.
$A1 = md5( $data[ 'username' ] . ':' . $realm 
    . ':' . $users[ $data[ 'username' ] ] );
$A2 = md5( $_SERVER[ 'REQUEST_METHOD' ] . ':' . $data[ 'uri' ] );
$valid_response = md5( $A1 . ':' . $data[ 'nonce' ] 
    . ':' . $data[ 'nc' ] . ':' . $data[ 'cnonce' ] 
    . ':' . $data[ 'qop' ] . ':' . $A2 );
    
// Если ожидаемый ответ не совпадает с реальным, отшить пользователя.
if( $data[ 'response' ] != $valid_response )
{
   header( 'WWW-Authenticate: Digest realm="' . $realm
       . '",qop="auth",nonce="' . uniqid() . '",opaque="' 
       . md5( $realm ) . '"' );
       
   header( 'HTTP/1.0 401 Unauthorized' );
   echo 'You must enter a valid login ID and password to access this resource.';
   exit;
}

// А здесь уже всё в порядке, можно создать клиента по логину.
$f = $g_client_db->filter();
$f->login( $data[ 'username' ] );
$list = array();
$g_client_db->load_list( $list, $f );
if( !isset( $list[ 0 ] ) )
{
   header( 'WWW-Authenticate: Digest realm="' . $realm
       . '",qop="auth",nonce="' . uniqid() . '",opaque="' 
       . md5( $realm ) . '"' );
   header( 'HTTP/1.0 401 Unauthorized' );
   echo 'Error during loggin in! Try again.';
   exit;
}

$g_client = $list[ 0 ];


//if( $page_code == 'logout' )
//{
//    header( 'HTTP/1.1 401 Unauthorized' );
//    header( 'WWW-Authenticate: Digest realm="' . $realm
//        . '",qop="auth",nonce="' . uniqid() . '",opaque="' 
//        . md5( $realm ) . '"' );

//    die( 'Login and password required!' );
//}


// function to parse the http auth header
function http_digest_parse( $txt )
{
    // protect against missing data
    $needed_parts = array( 
        'nonce'     => 1, 
        'nc'        => 1, 
        'cnonce'    => 1, 
        'qop'       => 1, 
        'username'  => 1, 
        'uri'       => 1, 
        'response'  => 1
    );
    $data = array();

    preg_match_all( '@(\w+)=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', 
        $txt, $matches, PREG_SET_ORDER );
        
    foreach( $matches as $m )
    {
       $data[ $m[ 1 ] ] = $m[ 3 ] ? $m[ 3 ] : $m[ 4 ];
       unset( $needed_parts[ $m[ 1 ] ] );
    }
    
    return $needed_parts ? false : $data;
}


function client_users()
{
    global $g_client_db;
    $list = array();
    $g_client_db->load_list( $list );
    
    $users = array();
    foreach( $list as $v )
    {
        $users[ $v->data( $v->m_db->m_login ) ] = 
            $v->data( $v->m_db->m_password );
    }
        
    return $users;
}


?>