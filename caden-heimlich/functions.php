<?php
// Constants
define( 'CHILD_DATE_OF_BIRTH', '2020-05-13 01:15PM' );
define( 'CHILD_NAME', 'Caden' );
define( 'CHILD_INSTAGRAM_HANDLE', 'CadeWithLove' );
define( 'CHILD_INSTAGRAM_HASHTAG', 'CadenApollo' );
define( 'CHILD_FACEBOOK_URL', '' );
define( 'CHILD_THEME_COLOR', '#0f4c81' );

$files_to_include = array(
	'scripts-styles.php',
	// 'rsvp.php',
);
$dir = get_stylesheet_directory() . '/functions/';
foreach ( $files_to_include as $filename ) {
	$file = $dir . $filename;
	if ( file_exists( $file ) ) {
		include $file;
	}
}
