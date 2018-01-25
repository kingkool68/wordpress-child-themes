<?php
// Constants
define( 'CHILD_DATE_OF_BIRTH', '2017-02-24 12:13PM' );
define( 'CHILD_NAME', 'Vera' );
define( 'CHILD_INSTAGRAM_HANDLE', '' );
define( 'CHILD_INSTAGRAM_HASHTAG', 'VeraAddison' );
define( 'CHILD_FACEBOOK_URL', 'https://www.facebook.com/media/set/?set=ft.10103094755058458&type=1' );
define( 'CHILD_THEME_COLOR', '#75ac6c' );

$files_to_include = array(
	'scripts-styles.php',
	'rsvp.php',
);
$dir = get_stylesheet_directory() . '/functions/';
foreach ( $files_to_include as $filename ) {
	$file = $dir . $filename;
	if ( file_exists( $file ) ) {
		include $file;
	}
}
