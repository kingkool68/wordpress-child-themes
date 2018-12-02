<?php
add_filter( 'body_class', function( $classes = array() ) {
	$classes[] = 'error404';
	return $classes;
} );

$age_query_var = get_query_var( 'age' );
if ( $age_query_var ) {
	$age = preg_replace( '/(\d+)/', ' $1 ', $age_query_var );
	$age = strtolower( $age ) . ' old';
}
$relative_date = new DateTime( $age_query_var );
$offset = $relative_date->format( 'U' ) - date( 'U' );
$calculated_date = $offset + Daddio_Dates::get_childs_birthday(); // integer since epoch
$smallest_unit = Daddio_Dates::get_smallest_time_unit( $age_query_var );
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

$tense = 'will be';
if ( $calculated_date < date( 'U' ) ) {
	$tense = 'was';
}
$date = date( $time_format, $calculated_date );

get_header();
?>
	<div id="content">
		<article class="article page">
			<h1 class="title">Nothing Found!</h1>
				<p class="date-explanation"><?php echo CHILD_NAME ?> <?php echo $tense; ?> <?php echo $age; ?> on <?php echo $date; ?></p>
		</article>
	</div>

<?php get_footer();
