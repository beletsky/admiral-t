<?


require_once( PATH_TO_ROOT . 'inc/user.class.php' );


// ��������� ������ ���������� ������ �����, ��� ��������.
$g_site_user = NULL;
$login_errors = array();
if( get_http_var( 'login', '' ) == 'login' )
{
    if( isset( $form[ 'Login' ] ) && isset( $form[ 'Password' ] ) )
    {
        if( !$form[ 'Login' ] ) $login_errors[] = '������� ��� ������������!';
        else
        {
            $g_site_user = User::login( $form[ 'Login' ], $form[ 'Password' ] );
            if( isset( $g_site_user ) )
            {
                // ������� �� ��� �� �������� ������� GET.    
                header( 'HTTP/1.1 303 See Other' ); 
                header( 'Location: ' . $_SERVER[ 'REQUEST_URI' ] );
                exit();
            }
            
            $login_errors[] = '�������� ���� �����-������!';
        }
    }
}
else $g_site_user = User::login();

// ��������� �������.
if( get_http_var( 'login', '' ) == 'logout' )
{
    if( isset( $g_site_user ) ) $g_site_user->logout();
    
    // ������� �� ��� �� �������� ������� GET.    
    header( 'HTTP/1.1 303 See Other' ); 
    header( 'Location: ' . $_SERVER[ 'REQUEST_URI' ] );
    exit();
}


?>
