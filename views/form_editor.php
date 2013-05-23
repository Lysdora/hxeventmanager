<div class="wrap">
<?php screen_icon() ?>
<h2>Custom Form Fields Editor <a href="?post_type=event&page=<?php echo $_REQUEST['page'] ?>&action=new" class="add-new-h2"><?php _e('Add New Field') ?></a></h2>
<?php
    $fieldstable = new CustomBookingsFieldsTable;
    $fieldstable->prepare_items();
    $fieldstable->display();
?>
</div>
<script type="text/javascript">
jQuery(function($)
{
    $('.button_delete').on('click', function(event)
    {
        return confirm('<?php  _e('Are you sure you want to delete this field? All data associated will also be erased!') ?>');
    });
})
</script>