<?php

$the_content = array();
if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		$context   = array();
		$templates = array();
		switch ( $post->post_type ) {
			case 'instagram' :
				$context = array(
					'daddio_content_header' => Sprig::do_action( 'daddio_content_header', get_post() ),
					'title'                 => get_the_title(),
					'permalink_url'         => get_permalink(),
					'the_time'              => get_the_time( Daddio_Dates::get_child_time_format() ),
					'machine_datetime'      => get_post_time( 'c', true ),
					'child_age'             => Daddio_Dates::how_old_was_child(),
					'instagram_media'       => Daddio_Media::get_the_instagram_media(),
					'the_content'           => apply_filters( 'the_content', get_the_content() ),
					'via_url'               => get_the_guid(),
					'instagram_username'    => Daddio_Instagram::get_instagram_username(),
					'daddio_content_footer' => Sprig::do_action( 'daddio_content_footer', get_post() ),
				);
				$templates[] = 'content-instagram.twig';
				break;

			case 'page' :
				$context = array(
					'the_title'             => get_the_title(),
					'the_content'           => apply_filters( 'the_content', get_the_content() ),
					'daddio_content_footer' => Sprig::do_action( 'daddio_content_footer', get_post() ),
				);
				$templates[] = 'content-page.twig';
				break;

			default :
				$context = array(
					'daddio_content_header' => Sprig::do_action( 'daddio_content_header', get_post() ),
					'title'                 => get_the_title(),
					'permalink_url'         => get_permalink(),
					'the_datetime'          => get_the_time( Daddio_Dates::get_child_time_format() ),
					'the_machine_datetime'  => get_post_time( 'c', true ),
					'child_age'             => Daddio_Dates::how_old_was_child(),
					'the_content'           => apply_filters( 'the_content', get_the_content() ),
					'daddio_content_footer' => Sprig::do_action( 'daddio_content_footer', get_post() ),
				);
				$templates[] = 'content-post.twig';
		}
		$the_content[] =  Sprig::render( $templates, $context );
	endwhile;
endif;

$context = array(
	'before_content' => Sprig::do_action( 'daddio_before_content', get_post() ),
	'the_content'    => implode( "\n", $the_content ),
	'pagination'     => Daddio_Pagination::render_from_wp_query(),
);
Sprig::out( 'index.twig', $context );
