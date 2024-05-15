<?php
/**
 * Tools to clean up and update old Instagram posts
 */
class Daddio_Instagram_Maintenance {
	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_actions();
			$instance->setup_filters();
		}
		return $instance;
	}

	/**
	 * Hook in to WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ), 9 );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {}

	/**
	 * Setup custom taxonomy to track maintenance tasks
	 */
	public function action_init() {
		$args = array(
			'labels'            => Daddio_Helpers::generate_taxonomy_labels( 'maintenance', 'maintenances' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => false,
			'show_tagcloud'     => false,
		);
		register_taxonomy( 'maintenance', array( Daddio_Instagram::$post_type ), $args );
	}

	/**
	 * Add Update Sync submenu
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=instagram',
			'Update Sync',
			'Update Sync',
			'manage_options',
			'instagram-update-sync',
			array( $this, 'handle_update_sync_submenu' )
		);
	}

	/**
	 * Render the Update Sync submenu
	 */
	public function handle_update_sync_submenu() {
		$args = array(
			'post_type'      => Daddio_Instagram::$post_type,
			'post_status'    => 'any',
			'post_parent'    => 0,
			'posts_per_page' => 20,
			'tax_query'      => array(
				array(
					'taxonomy' => 'maintenance',
					'field'    => 'slug',
					'terms'    => 'needs-to-be-updated',
				),
			),

			// For performance
			// 'no_found_rows'          => true,
			// 'update_post_meta_cache' => false,
			// 'update_post_term_cache' => false,
			// 'fields'                 => 'ids',
		);
		$query = new WP_Query( $args );
		foreach ( $query->posts as $post ) {
			echo 'view-source:' . $post->guid . '<br>';
		}
		/*
		global $wpdb;
		// Get all of the location IDs that need terms
		$location_ids = get_option( 'instagram-location-ids-needing-terms', array() );

		// Get all of the stale location IDs
		$stale_location_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT `meta_value` FROM {$wpdb->termmeta} WHERE `meta_key` = %s",
				'location-needs-updating'
			)
		);
		$stale_location_ids = array_map( 'absint', $stale_location_ids );
		$location_ids       = array_merge( $location_ids, $stale_location_ids );

		wp_enqueue_script(
			'daddio-location-sync-submenu',
			get_template_directory_uri() . '/js/admin-location-sync-submenu.js',
			array( 'jquery' )
		);

		$context = array(
			'location_ids' => $location_ids,
		);
		Sprig::out( 'admin/instagram-location-sync-submenu.twig', $context );
		*/
	}
}
Daddio_Instagram_Maintenance::get_instance();
