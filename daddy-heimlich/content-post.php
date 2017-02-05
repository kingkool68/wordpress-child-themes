<article class="article post">

	<?php do_action( 'daddio_content_header', $post ); ?>

	<h1 class="title">
		<?php if( !is_singular() ) { ?>
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
		<?php } else { ?>
			<?php the_title(); ?>
		<?php } ?>
	</h1>
	<h2 class="time-stamp"><time datetime="<?php echo get_post_time( 'c', true ) ?>"><?php the_time( get_child_time_format() ); ?></time> <b>&bull;</b> <span class="how-old-was-zadie"><?php echo how_old_was_child(); ?></span></h2>

	<div class="inner">
		<?php the_content(); ?>
	</div>

	<?php do_action( 'daddio_content_footer', $post ); ?>

</article>
