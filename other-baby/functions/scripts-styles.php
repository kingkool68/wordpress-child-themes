<?php
function babby_wp_enqueue_scripts() {
	// CSS
	$css_suffix = '.min.css';
	if( isset( $_GET['debug-css'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
		$css_suffix = '.css';
	}
	wp_register_style( 'zah-google-fonts', 'https://fonts.googleapis.com/css?family=Crete+Round:400,400italic|Open+Sans:300italic,700italic,300,700', array(), NULL, 'all' );
	wp_enqueue_style( 'other-baby', get_stylesheet_directory_uri() . '/css/other-baby' . $css_suffix, array('zah-google-fonts'), NULL, 'all' );
}
add_action( 'wp_enqueue_scripts', 'babby_wp_enqueue_scripts' );
