<?

require_once( PATH_TO_ADMIN . 'inc/init.inc.php' );
include_once( PATH_TO_EDITOR . EDITOR_SCRIPT );
include_once( PATH_TO_ROOT . 'inc/edit_object_page.class.php' );

if( !defined( 'DBNAME' ) ) define( 'DBNAME', strtolower( CLASSNAME ) );

include_once( PATH_TO_ROOT . 'inc/' . DBNAME . '.class.php' );

class Edit
    extends EditObjectPage
{
    var $db;
    
    function Edit( $parent_page, $prefix = '', $start_clean = false )
    {
        $dbname = 'g_' . DBNAME . '_db';
        global $$dbname;
        $this->db = $$dbname;
        parent::EditObjectPage( $parent_page, $prefix, $start_clean );
    }

    function create_object( $id = NULL, $form = NULL, $from_form = false )
    {
        $class = CLASSNAME;
        return new $class( $id, $form, $from_form );
    }
    
    function get_list( &$list, $order_limit )
    {
        return $this->db->load_list( $list, NULL, $order_limit );
    }
    
    function get_list_title() { return PAGE_TITLE; }
}


$edit = new Edit( $this_page . '?' );


require( PATH_TO_ADMIN . 'inc/top.inc.php' );
print $edit->show();
require( PATH_TO_ADMIN . 'inc/bottom.inc.php' );


?>
