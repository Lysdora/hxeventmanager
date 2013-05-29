<?php

if(!class_exists('WP_List_Table'))
{
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class CustomBookingsFieldsTable extends WP_List_Table
{
/**
     * Constructor, we override the parent to pass our own arguments
     * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
    */
    public function __construct()
    {
        parent::__construct(array(
            'singular' => 'field',
            'plural' => 'fields',
            'ajax' => true
            ));
    }

/**
 * Define the columns that are going to be used in the table
 * @return array $columns, the array of columns to use with the table
 */
    public function get_columns()
    {
        return $columns = array(
            'field_label' => __('Field Label'),
            'field_type' => __('Type'),
            'field_description' => __('Description'),
            'field_required' => __('Required?'),
            'field_active' => __('Active?')
            );
    }


/**
 * Decide which columns to activate the sorting functionality on
 * @return array $sortable, the array of columns that can be sorted by the user
 */
    public function get_sortable_columns()
    {
        return $columns = array();
    }

/**
 * Prepare the table with different parameters, pagination, columns and table elements
 */
    public function prepare_items() 
    {
        global $wpdb;

        $screen = get_current_screen();
        $tablenames = CustomBookings::$tablenames;
        $per_page = 25;

        /* -- Preparing your query -- */
        $query = "SELECT * FROM ".$wpdb->prefix.$tablenames['fields'];

        /* -- Ordering parameters -- */
            //Parameters that are going to be used to order the result
            $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'field_label';
            $order = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : 'ASC';
            if(!empty($orderby) & !empty($order))
            {
                $query.=' ORDER BY '.$orderby.' '.$order;
            }

        /* -- Pagination parameters -- */
            //Number of elements in your table?
            $totalitems = $wpdb->query($query); //return the total number of affected rows

            //Which page is this?
            $paged = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';

            //Page Number
            if(empty($paged) || !is_numeric($paged) || $paged <= 0)
            { 
                $paged = 1;
            }

            //How many pages do we have in total?
            $totalpages = ceil($totalitems / $per_page);
            
            //adjust the query to take pagination into account
            if(!empty($paged) && !empty($perpage))
            {
                $offset = ($paged - 1) * $perpage;
                $query .= ' LIMIT '.(int)$offset.','.(int)$perpage;
            }

        /* -- Register the pagination -- */
            $this->set_pagination_args( array(
                "total_items" => $totalitems,
                "total_pages" => $totalpages,
                "per_page" => $perpage
            ) );
            //The pagination links are automatically built according to those parameters

        /* -- Register the Columns -- */
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

        /* -- Fetch the items -- */
            $this->items = $wpdb->get_results($query);
    }

    function column_default($item, $column_name)
    {
        switch($column_name)
        {
            default:
            return stripslashes($item->$column_name);
            break;
        }
    }

    function column_field_label($item)
    {
        $actions = array(
            'edit'      => sprintf('<a href="edit.php?post_type='.EM_POST_TYPE_EVENT.'&page=%s&action=%s&field=%s">'.__('Edit').'</a>',$_REQUEST['page'],'edit',$item->field_slug),
            'delete'    => sprintf('<a href="edit.php?post_type='.EM_POST_TYPE_EVENT.'&page=%s&action=%s&field=%s" class="button_delete">'.__('Delete').'</a>',$_REQUEST['page'],'delete',$item->field_slug)
        );
        
        //Return the title contents
        return sprintf('%1$s %2$s',
            /*$1%s*/ stripslashes($item->field_label),
            /*$2%s*/ $this->row_actions($actions)
        );
    }

    function column_field_required($item)
    {
        return sprintf('<input disabled type="checkbox" name="'.$item->field_slug.'" value="%d" %s />', $item->field_required, ($item->field_required == '1' ? 'checked' : ''));
    }

    function column_field_active($item)
    {
        return sprintf('<input disabled type="checkbox" name="'.$item->field_slug.'" value="%d" %s />', $item->field_active, ($item->field_active == '1' ? 'checked' : ''));
    }

    function generateURL($action, $text, $action_value)
    {
        return sprintf('<a href="?page=%s&action=%s&event_ID=%s">%s</a>', $_REQUEST['page'], $action, $action_value, $text);
    }

/**
     * Send required variables to JavaScript land
     *
     * @access private
     */
    function _js_vars()
    {
        $current_screen = get_current_screen();

        $args = array(
            'class'  => get_class( $this ),
            'screen' => array(
                'id'   => $current_screen->id,
                'base' => $current_screen->base,
            )
        );

        printf( "<script type='text/javascript'>list_args = %s;</script>\n", json_encode( $args ) );
    }

    function ajax_response()
    {
        return cirrent_user_can('manage_bookings');
    }
}