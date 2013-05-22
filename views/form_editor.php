<div class="wrap">
<?php screen_icon() ?>
<h2>Custom Form Fields Editor <a href="?post_type=event&page=<?php echo $_REQUEST['page'] ?>&action=new" class="add-new-h2"><?php _e('Add New Field') ?></a></h2>
<?php
    $fieldstable = new CustomBookingsFieldsTable;
    $fieldstable->prepare_items();
    $fieldstable->display();
?>
</div>
