<?php 
if(isset($field->field_data))
    $field->processed_value = CustomBookings::process_field_data_and_return($field);
if(!is_array($field->field_options))
    $field->field_options = unserialize($field->field_options);
?>
                    <?php if($field->field_type == 'text'): ?>
                    <input type="text" name="cb[<?php echo $field->field_slug ?>]" id="cb-form-<?php echo $field->field_slug ?>" class="input" value="<?php if(!empty($_REQUEST['cb'][$field->field_slug])) echo esc_attr($_REQUEST['custom'][$field->field_slug]); elseif(isset($field->processed_value)) echo $field->processed_value ?>"  />
                
                <?php elseif($field->field_type == 'select'): ?>
                    <select name="cb[<?php echo $field->field_slug ?>]" id="cb-form-<?php echo $field->field_slug ?>">
                        <?php foreach($field->field_options as $option_value => $option_label): ?>
                        <option value="<?php echo $option_value ?>" <?php if(!empty($_REQUEST['cb'][$field->field_slug]) && $_REQUEST['cb'][$field->field_slug] == $option_value) echo 'selected'; elseif(isset($field->processed_value) && $field->processed_value == $option_value) echo 'selected' ?>><?php echo $option_label ?></option>
                        <?php endforeach; ?>
                    </select>
                
                <?php elseif($field->field_type == 'checkbox'): ?>
                    <input type="checkbox" name="cb[<?php echo $field->field_slug ?>]" id="cb-form-<?php echo $field->field_slug ?>" class="input" value="1" <?php if(!empty($_REQUEST['cb'][$field->field_slug]) && $_REQUEST['cb'][$field->field_slug] == '1') echo 'checked'; elseif(isset($field->processed_value) && $field->processed_value == $field->field_checkbox_label) echo 'checked' ?> style="width: auto;" /> <span><?php echo $field->field_checkbox_label ?></span>
                    <input type="hidden" name="cb_checkbox[<?php echo $field->field_slug ?>]" id="cb-form-checkbox-hidden-<?php echo $field->field_slug ?>" value="<?php echo isset($field->processed_value) ? $field->field_data : '0' ?>" />

                <?php elseif($field->field_type == 'textarea'): ?>
                    <textarea name="cb[<?php echo $field->field_slug ?>]" id="cb-form-<?php echo $field->field_slug ?>"><?php if(!empty($_REQUEST['cb'][$field->field_slug])) echo esc_attr($_REQUEST['custom'][$field->field_slug]); elseif(isset($field->processed_value)) echo $field->processed_value ?></textarea>
                
                <?php endif; ?>
