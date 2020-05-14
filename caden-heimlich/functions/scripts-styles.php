<?php
function cah_wp_enqueue_scripts() {
	wp_register_style( 'cah-google-fonts', 'https://fonts.googleapis.com/css2?family=Asap:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500;1,600;1,700&display=swap', array(), null, 'all' );
	wp_enqueue_style( 'caden-heimlich', get_stylesheet_directory_uri() . '/css/caden-heimlich' . Daddio_Scripts_Styles::get_css_suffix(), array( 'cah-google-fonts' ), null, 'all' );
}
add_action( 'wp_enqueue_scripts', 'cah_wp_enqueue_scripts' );
