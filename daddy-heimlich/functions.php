<?php

add_theme_support( 'post-thumbnails' );
add_theme_support( 'automatic-feed-links' );
add_theme_support( 'title-tag' );
add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );

/**
 * Pretty-print alternative to var_dump()
 */
if ( ! function_exists( 'wp_dump' ) ) :
	function wp_dump() {
		foreach ( func_get_args() as $arg ) {
			echo '<xmp>';
			var_dump( $arg );
			echo '</xmp>';
		}
	}
endif;

if ( ! function_exists( 'wp_log' ) ) :
	function wp_log() {
		foreach ( func_get_args() as $arg ) {
			if ( is_array( $arg ) || is_object( $arg ) ) {
				error_log( print_r( $arg, true ) );
			} else {
				error_log( $arg );
			}
		}
	}
endif;

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

function daddio_generate_taxonomy_labels( $singular = '', $plural = '', $overrides = array() ) {
	$lc_plural = strtolower( $plural );
	$uc_plural = ucwords( $lc_plural );
	$lc_singular = strtolower( $singular );
	$uc_singular = ucwords( $lc_singular );

	$labels = array(
		'name'                       => $uc_plural,
		'singular_name'              => $uc_singular,
		'menu_name'                  => $uc_plural,
		'all_items'                  => 'All ' . $uc_plural,
		'parent_item'                => 'Parent ' . $uc_singular,
		'parent_item_colon'          => 'Parent ' . $uc_singular . ':',
		'new_item_name'              => 'New ' . $uc_singular . ' Name',
		'add_new_item'               => 'Add New ' . $uc_singular,
		'edit_item'                  => 'Edit ' . $uc_singular,
		'update_item'                => 'Update ' . $uc_singular,
		'view_item'                  => 'View ' . $uc_singular,
		'separate_items_with_commas' => 'Separate ' . $lc_plural . ' with commas',
		'add_or_remove_items'        => 'Add or remove ' . $lc_plural,
		'choose_from_most_used'      => 'Choose from the most used',
		'popular_items'              => 'Popular ' . $uc_plural,
		'search_items'               => 'Search ' . $uc_plural,
		'not_found'                  => 'Not Found',
		'no_terms'                   => 'No ' . $lc_plural,
		'items_list'                 => ucfirst( $lc_plural ) . ' list',
		'items_list_navigation'      => ucfirst( $lc_plural ) . ' list navigation',
	);
	return wp_parse_args( $labels, $overrides );
}

// Include external libraries
include 'vendor/ForceUTF8/Encoding.php';

// Include our own libraries
$files_to_include = array(
	'class-daddio-admin.php',
	'class-daddio-scripts-styles.php',
	'class-daddio-dates.php',
	'class-daddio-media.php',
	'class-daddio-archive.php',
	'class-daddio-menus.php',
	'class-daddio-post-galleries.php',
	'class-daddio-instagram.php',
	'rsvp.php',
	'class-daddio-infinite-scroll.php',
	'class-daddio-on-this-day.php',
	// 'cli-commands.php',
);
$dir = get_template_directory() . '/functions/';
foreach ( $files_to_include as $filename ) {
	$file = $dir . $filename;
	if ( file_exists( $file ) ) {
		include $file;
	}
}
