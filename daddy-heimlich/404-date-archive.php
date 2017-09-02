<?php
$daddio_dates = Daddio_Dates::get_instance();
$year = get_query_var( 'year' );
$month = get_query_var( 'monthnum' );
$day = get_query_var( 'day' );

$future_prefix = 'On';

if ( $year && ! $month && ! $day ) {
	$timestamp = $year . '-01-01';
	$time_format = 'Y';
	$levels = 1;
	$future_prefix = 'In';
}
if ( $year && $month && ! $day ) {
	$timestamp = $year . '-' . $month . '-01';
	$time_format = 'F, Y,';
	$levels = 2;
	$future_prefix = 'In';
}
if ( $year && $month && $day ) {
	$timestamp = $year . '-' . $month . '-' . $day;
	$time_format = get_option( 'date_format' ) . ',';
	$levels = 3;
}


$relative_date = new DateTime( $timestamp );
$tense = 'future';
if ( $relative_date->format( 'U' ) < date( 'U' ) ) {
	$tense = 'past';
}
$offset = $relative_date->format( 'U' ) - date( 'U' );
$calculated_date = $offset + get_childs_birthday(); // integer since epoch
$smallest_unit = $daddio_dates->get_smallest_time_unit( $timestamp );

$age = get_childs_birthday_diff( $levels, $relative_date->format( 'U' ) );
$date = $relative_date->format( $time_format );

get_header();
?>
	<div id="content">
		<article class="article page">
			<h1 class="title">Nothing Found!</h1>
			<?php if ( 'past' == $tense ) : ?>
					<p><?php echo $date; ?> was <?php echo $age; ?> before <?php echo CHILD_NAME ?>&rsquo;s birthday.</p>
			<?php else : ?>
				<p><?php echo $future_prefix; ?> <?php echo $date; ?> <?php echo CHILD_NAME ?> will be <?php echo $age; ?> old.</p>
			<?php endif; ?>
			<?php do_action( 'daddio_content_footer', $post ); ?>
		</article>
	</div>

<?php get_footer();
