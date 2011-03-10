<?

define( 'PATH_DELIMITER', '/' );
define( 'HTML_EXTENSION', '.html' );

define( 'HOST_ROOT', 'admiral-t.ru' );
define( 'HOST_CODE', 'admiral' );

$g_sites = array(
    HOST_CODE => array(
        'Host'      => HOST_ROOT,
        'Abbrev'    => HOST_CODE,
        'Name'      => '',
        'Title'     => '',
        'EmailFrom' => 'info@' . HOST_ROOT
    )
);

?>
