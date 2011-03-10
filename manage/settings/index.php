<?

define( 'PATH_TO_ROOT',  '../../' );
define( 'PATH_TO_ADMIN', '../' );
define( 'PAGE_TITLE', 'Настройки сайта' );
define( 'PAGE_CODE', 'settings' );

require_once( PATH_TO_ADMIN . 'inc/init.inc.php' );
include_once( PATH_TO_EDITOR . EDITOR_SCRIPT );

include_once( PATH_TO_ROOT . 'inc/settings.class.php' );
include_once( PATH_TO_ROOT . 'inc/edit_object.class.php' );


class Edit
    extends TrueEditObject
{
    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
        return new Settings( $id, $form, $from_form );
    }
}


// Создать объект баннера.
$obj = new Settings( '1' );
$edit = new Edit( $obj, $this_page . '?' );


require( PATH_TO_ADMIN . 'inc/top.inc.php' );
print $edit->show();
require( PATH_TO_ADMIN . 'inc/bottom.inc.php' );


?>
