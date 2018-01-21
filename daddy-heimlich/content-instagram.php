<article class="article instagram h-entry">

	<?php do_action( 'daddio_content_header', $post ); ?>

	<div class="inner">
		<h1 class="title">
			<time datetime="<?php echo get_post_time( 'c', true ) ?>" class="dt-published">
				<?php if ( ! is_singular() ) { ?>
					<a href="<?php the_permalink(); ?>" class=""><?php the_time( get_child_time_format() ); ?></a>
				<?php } else { ?>
					<?php the_time( get_child_time_format() ); ?>
				<?php } ?>
			</time>
		</h1>
		<h2 class="time-stamp"><?php echo how_old_was_child(); ?></h2>
		<?php the_instagram_media(); ?>
		<div class="p-name e-content">
			<?php the_content(); ?>
		</div>

		<p class="via">(via <a href="<?php echo esc_url( $post->guid ); ?>" data-ga-category="instagram-via" data-ga-label="@<?php echo esc_attr( get_instagram_username() ); ?>|<?php echo esc_url( get_permalink() );?>" class="h-card p-author u-url">@<?php echo get_instagram_username(); ?></a>)</p>
	</div>

	<?php do_action( 'daddio_content_footer', $post ); ?>

</article>
