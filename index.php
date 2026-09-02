<?php
/**
 * Front controller. WordPress core lives in cms/ (installed by Composer);
 * this file tells it to load the theme from the root wp-content/.
 */
define( 'WP_USE_THEMES', true );

require dirname( __FILE__ ) . '/cms/wp-blog-header.php';
