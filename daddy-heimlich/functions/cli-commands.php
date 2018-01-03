<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

function fix_zadies_instagram_images() {
	$modified_posts = 0;
	// Get all Instagram posts
	$args = array(
		'posts_per_page' => -1,
		'post_type' => 'instagram',
		'post_status' => 'any',
	);
	$posts = get_posts( $args );
	foreach ( $posts as $post ) {
		$new_post_content = $post->post_content;
		if ( has_shortcode( $post->post_content, 'video' ) ) {
			// Get the ID of the Video attachment and store it as post_meta
			$args = array(
				'post_type' => 'attachment',
				'post_parent' => $post->ID,
				'post_mime_type' => 'video/mp4',
			);
			$video_posts = get_posts( $args );
			foreach ( $video_posts as $video_post ) {
				update_post_meta( $post->ID, '_video_id', $video_post->ID );
			}
		}

		// Strip inline images
		$new_post_content = preg_replace('/<img(.+) \/>/i', '', $new_post_content );

		// Strip all shortcodes
		$new_post_content = trim( strip_shortcodes( $new_post_content ) );

		if ( $new_post_content != $post->post_content ) {
			$new_post = array(
				'ID' => $post->ID,
				'post_content' => $new_post_content,
			);
			wp_update_post( $new_post );
			$modified_posts++;
		}
	}
	WP_CLI::line( 'Modified ' . $modified_posts . ' Instagram posts.' );
	WP_CLI::success( 'Done!' );
}
WP_CLI::add_command( 'zah-fix-instagrams', 'fix_zadies_instagram_images' );

function zah_add_locations() {
	// Get all published instagram posts that don't have a instagram_location_id meta key set...
	$args = array(
		'post_type'      => 'instagram',
		'post_status'    => 'public',
		'posts_per_page' => 1,
		'meta_query'     => array(
			array(
				'key'     => 'instagram_location_id',
				'compare' => 'NOT EXISTS',
			),
		),
	);
	$query = new WP_Query( $args );
	// @TODO Run through eahc post and get any location data from Instagram to process
	// Probably need to call do_action( 'daddio_after_instagram_inserted' ) passing the post_id and the node data
	// fetch_single_instagram() might be useful
	$insta = Daddio_Instagram::get_instance();
	foreach ( $query->posts as $post ) {
		$post_id = $post->ID;
		$guid = $post->guid;
		$parts = explode( 'com/p/', $guid );
		$code = $parts[1];
		$code = str_replace( '/', '', $code );
		$json = $insta->fetch_single_instagram( $code );
		if ( isset( $json->entry_data->PostPage[0] ) ) {
			$node = $json->entry_data->PostPage[0]->graphql->shortcode_media;
			$node = $insta->normalize_instagram_data( $node );
			// do_action( 'daddio_after_instagram_inserted', $post_id, $node );
			print_r( $node );
		}

		WP_CLI::line( $post_id . ' (' . $code . ') is done' );
	}
	WP_CLI::success( 'Done!' );
}
WP_CLI::add_command( 'zah-add-locations', 'zah_add_locations' );
