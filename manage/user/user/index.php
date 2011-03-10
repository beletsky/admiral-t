<?

define( 'PATH_TO_ROOT',  '../../../' );
define( 'PATH_TO_ADMIN', '../../' );
define( 'PAGE_TITLE', 'Пользователи' );
define( 'PAGE_CODE', 'user_user' );

require_once( PATH_TO_ADMIN . 'inc/init.inc.php' );

include_once( PATH_TO_ROOT . 'inc/user.class.php' );
include_once( PATH_TO_EDITOR . EDITOR_SCRIPT );

class Edit
    extends EditObject
{
    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
        return new User( $id, $form, $from_form );
    }
    
    function get_list( &$list, $order_limit )
    {
        global $g_user_db;
        $r = $g_user_db->load_list( $list, NULL, $order_limit );
        return $r;
    }
    
    function get_list_title() { return PAGE_TITLE; }
    
    function do_send()
    {
        $this->obj->send_activation_message();
        $this->obj->save();
        
        // Продолжаем в режиме правки.
        $this->action = ACT_EDIT;
    }
}


$edit = new Edit( $this_page . '?' );


require( PATH_TO_ADMIN . 'inc/top.inc.php' );
print $edit->show();
require( PATH_TO_ADMIN . 'inc/bottom.inc.php' );


?>
