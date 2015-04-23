<?php 

/*
Plugin Name: HX Event Manager
Plugin URI: http://welcometofryslan.nl/
Version: 1.3
Author: Coen de Jong
Author URI: http://shifthappens.nl
Description: Plugin to extend the functionality of the Events Manager plugin with features specific to bigger events. 
Text Domain: hxem
Domain Path: /languages
License: GPL2
*/

/*  Copyright 2013-2015  Coen de Jong  (email : coen@shifthappens.nl )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('CB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CB_DB_VERSION', 7); //this should be set to the newest db version

if(!isset($_COOKIE['PHPSESSID']))
    session_start();

/* Actions and Filters to hook into WordPress ecosystem */

//Installing and checking CB database tables on activation and plugin loading
register_activation_hook(__FILE__, array('CustomBookings', 'install_db'));
add_action('plugins_loaded', array('CustomBookings', 'db_upgrade_check'));



/* Actions and Filters to hook into data from existing Events Manager plugin */

//Making sure that the Custom Fields manager is available from the Events menu in Admin
add_filter('em_create_events_submenu', array('CustomBookings', 'create_events_submenu'));

//To create a custom form based on the custom form fields defined in the database
add_action('em_booking_form_custom', array('CustomBookingsForm', 'custom_form'));

//To make sure our custom form fields are saved together with a new booking
add_filter('em_booking_save', array('CustomBookingsForm', 'save_booking'), 10, 2);

//Validate our custom fields based on their validation rules in the database
add_filter('em_booking_validate', array('CustomBookingsForm', 'validate_booking'), 10, 2);

//Tell the bookings table in Admin area which custom columns are available
add_filter('em_bookings_table_cols_template', array('CustomBookings', 'bookings_table_cols_template'), 10, 2);

//static function to process the data that belongs to each column of each row; here data from our custom fields is processed and given back to be displayed in the table
add_filter('em_bookings_table_rows_col', array('CustomBookings', 'bookings_table_rows_col'), 10, 5);

//Special processing static function for when bookings have the status ID '5' (Awaiting Payment).
//For some reason all actions except delete are gone when a booking receives this status
add_filter('em_bookings_table_booking_actions_5', array('CustomBookings', 'bookings_table_booking_actions_5'), 10, 2);

//to force the status of the bookings query back to 'all' (if not set through POST)
add_action('em_bookings_table_header', array('CustomBookings', 'bookings_table_header'), 10, 1);

//to change the semantics of the booking system from Pending -> Awaiting Payment
add_action('em_booking', array('CustomBookings', 'booking_change_semantics'), 10, 2);
add_action('em_bookings_table', array('CustomBookings', 'bookings_table_change_semantics'), 10, 1);

//Hook for the "editing a single booking" page in Admin (to display the data of our custom fields again)
add_action('em_bookings_single_custom', array('CustomBookings', 'bookings_single_custom'), 10, 1);

//To show a WP native admin notice saying stuff like settings saved, custom field created, etc.
add_action('admin_notices', array('CustomBookings', 'show_message'), 10, 2);

//WP hooks to include script on the front page for enhancements to the bookings table
//this is done with JS because it's easier than to hook into the code here and easily disabled if unwanted
add_action('init', array('CustomBookings', 'add_clientside_scripts'));

//A shortcode to display the bookings table anywhere in your posts or pages (to let visitors see who is going to an event)
add_shortcode('bookings-table', 'display_bookings_table');

//To make inclusion of bookings table on front-end possible we need to delete the check on admin permissions, otherwise logged out users won't see anything
add_filter('em_bookings_get_default_search', array('CustomBookings', 'modify_bookings_get_default_search'), 10, 3);

//Upon deletion of a booking, all associated custom field data must be erased as well, to keep the db clean
add_filter('em_booking_delete', array('CustomBookings', 'booking_delete'), 10, 2);

//Upon deletion of a user, all associated custom booking data needs to be erased as well, to keep the db clean 
add_action('deleted_user', array('CustomBookings', 'delete_user_booking_data'));

//Register the JS scripts and their dependencies to WP, so all we have to do later on is wp_enqueue_script('handle')
wp_register_script('cb-clientside-scripts', plugin_dir_url(__FILE__) . 'cb-clientside.js', array('jquery'));

//DEBUG hook to see what queries are executed during page load
//define(SAVEQUERIES, true);
//add_action('shutdown', 'write_queries');

if(!class_exists('CustomBookingsFieldsTable'))
    include_once(CB_PLUGIN_PATH . 'classes/custombookingsfieldstable.class.php');

class CustomBookings
{
    static $tablenames = array(
        'fields'        => 'cb_fields',
        'field_data'    => 'cb_field_data'
    );

    function __construct()
    {
    }

    static function install_db($upgrade = FALSE)
    {
       global $wpdb;

       $tablenames = self::$tablenames;
       $sql = file_get_contents(CB_PLUGIN_PATH . 'db.sql');
    
       foreach($tablenames as $tablename)
       {
           $sql = str_replace('$'.$tablename, $wpdb->prefix.$tablename, $sql);
       }
          
       require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
       dbDelta($sql);
     
       update_option( "cb_db_version", CB_DB_VERSION);

       if($upgrade)
            self::show_message('HX Events Manager Database updated to version '.CB_DB_VERSION);
    }

    static function db_upgrade_check()
    {
        $curr_db_version = get_site_option( 'cb_db_version' );
        if($curr_db_version != CB_DB_VERSION) 
        {
            self::install_db(true);
        }
    }

    static function add_clientside_scripts()
    {
        wp_enqueue_script('cb-clientside-scripts');
    }

    static function booking_change_semantics($EM_Booking, $booking_data)
    {
        $EM_Booking->status_array = array(
            0 => __('Awaiting Payment','dbem'),
            1 => __('Approved','dbem'),
            2 => __('Rejected','dbem'),
            3 => __('Cancelled','dbem'),
            4 => __('Awaiting Online Payment','dbem'),
            5 => __('Awaiting Payment','dbem')
        );
    }

    static function create_events_submenu($plugin_pages)
    {
        $plugin_pages['custom_form_fields'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Custom Form Fields'),__('Custom Form Fields'), 'manage_bookings', "events-manager-custom-form-fields", array('CustomBookingsFormEditor', 'form_editor_overview'));

        return $plugin_pages;
    }

    static function bookings_table_change_semantics($EM_Bookings_Table)
    {
        $EM_Bookings_Table->statuses = array(
            'all' => array('label'=>__('All','dbem'), 'search'=>false),
            'pending' => array('label'=>__('Awaiting Payment','dbem'), 'search'=>0),
            'confirmed' => array('label'=>__('Confirmed','dbem'), 'search'=>1),
            'cancelled' => array('label'=>__('Cancelled','dbem'), 'search'=>3),
            'rejected' => array('label'=>__('Rejected','dbem'), 'search'=>2),
            'needs-attention' => array('label'=>__('Needs Attention','dbem'), 'search'=>array(0)),
            'incomplete' => array('label'=>__('Incomplete Bookings','dbem'), 'search'=>array(0))
        );        
    }

    static function bookings_table_cols_template($cols, $EM_Bookings_Table)
    {
        $custom_fields = CustomBookingsForm::getCustomFormFields();

        foreach($custom_fields as $field)
        {
            $cols[$field->field_slug] = stripslashes($field->field_label);
        }

        //ticket id exists, so column "ticket spaces" for this table is allowed
        if(isset($_REQUEST['ticket_id']))
        {
            $cols['ticket_spaces'] = "Ticket Spaces";
        }

        return $cols;
    }

    static function bookings_table_rows_col($val, $col, $EM_Booking, $EM_Bookings_Table, $csv)
    {
        if($col == "ticket_spaces")
        {
            return self::getTicketSpaces($EM_Booking->booking_id, $_REQUEST['ticket_id']);
        }

        $cb_custom_field_values = CustomBookingsForm::getCustomFormValues($EM_Booking->event_id, $EM_Booking->person_id);

        foreach($cb_custom_field_values as $row)
        {
            
            if($row->field_slug == $col)
                return self::process_field_data_and_return($row);
        }
            return '--';
    }

    static function bookings_table_booking_actions_5($actions, $EM_Booking)
    {
        //for some strange reason, when the booking status is set to 'awaiting payment', only the Delete link is displayed in the actions column
        //of course we still need to be able to approve and/or change the booking when awaiting payment
        return array(
            'approve' => '<a class="em-bookings-approve" href="'.em_add_get_params($url, array('action'=>'bookings_approve', 'booking_id'=>$EM_Booking->booking_id)).'">'.__('Approve','dbem').'</a>',
            'reject' => '<a class="em-bookings-reject" href="'.em_add_get_params($url, array('action'=>'bookings_reject', 'booking_id'=>$EM_Booking->booking_id)).'">'.__('Reject','dbem').'</a>',
            'delete' => '<span class="trash"><a class="em-bookings-delete" href="'.em_add_get_params($url, array('action'=>'bookings_delete', 'booking_id'=>$EM_Booking->booking_id)).'">'.__('Delete','dbem').'</a></span>',
            'edit' => '<a class="em-bookings-edit" href="'.em_add_get_params($EM_Booking->get_event()->get_bookings_url(), array('booking_id'=>$EM_Booking->booking_id, 'em_ajax'=>null, 'em_obj'=>null)).'">'.__('Edit/View','dbem').'</a>'
            );
    }

    static function bookings_table_header($EM_Bookings_Table)
    {
        $EM_Bookings_Table->status = ( !empty($_REQUEST['status']) && array_key_exists($_REQUEST['status'], $EM_Bookings_Table->statuses) ) ? $_REQUEST['status']:get_option('dbem_default_bookings_search','all');
    }

    static function bookings_single_custom($EM_Booking)
    {
        $custom_fields = CustomBookingsForm::getCustomFormValues($EM_Booking->event_id, $EM_Booking->person_id);

        include(CB_PLUGIN_PATH . 'views/booking_edit.php');
    }

    static function show_message($message, $error = false)
    {
        if(empty($message))
            return;

        if ($error)
        {
            echo '<div id="message" class="error">';
        }
        else 
        {
            echo '<div id="message" class="updated fade">';
        }

        echo "<p><strong>".$message."</strong></p></div>";
    }

    static function process_field_data_and_return($row)
    {
        $options = unserialize($row->field_options);

        //error_log('row = '.print_r($row, true));

        if(is_array($options) && count($options) > 0 && array_key_exists($row->field_data, $options))
            return stripslashes($options[$row->field_data]);
        elseif($row->field_type == 'checkbox' && $row->field_data == '1')
            return stripslashes($row->field_checkbox_label);
        elseif($row->field_type == 'checkbox' && ($row->field_data == '0' || $row->field_data == NULL))
            return '--';
        else
            return stripslashes($row->field_data);
    }

    static function modify_bookings_get_default_search($merged_defaults, $array, $defaults)
    {
        unset($merged_defaults['owner']);

        return $merged_defaults;
    }

    static function booking_delete($result, $EM_Booking)
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;

        $result = $wpdb->query($wpdb->prepare('DELETE FROM '.$wpdb->prefix.$tablenames['field_data'].' '
            .'WHERE user_ID = %d AND event_ID = %d', $EM_Booking->get_person()->ID, $EM_Booking->get_event()->event_id));


        return $result;
    }

    static function delete_user_booking_data($user_ID)
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;

        $result = $wpdb->query($wpdb->prepare('DELETE FROM '.$wpdb->prefix.$tablenames['field_data'].' '
            .'WHERE user_ID = %d', $user_ID));


        return $result;        
    }

    static function getTicketSpaces($booking_id, $ticket_id)
    {
        global $wpdb;
        $result = $wpdb->get_var($wpdb->prepare('SELECT ticket_booking_spaces as spaces FROM '.EM_TICKETS_BOOKINGS_TABLE.' '
            .'WHERE booking_id = %d AND ticket_id = %d', $booking_id, $ticket_id));

        return $result;
    }
}

class CustomBookingsForm
{

    static function custom_form($EM_Event)
    {
        //error_log(print_r($EM_Event, true));
        $custom_fields = self::getCustomFormFields();
        include(CB_PLUGIN_PATH . 'views/booking_form.php');
    }

    static function save_booking($status, $EM_Booking)
    {
        global $wpdb;

        $data['field_data_ID'] = 'NULL';
        $data['user_ID'] = $EM_Booking->person_id;
        $data['event_ID'] = $EM_Booking->event_id;
        $data['booking_ID'] = $EM_Booking->booking_id;

        error_log('data save booking: '.print_r($data, true));

        foreach($_REQUEST['cb'] as $custom_field_slug => $custom_field_data)
        {
            $data['field_data'] = $custom_field_data;

            //check if we are creating a new entry or updating an existing one
            if($_REQUEST['page'] == 'events-manager-bookings')
            {
                //it's an edit
                $where = array('user_ID' => $EM_Booking->person_id, 'event_ID' => $EM_Booking->event_id, 'field_slug' => $custom_field_slug, 'booking_ID' => $EM_Booking->booking_id);
                $data = array('field_data' => $custom_field_data);

                if($wpdb->update($wpdb->prefix.CustomBookings::$tablenames['field_data'], $data, $where) === false)
                {
                    error_log('last query after update: '.$wpdb->last_query);
                    $error = new WP_Error('dberror', 'Couldn\'t update custom field with slug "'.$custom_field_slug.'"');
                    $EM_Booking->add_error('Couldn\'t update custom field with slug "'.$custom_field_slug.'"');
                    CustomBookings::show_message($error->get_error_message(), true);
                    return false;
                }
            }
            else
            {
                //it's a new entry
                $data['field_slug'] = $custom_field_slug;
                if(!$wpdb->insert($wpdb->prefix.CustomBookings::$tablenames['field_data'], $data))
                {
                    $error = new WP_Error('dberror', 'Couldn\'t insert custom field with slug "'.$custom_field_slug.'"');
                    $EM_Booking->add_error('Couldn\'t insert custom field with slug "'.$custom_field_slug.'" (query error was: '.$wpdb->last_error.')');
                    CustomBookings::show_message($error->get_error_message(), true);
                    return false;
                }

                //set the default status of a booking to 'Awaiting Payment'
                //$EM_Booking->booking_status = 5;
            }
        }

        foreach($_REQUEST['cb_checkbox'] as $custom_field_slug => $custom_field_data)
        {
            if(!isset($_REQUEST['cb'][$custom_field_slug]))
            {
                $data['field_data'] = 0;
                //check if we are creating a new entry or updating an existing one
                if($_REQUEST['page'] == 'events-manager-bookings')
                {
                    //it's an edit
                    $where = array('user_ID' => $EM_Booking->person_id, 'event_ID' => $EM_Booking->event_id, 'field_slug' => $custom_field_slug, 'booking_ID' => $EM_Booking->booking_id);

                    if($wpdb->update($wpdb->prefix.CustomBookings::$tablenames['field_data'], $data, $where) === false)
                    {
                        error_log('last query after update: '.$wpdb->last_query);
                        $error = new WP_Error('dberror', 'Couldn\'t update custom field with slug "'.$custom_field_slug.'"');
                        CustomBookings::show_message($error->get_error_message(), true);
                        return false;
                    }
                }
                else
                {
                    //it's a new entry
                    $data['field_slug'] = $custom_field_slug;
                    if(!$wpdb->insert($wpdb->prefix.CustomBookings::$tablenames['field_data'], $data))
                    {
                        $error = new WP_Error('dberror', 'Couldn\'t insert custom field with slug "'.$custom_field_slug.'"');
                        CustomBookings::show_message($error->get_error_message(), true);
                        return false;
                    }
                }                            
            }
        }

        return true;
    }

    static function validate_booking($result, $EM_Booking)
    {
        global $wpdb;
        $custom_fields = self::getCustomFormFields();

        foreach($custom_fields as $field)
        {
            if($field->field_required == '1')
            {
                if(!isset($_REQUEST['cb'][$field->field_slug]) || trim($_REQUEST['cb'][$field->field_slug]) == '')
                {
                    $EM_Booking->add_error(__(sprintf('Field "%s" cannot be empty!', $field->field_label, $field->field_slug)));
                    $result = false;
                }                
            }
        }

        return $result;
    }

    static function getCustomFormFields()
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;

        if(!isset($_SESSION['cb_custom_fields_last_updated']))
            $_SESSION['cb_custom_fields_last_updated'] = get_option('cb_custom_fields_last_updated');

        if((isset($_SESSION['cb_custom_fields_last_updated']) && !empty($_SESSION['cb_custom_fields_last_updated']) && isset($_SESSION['cb_custom_fields_last_fetched'])) 
            && ($_SESSION['cb_custom_fields_last_updated'] > $_SESSION['cb_custom_fields_last_fetched']))
        {
            return $_SESSION['cb_custom_form_fields'];
        }
        else
        {
            $query = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.$tablenames['fields'].' WHERE field_active = %d', 1);
            $results = $wpdb->get_results($query);
            $_SESSION['cb_custom_form_fields'] = $results;
            $_SESSION['cb_custom_fields_last_fetched'] = time();

            return $results;            
        }
    }

    static function getCustomFormField($field_slug, $output_type = OBJECT)
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;

        $query = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.$tablenames['fields'].' WHERE field_slug = %s', $field_slug);
        $results = $wpdb->get_results($query, $output_type);

        return $results;
    }

    static function getCustomFormValues($event_ID, $user_ID)
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;
        $field_table = $wpdb->prefix.$tablenames['fields'];
        $field_data_table = $wpdb->prefix.$tablenames['field_data'];

        $query = $wpdb->prepare('SELECT '.$field_data_table.'.field_slug, '.$field_data_table.'.field_data, '.$field_table.'.field_label, '.
                                $field_table.'.field_options, '.$field_table.'.field_type, '.$field_table.'.field_checkbox_label '.
                                'FROM '.$field_data_table.' '.
                                'JOIN '.$field_table.' '.
                                'ON '.$field_data_table.'.field_slug = '.$field_table.'.field_slug '.
                                'WHERE event_ID = %d AND user_ID = %d AND '.$field_table.'.field_active = %d', $event_ID, $user_ID, 1);
        $results = $wpdb->get_results($query);

        //error_log('last query = '.$wpdb->last_query);

        return $results;            
    }
}

class CustomBookingsFormEditor
{
    static function form_editor_overview()
    {
        if(isset($_GET['action']))
        {
            switch($_GET['action'])
            {
                case 'delete':
                self::delete_field();
                break;

                case 'update':
                case 'edit':
                self::update_field();
                break;

                case 'new':
                self::new_field();
                break;
            }
        }
        else
        {
            include(CB_PLUGIN_PATH . 'views/form_editor.php');
        }
    }

    static function delete_field()
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;

        if(!$wpdb->query($wpdb->prepare('DELETE FROM '.$wpdb->prefix.$tablenames['fields'].' WHERE field_slug = %s', $_GET['field'])))
        {
            CustomBookings::show_message('Field was NOT deleted (halted on first query)!', true);
            include(CB_PLUGIN_PATH . 'views/form_editor.php');
        }
        if(!$wpdb->query($wpdb->prepare('DELETE FROM '.$wpdb->prefix.$tablenames['field_data'].' WHERE field_slug = %s', $_GET['field'])))
        {
            CustomBookings::show_message('Field was NOT deleted (halted on second query)!', true);
            include(CB_PLUGIN_PATH . 'views/form_editor.php');
        }
        else
        {
            CustomBookings::show_message('Field was deleted!');
            include(CB_PLUGIN_PATH . 'views/form_editor.php');
        }
    }

    static function update_field()
    {
        global $wpdb;

        if(!isset($_POST['submit']))
        {
            $data = CustomBookingsForm::getCustomFormField($_GET['field'], ARRAY_A);
            $data = $data[0];
            //error_log(print_r($data, true));
            include(CB_PLUGIN_PATH . 'views/new_edit_field.php');
        }
        else
        {
            $tablenames = CustomBookings::$tablenames;
            $data = $_POST;
            unset($data['submit']);

            $validation = self::validate_custom_field();
            if($validation === true)
            {
                //validated, even!
                $where = array('field_slug' => $data['field_slug']);
                unset($data['field_type'], $data['field_options']);

                if(!isset($data['field_required']))
                    $data['field_required'] = 0;
                if(!isset($data['field_active']))
                    $data['field_active'] = 0;

                if($wpdb->update($wpdb->prefix.$tablenames['fields'], $data, $where) === false)
                {
                    CustomBookings::show_message('Adding went wrong!', true);
                    error_log('last query after updating field = '.$wpdb->last_query);
                    include(CB_PLUGIN_PATH . 'views/new_edit_field.php');                                                
                }
                else
                {
                    $_SESSION['cb_custom_fields_last_updated'] = time();
                    update_option('cb_custom_fields_last_updated', $_SESSION['cb_custom_fields_last_updated']);
                    CustomBookings::show_message('Field updated!');
                    include(CB_PLUGIN_PATH . 'views/form_editor.php');
                }
            }
            else
            {
                $errors = $validation;
                include(CB_PLUGIN_PATH . 'views/new_edit_field.php');
            }            
        }

    }

    static function new_field()
    {
        if(isset($_POST['submit']))
        {
            global $wpdb;
            //save the new field to database

            $tablenames = CustomBookings::$tablenames;
            $data = $_POST;
            $data['field_slug'] = sanitize_title_with_dashes($data['field_label']);
            $data['field_options'] = self::process_field_options($data['field_options']);
            unset($data['submit']);

            $result = $wpdb->insert($wpdb->prefix.$tablenames['fields'], $data);

            if($result !== false)
            {
                CustomBookings::show_message('New Field added!');
                include(CB_PLUGIN_PATH . 'views/form_editor.php');
                $_SESSION['cb_custom_fields_last_updated'] = time();
                update_option('cb_custom_fields_last_updated', $_SESSION['cb_custom_fields_last_updated']);
            }
            else
            {
                CustomBookings::show_message('Adding went wrong!', true);
                include(CB_PLUGIN_PATH . 'views/new_edit_field.php');                            
            }
        }
        else
        {
            include(CB_PLUGIN_PATH . 'views/new_edit_field.php');            
        }
    }

    static function process_field_options($raw_options)
    {
        return serialize(preg_split('/\n|\r/', $raw_options, -1, PREG_SPLIT_NO_EMPTY));
    }

    static function validate_custom_field($data = false)
    {
        if($data === false)
            $data = $_POST;

        $errors = array();

        if(!isset($data['field_label']) || trim($data['field_label']) == '')
        {
            $errors[] = __('Field Label can\'t be empty!');
        }
        if( ($data['type'] == 'select' || $data['type'] == 'checkbox') && (!isset($data['field_options']) || trim($data['field_options']) == ''))
        {
            $errors[] = __('With a dropdown or checkbox element the field options can\'t be 0');
        }

        return count($errors) > 1 ? $errors : true;
    }
}

function display_bookings_table($atts, $content = NULL)
{
    extract(shortcode_atts(array('event' => 1, 'columns' => 'booking_date,first_name,booking_comment'), $atts));

    if(!class_exists('EM_Bookings_Table'))
        include_once(WP_PLUGIN_DIR . '/events-manager/classes/em-bookings-table.php');

    if(!class_exists('CustomBookingsBookingsTable'))
        include_once(CB_PLUGIN_PATH . 'classes/custom-bookings-bookings-table.class.php');

    $EM_Event = new EM_Event($event);
    $bookings_table = new CustomBookingsBookingsTable;
    $shortcode_columns = explode(',', $columns);
    //$custom_fields = CustomBookingsForm::getCustomFormFields();
    $filtered_cols = array();

    foreach($shortcode_columns as $col)
    {
        if(isset($bookings_table->cols_template[$col]))
        {
            $filtered_cols[$col] = $bookings_table->cols_template[$col];
            $colslugs[] = $col;
        }
    }

    //error_log('display bookings_table :'.print_r($filtered_cols, true));

    $bookings_table->cols = $colslugs;
    $bookings_table->cols_template = $filtered_cols;
    $bookings_table->output();
}

function write_queries()
{
    global $wpdb;
    error_log('Queries executed by wpdb: '.print_r($wpdb->queries, true));
}