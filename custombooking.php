<?php 

/*
Plugin Name: Custom Booking for Events Manager
Plugin URI: http://welcometofryslan.nl/
Version: 0.1 beta
Author: Coen de Jong
Author URI: http://shifthappens.nl
Description: Plugin to extend the functionality of the Events Manager plugin with custom Booking form fields. 
Text Domain: cbfem
Domain Path: /languages
License: GPL2
*/

/*  Copyright 2013  Coen de Jong  (email : co.dejong@gmail.com )

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
//define('HXEVENTS_DB_VERSION', 4); //this should be set to the newest db version

if(!isset($_COOKIE['PHPSESSID']))
    session_start();

add_action('em_booking_form_custom', array('CustomBookingsForm', 'custom_form'));
add_action('em_booking_save_pre', array('CustomBookingsForm', 'pre_save_booking'));
add_filter('em_booking_validate', array('CustomBookingsForm', 'validate_booking'), 10, 2);
add_filter('em_bookings_table_cols_template', array('CustomBookings', 'bookings_table_cols_template'), 10, 2);
add_filter('em_bookings_table_rows_col', array('CustomBookings', 'bookings_table_rows_col'), 10, 5);
add_action('em_bookings_single_custom', array('CustomBookings', 'bookings_single_custom'), 10, 1);
add_filter('em_create_events_submenu', array('CustomBookings', 'create_events_submenu'));
add_action('admin_notices', array('CustomBookings', 'show_message'), 10, 2);

if(!class_exists('CustomBookingsFieldsTable'))
    include_once(CB_PLUGIN_PATH . 'custombookingsfieldstable.class.php');

class CustomBookings
{
    static $tablenames = array(
        'fields'        => 'cb_fields',
        'field_data'    => 'cb_field_data'
    );

    function __construct()
    {

    }

    function create_events_submenu($plugin_pages)
    {
        $plugin_pages['custom_form_fields'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Custom Form Fields'),__('Custom Form Fields'), 'manage_bookings', "events-manager-custom-form-fields", array('CustomBookingsFormEditor', 'form_editor_overview'));

        return $plugin_pages;
    }

    function bookings_table_cols_template($cols, $EM_Bookings_Table)
    {
        $custom_fields = CustomBookingsForm::getCustomFormFields();

        foreach($custom_fields as $field)
        {
            $cols[$field->field_slug] = $field->field_label;            
        }

        return $cols;
    }

    function bookings_table_rows_col($val, $col, $EM_Booking, $EM_Bookings_Table, $csv)
    {
        $cb_custom_field_values = CustomBookingsForm::getCustomFormValues($EM_Booking->event_id, $EM_Booking->person_id);

        foreach($cb_custom_field_values as $row)
        {
            if($row->field_slug == $col)
                return self::process_field_data_and_return($row);
        }
            return print($col);
    }

    function bookings_single_custom($EM_Booking)
    {
        $custom_fields = CustomBookingsForm::getCustomFormValues($EM_Booking->event_id, $EM_Booking->person_id);

        include(CB_PLUGIN_PATH . 'views/booking_edit.php');
    }

    function show_message($message, $error = false)
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

    function process_field_data_and_return($row)
    {
        $options = unserialize($row->field_options);

        error_log('row = '.print_r($row, true));

        if(is_array($options) && count($options) > 0 && array_key_exists($row->field_data, $options))
            return $options[$row->field_data];
        elseif($row->field_type == 'checkbox' && $row->field_data == '1')
            return $row->field_checkbox_label;
        elseif($row->field_type == 'checkbox' && ($row->field_data == '0' || $row->field_data == NULL))
            return '--';
        else
            return $row->field_data;
    }
}

class CustomBookingsForm
{

    function custom_form($EM_Event)
    {
        //error_log(print_r($EM_Event, true));
        $custom_fields = self::getCustomFormFields();
        include(CB_PLUGIN_PATH . 'views/booking_form.php');
    }

    function pre_save_booking($EM_Booking)
    {
        global $wpdb;

        $data['field_data_ID'] = 'NULL';
        $data['user_ID'] = $EM_Booking->person_id;
        $data['event_ID'] = $EM_Booking->event_id;

        foreach($_REQUEST['cb'] as $custom_field_slug => $custom_field_data)
        {
            $data['field_data'] = $custom_field_data;

            //check if we are creating a new entry or updating an existing one
            if($_REQUEST['page'] == 'events-manager-bookings')
            {
                //it's an edit
                $where = array('user_ID' => $EM_Booking->person_id, 'event_ID' => $EM_Booking->event_id, 'field_slug' => $custom_field_slug);
                $data = array('field_data' => $custom_field_data);

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

        foreach($_REQUEST['cb_checkbox'] as $custom_field_slug => $custom_field_data)
        {
            if(!isset($_REQUEST['cb'][$custom_field_slug]))
            {
                $data['field_data'] = 0;
                //check if we are creating a new entry or updating an existing one
                if($_REQUEST['page'] == 'events-manager-bookings')
                {
                    //it's an edit
                    $where = array('user_ID' => $EM_Booking->person_id, 'event_ID' => $EM_Booking->event_id, 'field_slug' => $custom_field_slug);

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

    function validate_booking($result, $EM_Booking)
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

    function getCustomFormFields()
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

    function getCustomFormField($field_slug, $output_type = OBJECT)
    {
        global $wpdb;
        $tablenames = CustomBookings::$tablenames;

        $query = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.$tablenames['fields'].' WHERE field_slug = %s', $field_slug);
        $results = $wpdb->get_results($query, $output_type);

        return $results;
    }

    function getCustomFormValues($event_ID, $user_ID)
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
            error_log(print_r($data, true));
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