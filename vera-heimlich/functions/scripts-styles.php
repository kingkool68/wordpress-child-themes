<?php
function vah_wp_enqueue_scripts() {
	wp_register_style( 'vah-google-fonts', 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,700;1,300;1,700&family=Shrikhand&display=swap', array(), null, 'all' );
	wp_enqueue_style( 'vera-heimlich', get_stylesheet_directory_uri() . '/css/vera-heimlich' . Daddio_Scripts_Styles::get_css_suffix(), array( 'vah-google-fonts' ), null, 'all' );
}
add_action( 'wp_enqueue_scripts', 'vah_wp_enqueue_scripts' );
