<?php
$the_year  = date('Y');
$the_month = get_query_var( 'zah-on-this-month' );
$the_day   = get_query_var( 'zah-on-this-day' );
$the_time  = strtotime( $the_year . '-' . $the_month . '-' . $the_day );
$the_date  = date( 'F j<\s\up>S</\s\up>', $the_time );

$context = array(
	'the_date'         => $the_date,
	'switch_date_form' => Daddio_On_This_Day::get_switch_date_form(),
);
Sprig::out( '404-on-this-day.twig', $context );
