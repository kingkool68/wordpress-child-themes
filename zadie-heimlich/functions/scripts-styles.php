<?php
function zadie_wp_enqueue_scripts() {
	wp_register_style( 'zah-google-fonts', 'https://fonts.googleapis.com/css2?family=Crete+Round:ital@0;1&family=Open+Sans:ital,wght@0,300;0,700;1,300;1,700&display=swap', array(), null, 'all' );
	wp_enqueue_style( 'zadie-heimlich', get_stylesheet_directory_uri() . '/css/zadie-heimlich' . Daddio_Scripts_Styles::get_css_suffix(), array( 'zah-google-fonts' ), null, 'all' );
}
add_action( 'wp_enqueue_scripts', 'zadie_wp_enqueue_scripts' );
