<?php get_header(); ?>

	<div id="content">
		<section class="date-archive">
			<h1>By Age</h1>
			<?php
			$data = get_age_archive_data();
			foreach ( $data as $item ) {
				if ( ! $item['has_month'] ) : ?>
					<h2><a href="<?php echo esc_url( $item['permalink'] ); ?>"><?php echo $item['timestamp']; ?></a></h2>
				<?php else : ?>
					<a href="<?php echo esc_url( $item['permalink'] ); ?>" style="display: block;"><?php echo $item['timestamp']; ?></a>
				<?php endif;
			}
			?>
		</section>
		<section class="date-archive">
			<h1 class="heading">By Date</h1>
			<form>
				<label>Enter a Date</label>
				<input type="">
			</form>
			<?php
			$data = get_monthly_archive_links();
			foreach ( $data as $year => $month_data ) {
				?>
				<h2 class="year-heading"><a href="<?php echo esc_url( get_site_url() . '/' . $year . '/' ); ?>"><?php echo $year; ?></a></h2>
				<?php
				foreach ( $month_data as $month_num => $obj ) {
					$month_abbr = date( 'M', strtotime( '2001-' . $month_num . '-01' ) );
					$month_name = date( 'F', strtotime( '2001-' . $month_num . '-01' ) );
					if ( ! empty( $obj ) ) {
						$title_attr = 'View ' . $obj->count . ' items from ' . $month_name . ' ' . $year;
						echo '<a href="' . esc_url( $obj->link ) . '" title="' . esc_attr( $title_attr ) . '" class="rounded-button item">';
							echo '<span aria-label="' . esc_attr( $month_name . ' ' . $year ) . '">' . $month_abbr . '</span>';
						echo '</a>';
					} else {
						echo '<span class="item rounded-button" aria-label="' . esc_attr( $month_name . ' ' . $year ) . '">' . $month_abbr . '</span>';
					}
				}
			}
			?>
		</section>
		<section>
			<h1>By Tag</h1>
			<?php
				$args = array(
					'taxonomy' => 'post_tag',
					'unit' => 'em',
					'number' => 50,
					'smallest' => 0.75,
					'largest' => 3,
				);
				wp_tag_cloud( $args );
			?>
		</section>
	</div>

<?php get_footer(); ?>
