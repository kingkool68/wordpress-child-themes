<?php

// Include external libraries
include 'vendor/ForceUTF8/Encoding.php';

// Include our own libraries
$files_to_require = array(
	'debugging.php',
	'class-daddio-helpers.php',
	'class-daddio-admin.php',
	'class-daddio-scripts-styles.php',
	'class-daddio-head.php',
	'class-daddio-dates.php',
	'class-daddio-media.php',
	'class-daddio-archive.php',
	'class-daddio-menus.php',
	'class-daddio-post-galleries.php',
	'class-daddio-instagram.php',
	'class-daddio-instagram-locations.php',
	'class-daddio-infinite-scroll.php',
	'class-daddio-on-this-day.php',
	'rsvp.php',
	'cli-commands.php',
);
foreach ( $files_to_require as $filename ) {
	$file = get_template_directory() . '/functions/' . $filename;
	if ( file_exists( $file ) ) {
		require_once $file;
	}
}

add_theme_support( 'post-thumbnails' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'title-tag' );
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );

// Google Analytics Debugging flag
function daddio_google_analytics_debugging_flag( $class = array() ) {
	if ( is_user_logged_in() && isset( $_GET['debug-ga'] ) ) {
		$class[] = 'debug-ga';
	}
	return $class;
}
add_filter( 'body_class', 'daddio_google_analytics_debugging_flag' );
