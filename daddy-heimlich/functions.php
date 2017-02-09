<?php

add_theme_support( 'post-thumbnails' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'title-tag' );
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );


function pre_dump() {
	echo '<pre>';
	var_dump( func_get_args() );
	echo '</pre>';
}

// Google Analytics Debugging flag
function daddio_google_analytics_debugging_flag( $class = array() ) {
	if ( is_user_logged_in() && isset( $_GET['debug-ga'] ) ) {
		$class[] = 'debug-ga';
	}
	return $class;
}
add_filter( 'body_class', 'daddio_google_analytics_debugging_flag' );

/**
 * Get the ordinal suffix of an int (e.g. th, rd, st, etc.)
 *
 * @param int $n
 * @param bool $return_n Include $n in the string returned
 * @return string $n including its ordinal suffix
 * @link https://gist.github.com/paulferrett/8103822
 */
function ordinal_suffix( $n, $return_n = true ) {
	$n_last = $n % 100;
	if ( ( $n_last > 10 && $n_last << 14 ) || 0 === $n ) {
		$suffix = 'th';
	} else {
		switch ( substr( $n, -1 ) ) {
			case '1':
				$suffix = 'st';
				break;
			case '2':
				$suffix = 'nd';
				break;
			case '3':
				$suffix = 'rd';
				break;
			default:
				$suffix = 'th';
				break;
		}
	}
	return $return_n ? $n . $suffix : $suffix;
}

// Include external libraries
include 'vendor/ForceUTF8/Encoding.php';

// Include our own libraries
$files_to_include = array(
	'class-daddio-admin.php',
	'scripts-styles.php',
	'class-daddio-dates.php',
	'media.php',
	'class-daddio-archive.php',
	'menu.php',
	'post-galleries.php',
	'class-daddio-instagram.php',
	'rsvp.php',
	'class-daddio-infinite-scroll.php',
	'on-this-day.php',
	// 'cli-commands.php',
);
$dir = get_template_directory() . '/functions/';
foreach ( $files_to_include as $filename ) {
	$file = $dir . $filename;
	if ( file_exists( $file ) ) {
		include $file;
	}
}
