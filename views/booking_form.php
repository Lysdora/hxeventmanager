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
    <?php else: $wp_user = wp_get_current_user(); ?>
        <p><?php _e(sprintf('Logged in as <strong><a href="%s">%s</a></strong>. <a href="%s">Log out?</a>', admin_url('profile.php'), $wp_user->data->display_name, wp_logout_url())); ?></p>
    <?php endif; ?>
    <?php foreach($custom_fields as $field): $field->field_options = unserialize($field->field_options) ?>
     <p>
            <label for='cb[<?php echo $field->field_slug ?>]'><?php echo stripslashes($field->field_label) ?></label>

            <?php include(CB_PLUGIN_PATH . 'views/conditional-input-fields.php'); ?>
        
        <?php if(!empty($field->field_description)): ?> <span class="description" style="color: #666; font-style: italic; font-size: 0.8em; margin: 0; display: block;"><?php echo stripslashes($field->field_description) ?><?php endif; ?></span>
    </p>
    <?php endforeach; ?>
    <?php do_action('em_register_form'); //careful if making an add-on, this will only be used if you're not using custom booking forms ?>                  
    <p>
        <label for='booking_comment'><?php _e('Comment', 'dbem') ?></label>
        <textarea name='booking_comment' rows="2" cols="20"><?php echo !empty($_REQUEST['booking_comment']) ? esc_attr($_REQUEST['booking_comment']):'' ?></textarea>
    </p>