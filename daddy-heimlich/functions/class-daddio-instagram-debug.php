<?php
class Daddio_Instagram_Debug {

	/**
	 * Nonce field for the submenu form
	 *
	 * @var string
	 */
	public static $nonce_field_action = 'instagram-debug';

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_actions();
		}
		return $instance;
	}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
	}

	/**
	 * Add Private Sync submenu
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=instagram',
			'Debugger',
			'Debugger',
			'manage_options',
			'instagram-debug',
			array( $this, 'handle_debug_instagram_submenu' )
		);
	}

	public function handle_debug_instagram_submenu() {
		$result              = array();
		$instagram_permalink = '';
		if (
			! empty( $_POST['instagram-source'] )
			&& check_admin_referer( static::$nonce_field_action )
		) {
			$instagram_source = wp_unslash( $_POST['instagram-source'] );
			$json             = Daddio_Instagram::get_instagram_json_from_html( $instagram_source );
			$page_type        = '';

			// It's a Tag page
			if ( isset( $json->entry_data->TagPage[0] ) ) {
				$top_posts = array();
				$other     = array();

				if ( isset( $json->entry_data->TagPage[0]->tag ) ) {
					$tag       = $json->entry_data->TagPage[0]->tag;
					$top_posts = $tag->top_posts->nodes;
					$other     = $tag->media->nodes;
				}

				// New format I detected on 01/02/2018
				if ( isset( $json->entry_data->TagPage[0]->graphql->hashtag ) ) {
					$tag       = $json->entry_data->TagPage[0]->graphql->hashtag;
					$top_posts = $tag->edge_hashtag_to_top_posts->edges;
					$other     = $tag->edge_hashtag_to_media->edges;
					if ( ! empty( $tag->name ) ) {
						$instagram_permalink = 'https://www.instagram.com/explore/tags/' . $tag->name . '/';
					}
				}
				$nodes     = array_merge( $top_posts, $other );
				$page_type = 'tag';

			}

			// It's a single Post page
			if ( ! empty( $json->graphql->shortcode_media ) ) {
				$post_page_node = $json->graphql->shortcode_media;
				$nodes          = array( $post_page_node );
				$page_type      = 'post';
				if ( ! empty( $post_page_node->shortcode ) ) {
					$instagram_permalink = 'https://www.instagram.com/p/' . $post_page_node->shortcode . '/';
				}
			}

			if ( ! empty( $nodes ) ) {
				foreach ( $nodes as $raw_node ) :
					$result[] = static::render_raw_node_debug( $raw_node );
				endforeach;
			}

			// It's a location page
			if ( ! empty( $json->entry_data->LocationsPage[0]->graphql->location ) ) {
				$location  = $json->entry_data->LocationsPage[0]->graphql->location;
				$page_type = 'location';
				if ( ! empty( $location->id ) ) {
					$instagram_permalink = 'https://www.instagram.com/explore/locations/' . $location->id . '/';
				}
				$location_data = (array) Daddio_Instagram_Locations::normalize_location_data( $location );
				foreach ( $location_data as $key => $val ) {
					switch ( $key ) {

						case 'website':
							$val = make_clickable( $val );
							break;

						case 'term_last_updated':
							$val = date( 'F j, Y g:i a', intval( $val ) );
							break;

					}
					$location_data[ $key ] = $val;
					if ( is_bool( $val ) ) {
						$location_data[ $key ] = ( $val ) ? 'true' : 'false';
					}
				}
				$context  = array(
					'location' => $location_data,
				);
				$result[] = Sprig::render( 'admin/instagram-debug-node.twig', $context );
			}
		}

		$action  = static::$nonce_field_action;
		$name    = '_wpnonce';
		$referer = true;
		$echo    = false;

		$text_area_value = '';
		if ( ! empty( $_POST['instagram-source'] ) ) {
			$text_area_value = wp_unslash( $_POST['instagram-source'] );
		}
		$context = array(
			'instagram_permalink' => $instagram_permalink,
			'result'              => implode( "\n", $result ),
			'nonce_field'         => wp_nonce_field(
				$action,
				$name,
				$referer,
				$echo
			),
			'form_action_url'     => admin_url( 'edit.php?post_type=instagram&page=instagram-debug' ),
			'text_area_value'     => $text_area_value,
			'submit_button'       => get_submit_button( 'Debug', 'primary' ),
		);
		Sprig::out( 'admin/instagram-debug-submenu.twig', $context );
	}

	public static function render_raw_node_debug( $raw_node, $level = 1 ) {
		$node        = Daddio_Instagram::normalize_instagram_data( $raw_node );
		$child_nodes = array();
		if ( ! empty( $node->children ) ) {
			$child_level = $level + 1;
			foreach ( $node->children as $child_node ) {
				$child_nodes[] = static::render_raw_node_debug( $child_node, $child_level );
			}
		}

		$node_arr = (array) $node;
		unset( $node_arr['children'] );
		unset( $node_arr['_normalized'] );
		$node_arr['children_count'] = count( $node->children );
		foreach ( $node_arr as $key => $val ) {
			if ( is_bool( $val ) ) {
				$node_arr[ $key ] = ( $val ) ? 'true' : 'false';
			}
		}

		$location_arr = array();
		if ( ! empty( $node->location_id ) ) {
			$args          = array(
				'update_term' => false, // Don't add or update term info when we're just debugging instagram data
			);
			$location_data = Daddio_Instagram_Locations::get_location_data_by_location_id( $node->location_id, $args );
			foreach ( $location_data as $key => $val ) {
				switch ( $key ) {

					case 'website':
						$val = make_clickable( $val );
						break;

					case 'term_last_updated':
						$val = date( 'F j, Y g:i a', intval( $val ) );
						break;

				}
				$location_arr[ $key ] = $val;
				if ( is_bool( $val ) ) {
					$location_arr[ $key ] = ( $val ) ? 'true' : 'false';
				}
			}
			if ( $level > 1 ) {
				$location_arr = array();
			}
		}

		$context = array(
			'level'       => $level,
			'node'        => $node_arr,
			'child_nodes' => implode( "\n", $child_nodes ),
			'location'    => $location_arr,
		);
		return Sprig::render( 'admin/instagram-debug-node.twig', $context );
	}
}
Daddio_Instagram_Debug::get_instance();
