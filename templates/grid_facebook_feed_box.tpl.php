<?php if ( ! empty( $feed ) ): ?>
    <?php grid_social_boxes_init_facebook_js(); ?>
    <?php foreach ( $items as $item ):  ?>
        <?php echo $this->get_post($item, $page); ?>
    <?php endforeach; ?>
<?php endif; ?>
