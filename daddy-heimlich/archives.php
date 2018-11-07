<?php get_header(); ?>

	<div id="content">
		<section class="archive-header">
			<h1 class="heading">Archives</h1>
		</section>
		<section class="archive-section date-archive">
			<h2 class="heading">By Date</h2>
			<?php
			$data = Daddio_Dates::get_monthly_archive_links();
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

		<section class="archive-section age-archive">
			<h2 class="heading">By Age</h2>
			<?php
			$data = Daddio_Dates::get_age_archive_data();
			// We can better group the dates by reversing our data
			$data = array_reverse( $data );
			?>
			<div class="age-group">
				<h3 class="year-heading"><a href="#">0 years</a></h3>
			<?php
			foreach ( $data as $item ) {
				$title_attr = 'View ' . $item['count'] . ' items from when ' . CHILD_NAME . ' was ' . $item['timestamp'] . ' old';
				if ( ! $item['has_month'] ) : ?>
					</div>
					<div class="age-group">
						<h3 class="year-heading">
							<a href="<?php echo esc_url( $item['permalink'] ); ?>" title="<?php echo esc_attr( $title_attr ); ?>">
								<?php echo $item['timestamp']; ?>
							</a>
						</h3>
				<?php else : ?>
					<a href="<?php echo esc_url( $item['permalink'] ); ?>" title="<?php echo esc_attr( $title_attr ); ?>" class="age-item">
						<?php echo $item['timestamp']; ?>
					</a>
				<?php endif;
			}
			?>
			</div>
		</section>

		<section class="archive-section tag-archive">
			<h2 class="heading">By Tag</h2>
			<?php
				$args = array(
					'taxonomy' => 'post_tag',
					'unit' => 'em',
					'number' => 50,
					'smallest' => 0.75,
					'largest' => 2.5,
				);
				wp_tag_cloud( $args );
			?>
		</section>
	</div>

<?php get_footer(); ?>
