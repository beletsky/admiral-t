<?


define( 'PATH_TO_ROOT',  '../../' );
define( 'PATH_TO_ADMIN', '../' );
define( 'PAGE_TITLE', 'Пользовательские шаблоны' );
define( 'PAGE_CODE', 'user_tpl' );


require_once( PATH_TO_ADMIN . 'inc/init.inc.php' );

include_once( PATH_TO_ROOT . 'inc/user_tpl.class.php' );
include_once( PATH_TO_EDITOR . EDITOR_SCRIPT );


class Edit
    extends EditObject
{
    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
        return new UserTPL( $id, $form, $from_form );
    }
    
    function get_list( &$list, $order_limit )
    {
        global $g_user_tpl_db;
        return $g_user_tpl_db->load_list( $list, NULL, $order_limit );
    }
    
    function get_list_title() { return PAGE_TITLE; }
}


$edit = new Edit( $this_page . '?' );


require( PATH_TO_ADMIN . 'inc/top.inc.php' );
print $edit->show();
require( PATH_TO_ADMIN . 'inc/bottom.inc.php' );


?>
