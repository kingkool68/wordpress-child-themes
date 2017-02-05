<article class="article instagram">

	<?php do_action( 'daddio_content_header', $post ); ?>

	<div class="inner">
		<h1 class="title">
			<time datetime="<?php echo get_post_time( 'c', true ) ?>">
				<?php if( !is_singular() ) { ?>
					<a href="<?php the_permalink(); ?>"><?php the_time( get_child_time_format() ); ?></a>
				<?php } else { ?>
					<?php the_time( get_child_time_format() ); ?>
				<?php } ?>
			</time>
		</h1>
		<h2 class="time-stamp"><?php echo how_old_was_child(); ?></h2>
		<?php daddio_the_instagram_media(); ?>
		<?php the_content(); ?>

		<p class="via">(via <a href="<?php echo $post->guid; ?>" data-ga-category="instagram-via" data-ga-label="@<?php echo esc_attr( get_instagram_username() ); ?>|<?php echo esc_url( get_permalink() );?>">@<?php echo get_instagram_username(); ?></a>)</p>
	</div>

	<?php do_action( 'daddio_content_footer', $post ); ?>

</article>
