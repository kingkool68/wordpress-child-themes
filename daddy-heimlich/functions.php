<?php

// Include external libraries
require get_template_directory() . '/vendor/autoload.php';

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
	'class-daddio-pagination.php',
	'class-daddio-menus.php',
	'class-daddio-post-galleries.php',
	'class-instagram-scraper.php',
	'class-daddio-instagram.php',
	'class-daddio-instagram-locations.php',
	'class-daddio-instagram-debug.php',
	'class-daddio-instagram-maintenance.php',
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

if ( ! function_exists( 'str_starts_with' ) ) {
	/**
	 * Polyfill for PHP 8's str_starts_with
	 *
	 * @link https://php.watch/versions/8.0/str_starts_with-str_ends_with
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return boolean
	 */
	function str_starts_with( string $haystack, string $needle ): bool {
		return \strncmp( $haystack, $needle, \strlen( $needle ) ) === 0;
	}
}

if ( ! function_exists( 'str_contains' ) ) {
	/**
	 * Polyfill for PHP8's str_contains
	 *
	 * @link https://php.watch/versions/8.0/str_contains
	 *
	 * @param string $haystack The string to search in.
	 * @param string $needle   The substring to search for in the haystack.
	 * @return boolean
	 */
	function str_contains( string $haystack, string $needle ): bool {
		return '' === $needle || false !== strpos( $haystack, $needle );
	}
}
