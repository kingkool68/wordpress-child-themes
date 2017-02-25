<?php
function vah_wp_enqueue_scripts() {
	// CSS
	$css_suffix = '.min.css';
	if ( isset( $_GET['debug-css'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
		$css_suffix = '.css';
	}
	wp_register_style( 'vah-google-fonts', 'https://fonts.googleapis.com/css?family=Crete+Round:400,400italic|Shrikhand|Open+Sans:300italic,700italic,300,700', array(), null, 'all' );
	wp_enqueue_style( 'vera-heimlich', get_stylesheet_directory_uri() . '/css/vera-heimlich' . $css_suffix, array( 'vah-google-fonts' ), null, 'all' );
}
add_action( 'wp_enqueue_scripts', 'vah_wp_enqueue_scripts' );
