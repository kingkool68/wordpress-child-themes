<?php
global $wp_query;
$the_content = array();
if ( have_posts() ) :
	while ( have_posts() ) :
		the_post();
		$current_iteration = $wp_query->current_post + 1;
		$context           = array();
		$templates         = array();
		switch ( $post->post_type ) {
			case 'instagram':
				$context     = array(
					'title'              => get_the_title(),
					'permalink_url'      => get_permalink(),
					'the_time'           => get_the_time( Daddio_Dates::get_child_time_format() ),
					'machine_datetime'   => get_post_time( 'c', true ),
					'child_age'          => Daddio_Dates::how_old_was_child(),
					'instagram_media'    => Daddio_Media::get_the_instagram_media( get_the_ID(), $current_iteration ),
					'the_content'        => apply_filters( 'the_content', get_the_content() ),
					'via_url'            => get_the_guid(),
					'instagram_username' => Daddio_Instagram::get_instagram_username(),
				);
				$templates[] = 'content-instagram.twig';
				break;

			case 'page':
				$context     = array(
					'the_title'   => get_the_title(),
					'the_content' => apply_filters( 'the_content', get_the_content() ),
				);
				$templates[] = 'content-page.twig';
				break;

			default:
				$context     = array(
					'title'                => get_the_title(),
					'permalink_url'        => get_permalink(),
					'the_datetime'         => get_the_time( Daddio_Dates::get_child_time_format() ),
					'the_machine_datetime' => get_post_time( 'c', true ),
					'child_age'            => Daddio_Dates::how_old_was_child(),
					'the_content'          => apply_filters( 'the_content', get_the_content() ),
				);
				$templates[] = 'content-post.twig';
		}
		$the_content[] = Sprig::render( $templates, $context );
	endwhile;
endif;

$context = array(
	'archive_header' => Daddio_Archive::get_archive_header(),
	'the_content'    => implode( "\n", $the_content ),
	'pagination'     => Daddio_Pagination::render_from_wp_query(),
);
if ( Daddio_On_This_Day::is_on_this_day() ) {
	$context['archive_header'] = Daddio_On_This_Day::get_switch_date_form();
}
Sprig::out( 'index.twig', $context );
