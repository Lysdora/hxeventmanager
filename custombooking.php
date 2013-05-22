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
        $cols['user_cs_name'] = __('CS Nick');
        $cols['user_gender'] = __('Gender');

        return $cols;
    }

    function bookings_table_rows_col($val, $col, $EM_Booking, $EM_Bookings_Table, $csv)
    {
        //error_log(print_r($EM_Booking->booking_meta, true));
        if(array_key_exists($col, $EM_Booking->booking_meta['custom_form_fields']))
            return $EM_Booking->booking_meta['custom_form_fields'][$col]['value'];
        else
            return __('Missing value for column '.$col);
    }

    function bookings_single_custom($EM_Booking)
    {
    ?>
    <script type="text/javascript">
        jQuery(document).ready( function($){
            $('#em-booking-submit-modify').click(function(){
                $('#em-booking-custom-user-gender-details').hide();
                $('#em-booking-custom-user-gender-edit').show();
                $('#em-booking-custom-user-cs-name-details').hide();
                $('#em-booking-custom-user-cs-name-edit').show();
            });
            $('#em-booking-submit-cancel').click(function(){
                $('#em-booking-custom-user-gender-details').show();
                $('#em-booking-custom-user-gender-edit').hide();
                $('#em-booking-custom-user-cs-name-details').show();
                $('#em-booking-custom-user-cs-name-edit').hide();
            });
        });
    </script>               
    <hr />
        <h4><?php _e('Custom Fields') ?></h4>
            <table>
                <tr>
                    <td><strong><?php _e('Gender') ?> :</strong></td>
                    <td id="em-booking-custom-user-gender-details"><?php echo $EM_Booking->booking_meta['custom_form_fields']['user_gender']['value'] ?></td>
                    <td id="em-booking-custom-user-gender-edit" style="display: none;">
                        <select name="custom[user_gender][value]" id="user_gender" class="">
                            <option value="M" <?php if(!empty($_REQUEST['custom']['user_gender']['value']) && $_REQUEST['custom']['user_gender']['value'] == 'M') echo 'selected'; elseif($EM_Booking->booking_meta['custom_form_fields']['user_gender']['value'] == 'M') echo 'selected'; ?>><?php _e('Male') ?></option>
                            <option value="F" <?php if(!empty($_REQUEST['custom']['user_gender']['value']) && $_REQUEST['custom']['user_gender']['value'] == 'F') echo 'selected'; elseif($EM_Booking->booking_meta['custom_form_fields']['user_gender']['value'] == 'F') echo 'selected'; ?>><?php _e('Female') ?></option>
                        </select>
                        <input type="hidden" name="custom[user_gender][type]" value="select" />
                        <input type="hidden" name="custom[user_gender][options]" value="M,F" />
                        <input type="hidden" name="custom[user_gender][label]" value="Gender" />
                    </td>
                </tr>
                <tr>
                    <td><strong>CS Nick :</strong></th>
                    <td id="em-booking-custom-user-cs-name-details"><?php echo $EM_Booking->booking_meta['custom_form_fields']['user_cs_name']['value'] ?></td>
                    <td id="em-booking-custom-user-cs-name-edit" style="display: none;">
                        <input type="text" name="custom[user_cs_name][value]" id="user_cs_name" class="input" 
                        value="<?php 
                        if(!empty($_REQUEST['custom']['user_cs_name'])) 
                            echo esc_attr($_REQUEST['custom']['user_cs_name']); 
                        else 
                            echo $EM_Booking->booking_meta['custom_form_fields']['user_cs_name']['value']; ?>"  />
                        <input type="hidden" name="custom[user_cs_name][type]" value="text" />
                    </td>
                </tr>
            </table>
    <?php
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
}

class CustomBookingsForm
{

    function custom_form($EM_Event)
    {
        //error_log(print_r($EM_Event, true));
    ?>
    <?php if( !is_user_logged_in() && apply_filters('em_booking_form_show_register_form',true) ): ?>
        <?php //User can book an event without registering, a username will be created for them based on their email and a random password will be created. ?>
        <input type="hidden" name="register_user" value="1" />
        <p>
            <label for='user_name'><?php _e('Name','dbem') ?></label>
            <input type="text" name="user_name" id="user_name" class="input" value="<?php if(!empty($_REQUEST['user_name'])) echo esc_attr($_REQUEST['user_name']); ?>" />
        </p>
        <p>
            <label for='dbem_phone'><?php _e('Phone','dbem') ?></label>
            <input type="text" name="dbem_phone" id="dbem_phone" class="input" value="<?php if(!empty($_REQUEST['dbem_phone'])) echo esc_attr($_REQUEST['dbem_phone']); ?>" />
        </p>
        <p>
            <label for='user_email'><?php _e('E-mail','dbem') ?></label> 
            <input type="text" name="user_email" id="user_email" class="input" value="<?php if(!empty($_REQUEST['user_email'])) echo esc_attr($_REQUEST['user_email']); ?>"  />
        </p>
     <p>
            <label for='custom[user_cs_name]'><?php _e('CouchSurfing Username','dbem') ?></label> 
            <input type="text" name="custom[user_cs_name][value]" id="user_cs_name" class="input" value="<?php if(!empty($_REQUEST['custom']['user_cs_name'])) echo esc_attr($_REQUEST['custom']['user_cs_name']); ?>"  />
            <input type="hidden" name="custom[user_cs_name][type]" value="text" />
    </p>
     <p>
            <label for='custom[user_gender]'><?php _e('Gender','dbem') ?></label>
            <select name="custom[user_gender][value]" id="user_gender" class="">
                <option value="M" <?php if(!empty($_REQUEST['custom']['user_gender']['value']) && $_REQUEST['custom']['user_gender']['value'] == 'M') echo 'selected'; ?>><?php _e('Male') ?></option>
                <option value="F" <?php if(!empty($_REQUEST['custom']['user_gender']['value']) && $_REQUEST['custom']['user_gender']['value'] == 'F') echo 'selected'; ?>><?php _e('Female') ?></option>
            </select>
            <input type="hidden" name="custom[user_gender][type]" value="select" />
            <input type="hidden" name="custom[user_gender][options]" value="M,F" />
            <input type="hidden" name="custom[user_gender][label]" value="Gender" />
    </p>
           <?php do_action('em_register_form'); //careful if making an add-on, this will only be used if you're not using custom booking forms ?>                  
    <?php endif; ?>     
    <p>
        <label for='booking_comment'><?php _e('Comment', 'dbem') ?></label>
        <textarea name='booking_comment' rows="2" cols="20"><?php echo !empty($_REQUEST['booking_comment']) ? esc_attr($_REQUEST['booking_comment']):'' ?></textarea>
    </p>
    <?php
    }

    function pre_save_booking($EM_Booking)
    {
        foreach($_REQUEST['custom'] as $custom_field_name => $custom_field_data)
        {
            $EM_Booking->booking_meta['custom_form_fields'][$custom_field_name] = array('type' => $custom_field_data['type'], 'value' => $custom_field_data['value'], 'label' => $custom_field_data['label']);
            isset($custom_field_data['options']) ? $EM_Booking->booking_meta['custom_form_fields'][$custom_field_name]['options'] = explode(',', $custom_field_data['options']) : NULL;
        }
    }

    function validate_booking($result, $EM_Booking)
    {
        if(empty($_REQUEST['custom']['user_cs_name']['value']))
        {
            $EM_Booking->add_error(__('No CS Nickname given'));
            return false;
        }

        return $result;
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

    }

    static function update_field()
    {

    }

    static function new_field()
    {
        if(isset($_POST['submit']))
        {
            global $wpdb;
            //save the new field to database

            $tablenames = CustomBookings::$tablenames;
            $data = $_POST;
            $data['field_ID'] = 'NULL';
            $data['field_slug'] = sanitize_title_with_dashes($data['field_label']);
            $data['field_options'] = self::process_field_options($data['field_options']);
            unset($data['submit']);

            $result = $wpdb->insert($wpdb->prefix.$tablenames['fields'], $data);

            if($result !== false)
            {
                CustomBookings::show_message('New Field added!');
                include(CB_PLUGIN_PATH . 'views/form_editor.php');
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
}