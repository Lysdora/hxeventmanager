<div class="wrap">
<?php screen_icon() ?>
<h2>Add/Edit New Custom Form Field</h2>
<?php

if(isset($errors) && count($errors > 0)):
    foreach($errors as $message):
        CustomBookings::show_message($message, true);
    endforeach;
endif
?>
<form action="" method="post">
<table class="form-table">
    <tr valign="top">
        <th scope="row"><label for="field_label"><?php _e('Field Label') ?></label></th>
        <td><input name="field_label" type="text" id="field_label" class="regular-text" value="<?php echo isset($data['field_label']) ? $data['field_label'] : '' ?>" /></td>
    </tr>
    <tr valign="top">
        <th scope="row"><label for="field_description">Field Description</label></th>
        <td><input name="field_description" type="text" id="field_description" class="regular-text" value="<?php echo isset($data['field_description']) ? $data['field_description'] : '' ?>" />
        </td>
    </tr>
<tr>
    <th scope="row"><label for="field_type">Type of Field</label></th>
    <td>
        <select id="field_type" name="field_type" <?php if($_GET['action'] == 'edit') echo "disabled" ?>>
        <option value="text" <?php echo isset($data['field_type']) && $data['field_type'] == 'text' ? 'selected' : '' ?>>Text</option>
        <option value="checkbox" <?php echo isset($data['field_type']) && $data['field_type'] == 'checkbox' ? 'selected' : '' ?>>Checkbox</option>
        <option value="select" <?php echo isset($data['field_type']) && $data['field_type'] == 'select' ? 'selected' : '' ?>>Dropdown</option>
        <option value="textarea" <?php echo isset($data['field_type']) && $data['field_type'] == 'textarea' ? 'selected' : '' ?>>Big text area</option>
        <option value="captcha" <?php echo isset($data['field_type']) && $data['field_type'] == 'captcha' ? 'selected' : '' ?>>CAPTCHA</option>
        </select>
    </td>
</tr>
    <tr valign="top" id="field_options_row" style="display: none;">
        <th scope="row"><label for="field_options">Field Options</label></th>
        <td><textarea name="field_options" cols="39" rows="5" id="field_options"><?php echo isset($data['field_options']) ? $data['field_options'] : '' ?></textarea>
        <p class="description">Place every option on a separate line.</p></td>
    </tr>
    <tr valign="top" id="field_checkbox_label_row" style="display: none;">
        <th scope="row"><label for="field_checkbox_label"><?php _e('Checkbox Label') ?></label></th>
        <td>
            <input name="field_checkbox_label" type="text" id="field_checkbox_label" class="regular-text" value="<?php echo isset($data['field_checkbox_label']) ? $data['field_checkbox_label'] : '' ?>">
            <p class="description">The text that is displayed next to the checkbox (example: Yes).</p>
        </td>
    </tr>
    <tr valign="top">
    <th scope="row">Required?</th>
    <td>
        <fieldset>
            <legend class="screen-reader-text"><span>Required?</span></legend>
            <label for="field_required">
                <input name="field_required" type="checkbox" id="field_required" value="1" <?php echo isset($data['field_required']) && $data['field_required'] == '1' ? 'checked' : '' ?>> Yes
            </label>
            <p class="description">Field must be filled in / a choice has to be made</p>
        </fieldset>
    </td>
</tr>
    <tr valign="top">
    <th scope="row">Active?</th>
    <td>
        <fieldset>
            <legend class="screen-reader-text"><span>Active?</span></legend>
            <label for="field_active">
                <input name="field_active" type="checkbox" id="field_active" value="1" <?php echo isset($data['field_active']) && $data['field_active'] == '1' ? 'checked' : '' ?>> Yes
            </label>
        </fieldset>
    </td>
</tr>
</table>
<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save New Field"></p>
<input type="hidden" name="field_slug" value="<?php echo isset($data['field_slug']) ? $data['field_slug'] : '' ?>" />
</form>
</div>
<script type="text/javascript">
jQuery(function($)
{
    $('#field_type').on('change', function(event)
    {
        switch($('#field_type option:selected').val())
        {
            case 'select':
            $('#field_options_row').show();
            $('#field_checkbox_label_row').hide();
            break;

            case 'checkbox':
            $('#field_options_row').hide().find('textarea').val('');
            $('#field_checkbox_label_row').show();
            break;

            default:
            $('#field_options_row').hide();
            $('#field_checkbox_label_row').hide();
            break;
        }
    });
});
</script>