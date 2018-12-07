<?php

$month_archive_data = Daddio_Dates::get_monthly_archive_links();
foreach ( $month_archive_data as $year => $month_data ) {
	foreach ( $month_data as $month_num => $obj ) {
		if ( ! is_object( $obj ) ) {
			$obj = new stdClass;
		}
		$obj->month_abbr                           = date( 'M', strtotime( '2001-' . $month_num . '-01' ) );
		$obj->month_name                           = date( 'F', strtotime( '2001-' . $month_num . '-01' ) );
		$month_archive_data[ $year ][ $month_num ] = $obj;
	}
}

$age_archive_data = Daddio_Dates::get_age_archive_data();
// We can better group the dates by reversing our data
$age_archive_data= array_reverse( $age_archive_data );

$tag_cloud_args = array(
	'taxonomy' => 'post_tag',
	'unit'     => 'em',
	'number'   => 50,
	'smallest' => 0.75,
	'largest'  => 2.5,
	'echo'     => false,
);

$context = array(
	'site_url'           => get_site_url(),
	'child_name'         => CHILD_NAME,
	'month_archive_data' => $month_archive_data,
	'age_archive_data'   => $age_archive_data,
	'tag_cloud'          => wp_tag_cloud( $tag_cloud_args ),
);
Sprig::out( 'archives.twig', $context );
