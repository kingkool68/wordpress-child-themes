<?php
get_header();

$obj = Daddio_Weather::get_instance();

echo 'LEGOLAND!';
$args = array(
	'latitude'  => '40.966222735791',
	'longitude' => '-73.856948992804',
	'time'      => '2017-12-16 04:41:56',
);
$weather = $obj->fetch_weather( $args );
var_dump( $weather['currently'] );
var_dump( $weather['hourly']['data'] );
var_dump( date( 'Y-m-d H:i a Z', $weather['currently']['time'] ) );


echo 'Manassas Park Community Center Manassas Park Community Center';
$args = array(
	'latitude'  => '38.782925131421',
	'longitude' => '-77.461950045337',
	'time'      => '2017-12-09 23:29:57',
);
$weather = $obj->fetch_weather( $args );
var_dump( $weather['currently'] );
var_dump( $weather['hourly']['data'] );
var_dump( date( 'Y-m-d H:i a Z', $weather['currently']['time'] ) );

get_footer();
