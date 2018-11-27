<?php

$the_content = array();
if ( have_posts() ) :
	while ( have_posts() ) : the_post();
	$post = get_post();
	$attachment_type = '';
	if ( ! empty( $post->post_mime_type ) ) {
		$pieces = explode( '/', $post->post_mime_type );
		if ( ! empty( $pieces[0] ) ) {
			$attachment_type = $pieces[0];
		}
	}

	$context   = array();
	$templates = array();

	switch ( $attachment_type ) {

		case 'image' :

			$attachment_size = get_query_var('size');
			if ( ! $attachment_size || in_array( $attachment_size, array('full', 'original') ) ) {
				$attachment_size = 'large';
			}
			$img = wp_get_attachment_image_src( get_the_ID(), $attachment_size );

			$max_width = 'none';
			if ( ! empty( $img[1] ) && is_numeric( $imag[1] ) ) {
				$max_width = $img[1] / 16;
			}

			$context = array(
				'daddio_content_header' => Sprig::do_action( 'daddio_content_header', $post ),
				'the_title'             => get_the_title(),
				'how_old'               => Daddio_Dates::how_old_was_child(),
				'the_image'             => wp_get_attachment_image( get_the_ID(), $attachment_size ),
				'max_width'             => $max_width,
				'the_content'           => apply_filters( 'the_content', get_the_content() ),
				'daddio_content_footer' => Sprig::do_action( 'daddio_content_footer', $post ),
			);
			$templates[] = 'attachment-image.twig';
			break;
	}

	$the_content[] =  Sprig::render( $templates, $context );

	endwhile;
endif;

$context = array(
	'the_content' => implode( "\n", $the_content ),
);
Sprig::out( 'attachment.twig', $context );
