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

		switch ( $attachment_type ) {

			case 'image' :

				$attachment_size = get_query_var('size');
				if ( ! $attachment_size || in_array( $attachment_size, array('full', 'original') ) ) {
					$attachment_size = 'large';
				}
				$img = wp_get_attachment_image_src( get_the_ID(), $attachment_size );

				$max_width = 'none';
				if ( ! empty( $img[1] ) && is_numeric( $img[1] ) ) {
					$max_width = $img[1] / 16;
				}

				$context = array(
					'attachment_type'    => $attachment_type,
					'parent_link'        => Daddio_Post_Galleries::render_parent_post_link(),
					'the_title'          => get_the_title(),
					'how_old'            => Daddio_Dates::how_old_was_child(),
					'the_image'          => wp_get_attachment_image( get_the_ID(), $attachment_size ),
					'max_width'          => $max_width,
					'the_content'        => apply_filters( 'the_content', get_the_content() ),
					'gallery_navigation' => Daddio_Post_Galleries::render_gallery_navigation(),
				);
				$the_content[] =  Sprig::render( 'attachment-image.twig', $context );
				break;

			case 'video' :

				$video = Daddio_Media::get_the_instagram_media( $post->post_parent );
				$title = get_the_title();
				$context = array(
					'attachment_type'    => $attachment_type,
					'the_title'          => $title,
					'how_old'            => Daddio_Dates::how_old_was_child(),
					'the_video'          => $video,
					'the_content'        => apply_filters( 'the_content', get_the_content() ),
				);
				$the_content[] =  Sprig::render( 'attachment-video.twig', $context );
				break;

			default :

		}

	endwhile;
endif;

$context = array(
	'the_content' => implode( "\n", $the_content ),
);
Sprig::out( 'attachment.twig', $context );
