<?php
get_header();

$scraper  = Daddio_Instagram::get_instagram_scraper();
$location = $scraper->getLocationById( 115578671850891 );

// https://stackoverflow.com/a/27754169/1119655
function getProtectedValue( $obj, $name ) {
	$array  = (array) $obj;
	$prefix = chr( 0 ) . '*' . chr( 0 );
	return $array[ $prefix . $name ];
}

var_dump( getProtectedValue( $location, 'data' ) );
var_dump( $location->getLat() );
var_dump( $location->getLng() );

get_footer();
