<?php
add_filter( 'body_class', function( $classes = array() ) {
	$classes[] = 'error404';
	return $classes;
} );

$year  = get_query_var( 'year' );
$month = get_query_var( 'monthnum' );
$day   = get_query_var( 'day' );

$future_prefix = 'On';

if ( $year && ! $month && ! $day ) {
	$timestamp     = $year . '-01-01';
	$time_format   = 'Y';
	$levels        = 1;
	$future_prefix = 'In';
}

if ( $year && $month && ! $day ) {
	$timestamp     = $year . '-' . $month . '-01';
	$time_format   = 'F, Y,';
	$levels        = 2;
	$future_prefix = 'In';
}

if ( $year && $month && $day ) {
	$timestamp   = $year . '-' . $month . '-' . $day;
	$time_format = get_option( 'date_format' ) . ',';
	$levels      = 3;
}

$relative_date   = new DateTime( $timestamp );
$offset          = $relative_date->format( 'U' ) - date( 'U' );
$calculated_date = $offset + Daddio_Dates::get_childs_birthday(); // integer since epoch
$smallest_unit   = Daddio_Dates::get_smallest_time_unit( $timestamp );

$tense = 'future';
if ( $relative_date->format( 'U' ) < date( 'U' ) ) {
	$tense = 'past';
}

$age  = Daddio_Dates::get_childs_birthday_diff( $levels, $relative_date->format( 'U' ) );
$date = $relative_date->format( $time_format );

$context = array(
	'child_name'    => CHILD_NAME,
	'tense'         => $tense,
	'date'          => $date,
	'age'           => $age,
	'future_prefix' => $future_prefix,
);
Sprig::out( '404-date-archive.twig', $context );
