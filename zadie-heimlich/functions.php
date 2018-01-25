<?php
// Constants
define( 'CHILD_DATE_OF_BIRTH', '2014-12-28 7:04PM' );
define( 'CHILD_NAME', 'Zadie' );
define( 'CHILD_INSTAGRAM_HANDLE', 'lilzadiebug' );
define( 'CHILD_INSTAGRAM_HASHTAG', 'ZadieAlyssa' );
define( 'CHILD_FACEBOOK_URL', 'https://www.facebook.com/media/set/?set=ft.10101891838917048&type=1' );
define( 'CHILD_THEME_COLOR', '#7b0e7f' );

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
