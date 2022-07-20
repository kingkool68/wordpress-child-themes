<?php
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

function fix_zadies_instagram_images() {
	$modified_posts = 0;
	// Get all Instagram posts
	$args  = array(
		'posts_per_page' => -1,
		'post_type'      => 'instagram',
		'post_status'    => 'any',
	);
	$posts = get_posts( $args );
	foreach ( $posts as $post ) {
		$new_post_content = $post->post_content;
		if ( has_shortcode( $post->post_content, 'video' ) ) {
			// Get the ID of the Video attachment and store it as post_meta
			$args        = array(
				'post_type'      => 'attachment',
				'post_parent'    => $post->ID,
				'post_mime_type' => 'video/mp4',
			);
			$video_posts = get_posts( $args );
			foreach ( $video_posts as $video_post ) {
				update_post_meta( $post->ID, '_video_id', $video_post->ID );
			}
		}

		// Strip inline images
		$new_post_content = preg_replace( '/<img(.+) \/>/i', '', $new_post_content );

		// Strip all shortcodes
		$new_post_content = trim( strip_shortcodes( $new_post_content ) );

		if ( $new_post_content !== $post->post_content ) {
			$new_post = array(
				'ID'           => $post->ID,
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

function zah_prep_locations() {
	$args  = array(
		'post_type'      => 'instagram',
		'post_status'    => 'public',
		'posts_per_page' => -1,
	);
	$query = new WP_Query( $args );
	$count = 0;
	foreach ( $query->posts as $post ) {
		$post_id         = $post->ID;
		$has_location_id = get_post_meta( $post_id, 'instagram_location_id', true );
		if ( ! $has_location_id ) {
			update_post_meta( $post_id, 'needs-location-data', '1' );
			$count++;
		}
	}
	WP_CLI::success( number_format( $count ) . ' posts need location data out of ' . number_format( $query->found_posts ) . ' posts' );
}
WP_CLI::add_command( 'zah-prep-locations', 'zah_prep_locations' );

function zah_add_locations() {
	// Get all published instagram posts that don't have a instagram_location_id meta key set...
	$args  = array(
		'post_type'      => 'instagram',
		'post_status'    => 'public',
		'posts_per_page' => -1,
		'meta_query'     => array(
			array(
				'key'     => 'needs-location-data',
				'value'   => '1',
				'compare' => '=',
			),
		),
	);
	$query = new WP_Query( $args );
	$insta = Daddio_Instagram::get_instance();
	foreach ( $query->posts as $post ) {
		$post_id = $post->ID;
		$guid    = $post->guid;
		$parts   = explode( 'com/p/', $guid );
		$code    = $parts[1];
		$code    = str_replace( '/', '', $code );
		$json    = $insta->fetch_single_instagram( $code );
		if ( isset( $json->entry_data->PostPage[0] ) ) {
			$node = $json->entry_data->PostPage[0]->graphql->shortcode_media;
			$node = $insta->normalize_instagram_data( $node );
			do_action( 'daddio_after_instagram_inserted', $post->ID, $node );
			delete_post_meta( $post_id, 'needs-location-data' );
			WP_CLI::line( $post_id . ' (https://www.instagram.com/p/' . $code . '/) is done' );
		} else {
			update_post_meta( $post_id, 'needs-private-location-data', '1' );
			WP_CLI::line( 'PRIVATE: ' . $post_id . ' (https://www.instagram.com/p/' . $code . '/) is private!' );
		}
	}
	WP_CLI::success( 'All Done!' );
}
WP_CLI::add_command( 'zah-add-locations', 'zah_add_locations' );

function zah_tag_posts_to_be_updated() {
	// Make sure needs-to-be-updated term exists...
	$args      = array(
		'term_name' => 'Needs to be updated',
		'taxonomy'  => 'maintenance',
	);
	$term      = get_term_by(
		$field = 'name',
		$args['term_name'],
		$args['taxonomy']
	);
	if ( $term === false ) {
		$term = wp_insert_term(
			$args['term_name'],
			$args['taxonomy'],
		);
		if ( is_wp_error( $term ) ) {
			wp_die( $term );
		}
		$term = (object) $term;
	}

	// Get all Instagram posts
	$args  = array(
		'post_type'              => Daddio_Instagram::$post_type,
		'post_status'            => 'any',
		'posts_per_page'         => -1,

		// For performance
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		// 'update_post_term_cache' => false,
		'fields'                 => 'ids',
	);
	$query = new WP_Query( $args );
	foreach ( $query->posts as $post_id ) {
		$result = wp_set_object_terms(
			$post_id,
			$term->term_id,
			$term->taxonomy,
			true
		);
	}
	$count = count( $query->posts );
	$count = number_format( $count );
	WP_CLI::success( 'Associated with ' . $count . ' Instagram posts' );
}
WP_CLI::add_command( 'zah-tag-posts-to-be-updated', 'zah_tag_posts_to_be_updated' );
