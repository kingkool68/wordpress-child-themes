<?php get_header(); ?>

	<div id="content">
		<section>
			<h1>By Age</h1>
		</section>
		<section>
			<h1>By Date</h1>
			<form>
				<label>Enter a Date</label>
				<input type="">
			</form>
			<?php
			$data = get_monthly_archive_links();
			foreach ( $data as $year => $month_data ) {
				?>
				<h2><a href="<?php echo esc_url( get_site_url() . '/' . $year . '/' ); ?>"><?php echo $year; ?></a></h2>
				<?php
				foreach ( $month_data as $month => $obj ) {
					$item = $month;
					if ( ! empty( $obj ) ) {
						$item = '<a href="' . esc_url( $obj->link ) . '" title="' . esc_attr( number_format( $obj->count ) ) . ' items">' . $item . '</a>';
					}
					echo $item;
				}
			}
			?>
		</section>
		<section>
			<h1>By Tag</h1>
			<?php
				$args = array(
					'taxonomy' => 'post_tag',
				);
				wp_tag_cloud( $args );
			?>
		</section>
	</div>

<?php get_footer(); ?>
