    <script type="text/javascript">
        jQuery(document).ready( function($){
            $('#em-booking-submit-modify').click(function(){
                <?php foreach($custom_fields as $field): ?>
                $('#cb-<?php echo $field->field_slug ?>-details').hide();
                $('#cb-<?php echo $field->field_slug ?>-edit').show();
                <?php endforeach; ?>
            });
            $('#em-booking-submit-cancel').click(function(){
                <?php foreach($custom_fields as $field): ?>
                $('#cb-<?php echo $field->field_slug ?>-details').show();
                $('#cb-<?php echo $field->field_slug ?>-edit').hide();
                <?php endforeach; ?>
            });
        });
    </script>               
    <hr />
        <h4><?php _e('Custom Fields') ?></h4>
            <table class="form-table">
                <?php foreach($custom_fields as $field): ?>
                <tr valign="top">
                    <th scope="row"><?php echo $field->field_label ?> :</th>
                    <td id="cb-<?php echo $field->field_slug ?>-details"><?php echo CustomBookings::process_field_data_and_return($field) ?></td>
                    <td id="cb-<?php echo $field->field_slug ?>-edit" style="display: none;">
                    <?php include(CB_PLUGIN_PATH . 'views/conditional-input-fields.php'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
   