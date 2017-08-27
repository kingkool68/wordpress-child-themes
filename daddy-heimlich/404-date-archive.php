<?php
$daddio_dates = Daddio_Dates::get_instance();
$year = get_query_var( 'year' );
$month = get_query_var( 'monthnum' );
$day = get_query_var( 'day' );
$relative_date = new DateTime( $year . '-' . $month . '-' . $day );
$offset = $relative_date->format( 'U' ) - date( 'U' );
$calculated_date = $offset + get_childs_birthday(); // integer since epoch
$smallest_unit = $daddio_dates->get_smallest_time_unit( $year . '-' . $month . '-' . $day );
$time_format = get_option( 'date_format' );
switch( $smallest_unit ) {
	case 'hour':
	case 'hours':
	case 'minute':
	case 'minutes':
	case 'second':
	case 'seconds':
		$time_format .= ' ' . get_option( 'time_format' );
		break;
}

$age = get_childs_birthday_diff( 2, $relative_date->format( 'U' ) );

$tense = 'will be';
if ( $relative_date->format( 'U' ) < date( 'U' ) ) {
	$tense = 'was';
}
$date = $relative_date->format( $time_format );

get_header();
?>
	<div id="content">
		<article class="article page">
			<h1 class="title">Nothing Found!</h1>
				<p>On <?php echo $date; ?> <?php echo CHILD_NAME ?> <?php echo $tense; ?> <?php echo $age; ?> old.</p>
			<?php do_action( 'daddio_content_footer', $post ); ?>
		</article>
	</div>

<?php get_footer();
