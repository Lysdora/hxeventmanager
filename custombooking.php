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

define('CBFEM_PLUGIN_PATH', plugin_dir_path(__FILE__));
//define('HXEVENTS_DB_VERSION', 4); //this should be set to the newest db version

add_action('em_booking_form_custom', 'hx_custom_form');
add_action('em_booking_save_pre', 'hx_pre_save_booking');
add_filter('em_booking_validate', 'hx_validate_booking', 10, 2);
add_filter('em_bookings_table_cols_template', 'hx_bookings_table_cols_template', 10, 2);
add_filter('em_bookings_table_rows_col', 'hx_bookings_table_rows_col', 10, 5);
add_action('em_bookings_single_custom', 'hx_bookings_single_custom', 10, 1);
add_filter('em_create_events_submenu', 'hx_create_events_submenu');

function hx_create_events_submenu($plugin_pages)
{
    $plugin_pages['custom_form_fields'] = add_submenu_page('edit.php?post_type='.EM_POST_TYPE_EVENT, __('Custom Form Fields'),__('Custom Form Fields'), 'manage_bookings', "events-manager-custom-form-fields", 'hx_events_manage_custom_form_fields');

    return $plugin_pages;
}

function hx_custom_form($EM_Event)
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

function hx_pre_save_booking($EM_Booking)
{
    foreach($_REQUEST['custom'] as $custom_field_name => $custom_field_data)
    {
        $EM_Booking->booking_meta['custom_form_fields'][$custom_field_name] = array('type' => $custom_field_data['type'], 'value' => $custom_field_data['value'], 'label' => $custom_field_data['label']);
        isset($custom_field_data['options']) ? $EM_Booking->booking_meta['custom_form_fields'][$custom_field_name]['options'] = explode(',', $custom_field_data['options']) : NULL;
    }
}

function hx_validate_booking($result, $EM_Booking)
{
    if(empty($_REQUEST['custom']['user_cs_name']['value']))
    {
        $EM_Booking->add_error(__('No CS Nickname given'));
        return false;
    }

    return $result;
}

function hx_bookings_table_cols_template($cols, $EM_Bookings_Table)
{
    $cols['user_cs_name'] = __('CS Nick');
    $cols['user_gender'] = __('Gender');

    return $cols;
}

function hx_bookings_table_rows_col($val, $col, $EM_Booking, $EM_Bookings_Table, $csv)
{
    //error_log(print_r($EM_Booking->booking_meta, true));
    if(array_key_exists($col, $EM_Booking->booking_meta['custom_form_fields']))
        return $EM_Booking->booking_meta['custom_form_fields'][$col]['value'];
    else
        return __('Missing value for column '.$col);
}

function hx_bookings_single_custom($EM_Booking)
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
                        <option value="M" <?php if(!empty($_REQUEST['custom']['user_gender']['value']) && $_REQUEST['custom']['user_gender']['value'] == 'M') echo 'selected'; elseif($EM_Booking->booking_meta['custom_form_fields']['user_gender']['value'] == 'M') echo 'selected'; ?>><?php _e('Male') ?></option><!-- <?php echo $EM_Booking->booking_meta['custom_form_fields']['user_gender']['value'] ?> -->
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

function hx_events_manage_custom_form_fields()
{
?>
<div class="wrap">
<?php screen_icon() ?>
<h2>Custom Form Fields Editor</h2>
<div id="poststuff" class="metabox-holder">
            <!-- END OF SIDEBAR -->
            <div id="post-body">
                <div id="post-body-content">
                                        <div id="em-booking-form-editor" class="stuffbox">
                        <h3 id="booking-form">
                            Booking Form - General Information                      </h3>
                        <div class="inside">
                            <p>You can customize the fields shown in your booking forms below.  It is required that you have at least an email and name field so guest users can register.</p>
                            <p>Registration fields are only shown to guest visitors by default. You can choose to show these fields and make them editable by logged in user in your <a href="?post_type=event&amp;page=events-manager-options#bookings">PRO Booking Form Options</a>.                          </p><div>
                                <form method="get" action="#booking-form"> 
                                    Selected Booking Form :
                                    <select name="form_id" onchange="this.parentNode.submit()">
                                                                                <option value="1" selected="selected">Default</option>
                                                                            </select>
                                    <input type="hidden" name="post_type" value="event">
                                    <input type="hidden" name="page" value="events-manager-forms-editor">
                                </form>
                                 | 
                                <form method="post" action="/wtf2013/wp-admin/edit.php?post_type=event&amp;page=events-manager-forms-editor#booking-form" id="bookings-form-add">
                                    <input type="text" name="form_name">
                                    <input type="hidden" name="bookings_form_action" value="add">
                                    <input type="hidden" name="_wpnonce" value="1f31e46a36">
                                    <input type="submit" value="Add New »" class="button-secondary">
                                </form>
                                                                <br><em>This is the default bookings form and will be used for any event where you have not chosen a speficic form to use.</em>
                                                            </div>
                            <br>
                            <form method="post" action="/wtf2013/wp-admin/edit.php?post_type=event&amp;page=events-manager-forms-editor#booking-form" id="bookings-form-rename">
                                <span style="font-weight:bold;">You are now editing </span>
                                <input type="text" name="form_name" value="Default">
                                <input type="hidden" name="form_id" value="1">
                                <input type="hidden" name="bookings_form_action" value="rename">
                                <input type="hidden" name="_wpnonce" value="6aa842a007">
                                <input type="submit" value="Rename »" class="button-secondary">
                            </form>
                                                        <br><strong>Important:</strong> When editing this form, to make sure your old booking information is displayed, make sure new field ids correspond with the old ones.                           <br><br>
                                    <form method="post" action="" class="em-form-custom" id="em-form-em_bookings_form">
            <div>
                <div class="booking-custom-head">
                    <div class="bc-col-sort bc-col">&nbsp;</div>
                    <div class="bc-col-label bc-col">Label</div>
                    <div class="bc-col-id bc-col">Field ID<a title="DO NOT change these values if you want to keep your field settings associated with previous booking fields.">?</a></div>
                    <div class="bc-col-type bc-col">Type</div>
                    <div class="bc-col-required bc-col">Required</div>
                </div>
                <ul class="booking-custom-body ui-sortable">
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Name"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="user_name"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name" selected="selected">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Email"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="user_email"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email" selected="selected">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Address"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_address"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address" selected="selected">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="City"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_city"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city" selected="selected">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="State/County"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_state"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state" selected="selected">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Zip/Post Code"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_zip"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip" selected="selected">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Country"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_country"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country" selected="selected">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1" checked="checked">
                            <input type="hidden" name="required[]" value="1">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Phone"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_phone"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone" selected="selected">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1">
                            <input type="hidden" name="required[]" value="">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Fax"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="dbem_fax"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax" selected="selected">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1">
                            <input type="hidden" name="required[]" value="">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Comment"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="booking_comment"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option selected="selected">textarea</option>
                                    <option>checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1">
                            <input type="hidden" name="required[]" value="">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]"></textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        <li class="booking-custom-item">
                        <div class="bc-col-sort bc-col">&nbsp;</div>
                        <div class="bc-col-label bc-col"><input type="text" name="label[]" class="booking-form-custom-label" value="Need Host?"></div>
                        <div class="bc-col-id bc-col"><input type="text" name="fieldid[]" class="booking-form-custom-fieldid" value="need_host_f"></div>
                        <div class="bc-col-type bc-col">
                            <select name="type[]" class="booking-form-custom-type">
                                <option value="">Select Type</option>
                                                                <optgroup label="Customizable Fields">
                                    <option>text</option>
                                    <option>html</option>
                                    <option>checkbox</option>
                                    <option>textarea</option>
                                    <option selected="selected">checkboxes</option>
                                    <option>radio</option>
                                    <option>select</option>
                                    <option>multiselect</option>
                                    <option>country</option>
                                    <option>date</option>
                                    <option>time</option>
                                                                        <option>captcha</option>
                                                                    </optgroup>
                                                                                                <optgroup label="Registration Fields">
                                                                        <option value="name">Name</option>
                                                                        <option value="user_login">Username Login</option>
                                                                        <option value="user_email">E-mail (required)</option>
                                                                        <option value="user_password">Password</option>
                                                                        <option value="first_name">First Name</option>
                                                                        <option value="last_name">Last Name</option>
                                                                        <option value="user_url">Website</option>
                                                                        <option value="aim">AIM</option>
                                                                        <option value="yim">Yahoo IM</option>
                                                                        <option value="jabber">Jabber / Google Talk</option>
                                                                        <option value="about">Biographical Info</option>
                                                                    </optgroup>
                                                                        <optgroup label="Custom Registration Fields">
                                                                                        <option value="dbem_address">Address</option>
                                                                                        <option value="dbem_address_2">Address Line 2</option>
                                                                                        <option value="dbem_city">City</option>
                                                                                        <option value="dbem_state">State/County</option>
                                                                                        <option value="dbem_zip">Zip/Post Code</option>
                                                                                        <option value="dbem_country">Country</option>
                                                                                        <option value="dbem_phone">Phone</option>
                                                                                        <option value="dbem_fax">Fax</option>
                                                                                        <option value="dbem_company">Company</option>
                                                                                    </optgroup>
                                                                                                    </select>
                        </div>
                        <div class="bc-col-required bc-col">
                            <input type="checkbox" class="booking-form-custom-required" value="1">
                            <input type="hidden" name="required[]" value="">
                        </div>
                        <div class="bc-col-options bc-col"><a href="#" class="booking-form-custom-field-remove">remove</a> | <a href="#" class="booking-form-custom-field-options">options</a></div>
                        <div class="booking-custom-types">
                                                        <div class="bct-select bct-options" style="display: none;">
                                <!-- select,multiselect -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_select_values[]"></textarea>
                                        <em>Available options, one per line.</em>   
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Use Default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_select_default[]" value=""> 
                                        <em>If checked, the first value above will be used.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Default Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_default_text[]" value="Select ...">
                                        <em>Shown when a default value isn't selected, selected by default.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_select_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-html bct-options" style="display: none;">
                                <!-- html -->
                                <div class="bct-field">
                                    <div class="bct-label">Content</div>
                                    <div class="bct-input">
                                        <em>This html will be displayed on your form, the label for this field is used only for reference purposes.</em>
                                        <textarea name="options_html_content[]"></textarea>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-country bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_country_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-date bct-options" style="display: none;">
                                <!-- country -->
                                <div class="bct-field">
                                    <div class="bct-label">Date Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_date_range[]" value=""> 
                                        <em>If selected, this field will also have an end-date.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <p><strong>Error Messages</strong></p>
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect date format is used.                                           <br>Default: <code>Please use the date picker provided to select the appropriate date format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no end date is selected.                                          <br>Default: <code>You must provide an end date.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start date required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_date_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a date-range and no start date is selected.                                            <br>Default: <code>You must provide a start date.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>  
                            <div class="bct-time bct-options" style="display: none;">
                                <div class="bct-field">
                                    <div class="bct-label">Time Range?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_time_range[]" value=""> 
                                        <em>If selected, this field will also have an end-time.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Separator</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_range_seperator[]" value="">
                                        <em>This text will appear between the two date fields if this is a date range.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <p><strong>Error Messages</strong></p>
                                <div class="bct-field">
                                    <div class="bct-label">Field Required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error[]" value="">
                                        <em>
                                            This error will show this field is required and no value is entered.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Incorrect Formatting</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_format[]" value="">
                                        <em>
                                            This error will show if an incorrect time format is used.                                           <br>Default: <code>Please use the time picker provided to select the appropriate time format.</code>                                        </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">End time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_end[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no end time is selected.                                          <br>Default: <code>You must provide an end time.</code>                                     </em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Start time required</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_time_error_start[]" value="">
                                        <em>
                                            This error will show if the field is a time-range and no start time is selected.                                            <br>Default: <code>You must provide a start time.</code>                                        </em>
                                    </div>
                                </div>
                                                            </div>              
                            <div class="bct-selection bct-options" style="display: none;">
                                <!-- checkboxes,radio -->
                                <div class="bct-field">
                                    <div class="bct-label">Options</div>
                                    <div class="bct-input">
                                        <textarea name="options_selection_values[]">Yes
No</textarea>
                                        <em>Available options, one per line.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_tip[]" value="Do you need a host to sleep at during the event?">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_selection_error[]" value="">
                                        <em>
                                            This error will show if a value isn't chosen.                                           <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-checkbox bct-options" style="display: none;">
                                <!-- checkbox -->
                                <div class="bct-field">
                                    <div class="bct-label">Checked by default?</div>
                                    <div class="bct-input">
                                        <input type="checkbox" value="1">
                                        <input type="hidden" name="options_checkbox_checked[]" value=""> 
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_checkbox_error[]" value="">
                                        <em>
                                            This error will show if this box is not checked.                                            <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>
                            <div class="bct-text bct-options" style="display: none;">
                                <!-- text,textarea,email,name -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_text_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                                                            </div>  
                                                                                    <div class="bct-registration bct-options" style="display: none;">
                                <!-- registration -->
                                <div class="bct-field">
                                    <div class="bct-label">Tip Text</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_tip[]" value="">
                                        <em>Will appear next to your field label as a question mark with a popup tip bubble.</em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Regex</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_regex[]" value="">
                                        <em>By adding a regex expression, you can limit the possible values a user can input, for example the following only allows numbers: <code>^[0-9]+$</code></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_reg_error[]" value="">
                                        <em>
                                            If the regex above does not match this error will be displayed.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                                                    <div class="bct-captcha bct-options" style="display: none;">
                                <!-- captcha -->
                                                                <div class="bct-field">
                                    <div class="bct-label">Public Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_pub[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Private Key</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_key_priv[]" value="">
                                        <em>Required, get your keys <a href="https://www.google.com/recaptcha/admin/create?domains=try.eventsmanagerpro.com&amp;app=wordpress">here</a></em>
                                    </div>
                                </div>
                                <div class="bct-field">
                                    <div class="bct-label">Error Message</div>
                                    <div class="bct-input">
                                        <input type="text" name="options_captcha_error[]" value="">
                                        <em>
                                            This error will show if the captcha is not correct.                                         <br>Default: <code>Please fill in the field: [FIELD]</code>                                     </em>
                                    </div>
                                </div>
                            </div>
                                                    </div>
                        <br style="clear:both">
                    </li>
                                        
                                    </ul>
            </div>
            <p>
                <input type="hidden" name="_wpnonce" value="a7f4a25127">
                <input type="hidden" name="form_action" value="form_fields">
                <input type="hidden" name="form_name" value="em_bookings_form">
                <input type="button" value="Add booking field" class="booking-form-custom-field-add button-secondary">
                <input type="submit" name="events_update" value="Save Form »" class="button-primary">
            </p>
        </form> 
        <script type="text/javascript">
            jQuery(document).ready( function($){
                $('.bct-options').hide();
                //Booking Form
                var booking_template = $('#em-form-em_bookings_form #booking-custom-item-template').detach();
                $('#em-form-em_bookings_form').delegate('.booking-form-custom-field-remove', 'click', function(e){
                    e.preventDefault();
                    $(this).parents('.booking-custom-item').remove();
                });
                $('#em-form-em_bookings_form .booking-form-custom-field-add').click(function(e){
                    e.preventDefault();
                    booking_template.clone().appendTo($(this).parents('.em-form-custom').find('ul.booking-custom-body').first());
                });
                $('#em-form-em_bookings_form').delegate('.booking-form-custom-field-options', 'click', function(e){
                    e.preventDefault();
                    if( $(this).attr('rel') != '1' ){
                        $(this).parents('.em-form-custom').find('.booking-form-custom-field-options').text('options').attr('rel','0')
                        $(this).parents('.booking-custom-item').find('.booking-form-custom-type').trigger('change');
                    }else{
                        $(this).text('options').parents('.booking-custom-item').find('.bct-options').slideUp();
                        $(this).attr('rel','0');
                    }
                });
                //specifics
                $('#em-form-em_bookings_form').delegate('.booking-form-custom-label', 'change', function(e){
                    var parent_div =  $(this).parents('.booking-custom-item').first();
                    var field_id = parent_div.find('input.booking-form-custom-fieldid').first();
                    if( field_id.val() == '' ){
                        field_id.val(escape($(this).val()).replace(/%[0-9]+/g,'_').toLowerCase());
                    }
                });
                $('#em-form-em_bookings_form').delegate('input[type="checkbox"]', 'change', function(){
                    var checkbox = $(this);
                    if( checkbox.next().attr('type') == 'hidden' ){
                        if( checkbox.is(':checked') ){
                            checkbox.next().val(1);
                        }else{
                            checkbox.next().val(0);
                        }
                    }
                });
                $('#em-form-em_bookings_form').delegate('.booking-form-custom-type', 'change', function(){
                    $('.bct-options').slideUp();
                    var type_keys = {
                        select : ['select','multiselect'],
                        country : ['country'],
                        date : ['date'],
                        time : ['time'],
                        html : ['html'],
                        selection : ['checkboxes','radio'],
                        checkbox : ['checkbox'],
                        text : ['text','textarea','email'],
                        registration : ['name', 'user_login', 'user_email', 'user_password', 'first_name', 'last_name', 'user_url', 'aim', 'yim', 'jabber', 'about', 'dbem_address', 'dbem_address_2', 'dbem_city', 'dbem_state', 'dbem_zip', 'dbem_country', 'dbem_phone', 'dbem_fax', 'dbem_company'],
                        captcha : ['captcha']                           
                    }
                    var select_box = $(this);
                    var selected_value = select_box.val();
                    $.each(type_keys, function(option,types){
                        if( $.inArray(selected_value,types) > -1 ){
                            //get parent div
                            parent_div =  select_box.parents('.booking-custom-item').first();
                            //slide the right divs in/out
                            parent_div.find('.bct-'+option).slideDown();
                            parent_div.find('.booking-form-custom-field-options').text('hide options').attr('rel','1');
                        }
                    });
                });
                $('#em-form-em_bookings_form').delegate('.bc-link-up, #em-form-em_bookings_form .bc-link-down', 'click', function(e){
                    e.preventDefault();
                    item = $(this).parents('.booking-custom-item').first();
                    if( $(this).hasClass('bc-link-up') ){
                        if(item.prev().length > 0){
                            item.prev().before(item);
                        }
                    }else{
                        if( item.next().length > 0 ){
                            item.next().after(item);
                        }
                    }
                });
                $('#em-form-em_bookings_form').delegate('.bc-col-sort', 'mousedown', function(){
                    parent_div =  $(this).parents('.booking-custom-item').first();
                    parent_div.find('.bct-options').hide();
                    parent_div.find('.booking-form-custom-field-options').text('options').attr('rel','0');
                });
                $("#em-form-em_bookings_form .booking-custom-body" ).sortable({
                    placeholder: "bc-highlight",
                    handle:'.bc-col-sort'
                });
            });
        </script>
                                </div>
                    </div>
                </div>
            </div>
        </div>
</div>
<?php
}