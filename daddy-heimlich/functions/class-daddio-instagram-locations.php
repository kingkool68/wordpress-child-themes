<?php
class Daddio_Instagram_Locations {

	/**
	 * Store a mapping of term ids keyed by Instagram location id
	 *
	 * @var array
	 */
	private static $term_id_from_location_id_cache = array();

	/**
	 * Holds a cache of normalized data
	 *
	 * @var array
	 */
	private static $data_cache = array();

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
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'daddio_after_instagram_inserted', array( __CLASS__, 'action_daddio_after_instagram_inserted' ), 10, 3 );
		add_action( 'location_edit_form_fields', array( __CLASS__, 'action_location_edit_form_fields' ), 11 );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'wp_ajax_daddio_location_sync', array( $this, 'action_ajax_daddio_location_sync' ) );
	}

	/**
	 * Setup taxonomies
	 */
	public function action_init() {
		$args = array(
			'labels'            => Daddio_Helpers::generate_taxonomy_labels( 'location', 'locations' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'location', array( Daddio_Instagram::$post_type ), $args );

		$args = array(
			'labels'            => Daddio_Helpers::generate_taxonomy_labels( 'zip code', 'zip codes' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'zip-code', array( Daddio_Instagram::$post_type ), $args );

		$args = array(
			'labels'            => Daddio_Helpers::generate_taxonomy_labels( 'state', 'states' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'state', array( Daddio_Instagram::$post_type ), $args );

		$args = array(
			'labels'            => Daddio_Helpers::generate_taxonomy_labels( 'county', 'counties' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'county', array( Daddio_Instagram::$post_type ), $args );

		$args = array(
			'labels'            => Daddio_Helpers::generate_taxonomy_labels( 'country', 'countries' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'country', array( Daddio_Instagram::$post_type ), $args );
	}

	/**
	 * Handle associating terms and post meta for location data for a newly inserted Instagram post
	 *
	 * @param  integer $post_id WordPress Post ID location data is to be associated with
	 * @param  object  $node    Normalized Instagram data
	 */
	public static function action_daddio_after_instagram_inserted( $post_id = 0, $node ) {
		$post    = get_post( $post_id );
		$post_id = $post->ID;
		if ( $post->post_type !== Daddio_Instagram::$post_type ) {
			return;
		}

		$location_id = 0;
		if ( ! empty( $node->location_id ) ) {
			$location_id = absint( $node->location_id );
		}
		update_post_meta( $post_id, 'instagram_location_id', $location_id );

		// If there is no location ID set then we can associate the Instagram post with the None location
		if ( $location_id === 0 ) {
			$args = array(
				'term_name'        => 'None',
				'term_description' => 'No location set',
				'taxonomy'         => 'location',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
			return;
		}

		// If the location id has a term_id, associate this Instagram post
		// Otherwise we need to note that this location ID needs its data synced
		static::associate_location_with_post( $location_id, $post_id );
	}

	/**
	 * Add form fields to the edit Location term screen for displaying meta data
	 *
	 * @param  WP_Term $term WordPress term being edited
	 */
	public static function action_location_edit_form_fields( $term ) {
		$location_data = static::get_location_data_by_term_id( $term->term_id );

		// Display link to Instagram location page
		if ( ! empty( $location_data->location_id ) ) {
			$url  = 'https://www.instagram.com/explore/locations/' . $location_data->location_id . '/';
			$link = '<a href="' . esc_url( $url ) . '" target="_blank">' . $location_data->location_id . '</a>';
			echo static::edit_form_field(
				'Instagram Location',
				$link
			);
		}

		// Display link to reverse geocode data for lat/long coordinates
		$query_args        = array(
			'format'         => 'json',
			'lat'            => $location_data->latitude,
			'lon'            => $location_data->longitude,
			'zoom'           => 80,
			'addressdetails' => 1,
		);
		$request_url       = add_query_arg( $query_args, 'https://nominatim.openstreetmap.org/reverse' );
		$request_link_text = $location_data->latitude . ', ' . $location_data->longitude;
		$request_link      = '<a href="' . esc_url( $request_url ) . '" target="_blank">' . $request_link_text . '</a>';
		echo static::edit_form_field(
			'Reverse Geocode',
			$request_link
		);

		// Object properties of the $location_data object mapped to a human-readable label
		$mapping = array(
			'website'           => 'Web Site',
			'phone'             => 'Phone',
			'street_address'    => 'Street Address',
			'city'              => 'City',
			'county'            => 'County',
			'state'             => 'State',
			'postcode'          => 'Post Code',
			'country'           => 'Country',
			'term_last_updated' => 'Last Updated',
		);
		foreach ( $mapping as $key => $label ) {
			if ( empty( $location_data->{ $key } ) ) {
				continue;
			}
			$val = $location_data->{ $key };
			switch ( $key ) {

				case 'website':
					$val = make_clickable( $val );
					break;

				case 'term_last_updated':
					$val = date( 'F j, Y g:i a', intval( $val ) );
					break;

			}
			echo static::edit_form_field(
				$label,
				$val
			);
		}
	}

	/**
	 * Helper for rendering form field markup for edit term screen
	 *
	 * @param  string $label Label for form field
	 * @param  string $value Value of form field
	 */
	public static function edit_form_field( $label = '', $value = '' ) {
		$context = array(
			'label' => $label,
			'value' => $value,
		);
		return Sprig::render( 'admin/location-term-form-field.twig', $context );
	}

	/**
	 * Add Location Sync submenu
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=instagram',
			'Location Sync',
			'Location Sync',
			'manage_options',
			'instagram-location-sync',
			array( $this, 'handle_location_sync_submenu' )
		);
	}

	/**
	 * Handle AAJX requests from the Location Sync sub menu
	 *
	 * @todo Add a nonce check to this
	 */
	public function action_ajax_daddio_location_sync() {
		if ( empty( $_POST['instagram-source'] ) ) {
			wp_send_json_fail( array( 'message' => 'No Instagram HTML provided' ) );
			die();
		}
		$html          = wp_unslash( $_POST['instagram-source'] );
		$json          = Daddio_Instagram::get_instagram_json_from_html( $html );
		$location_data = static::normalize_location_data( $json );
		if ( empty( $location_data->location_id ) ) {
			wp_send_json_fail( array( 'message' => 'Location ID not found!' ) );
			die();
		}

		// Get all posts with the same location ID and update them
		$args            = array(
			'post_type'      => Daddio_Instagram::$post_type,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_key'       => 'instagram_location_id',
			'meta_value'     => $location_data->location_id,
			'fields'         => 'ids',
		);
		$instagram_posts = new WP_Query( $args );
		if ( ! empty( $instagram_posts->posts ) ) {
			foreach ( $instagram_posts->posts as $post_id ) {
				static::associate_location_with_post( $location_data->location_id, $post_id, $location_data );
			}
		}
		$found_posts = number_format( $instagram_posts->found_posts );
		wp_send_json_success( array( 'message' => 'Success! Updated ' . $found_posts . ' Instagram posts.' ) );
		die();
	}

	/**
	 * Render the Location Sync submenu
	 */
	public function handle_location_sync_submenu() {
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

		wp_enqueue_script(
			'daddio-location-sync-submenu',
			get_template_directory_uri() . '/js/admin-location-sync-submenu.js',
			array( 'jquery' )
		);

		$context = array(
			'location_ids'       => $location_ids,
			'stale_location_ids' => $stale_location_ids,
		);
		Sprig::out( 'admin/instagram-location-sync-submenu.twig', $context );
	}

	/**
	 * Add a term if it doesn't exist and associate with a post ID
	 *
	 * @param array $args Arguments of the term to maybe create
	 *                    and the post ID to associate the term with
	 * @return integer Return the term ID if found or inserted, 0 if something went wrong
	 */
	public static function maybe_add_term_and_associate_with_post( $args = array() ) {
		$default_args = array(
			'term_name'        => '',
			'term_description' => '',
			'slug'             => '',
			'taxonomy'         => '',
			'post_id'          => 0,
			'append'           => false,
		);
		$args         = wp_parse_args( $args, $default_args );
		if ( ! is_numeric( $args['post_id'] ) ) {
			$args['post_id'] = 0;
		}

		$term      = get_term_by(
			$field = 'name',
			$args['term_name'],
			$args['taxonomy']
		);
		if ( $term === false ) {
			$term = wp_insert_term(
				$args['term_name'],
				$args['taxonomy'],
				array(
					'description' => $args['term_description'],
					'slug'        => $args['slug'],
				)
			);
			if ( is_wp_error( $term ) ) {
				wp_die( $term );
			}
			$term = (object) $term;
		}
		if ( isset( $term->term_id ) && $args['post_id'] > 0 ) {
			wp_set_object_terms(
				$args['post_id'],
				$term->term_id,
				$args['taxonomy'],
				$args['append']
			);
			return $term->term_id;
		}

		return 0;
	}

	/**
	 * Get the term ID for a given Instagram location ID
	 *
	 * @param  integer $location_id ID of Instagram location
	 * @return integer              WordPress term ID
	 */
	public static function get_term_id_from_location_id( $location_id = 0 ) {
		global $wpdb;

		if ( ! empty( static::$term_id_from_location_id_cache[ $location_id ] ) ) {
			return static::$term_id_from_location_id_cache[ $location_id ];
		}

		$meta_key   = 'instagram-location-id';
		$meta_value = $location_id;

		$term_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT `term_id`
				FROM `{$wpdb->termmeta}`
				WHERE `meta_key` = %s
				AND `meta_value` = %s
				LIMIT 0,1;
			",
				$meta_key,
				$meta_value
			)
		);
		$term_id = intval( $term_id );
		if ( $term_id ) {
			static::$term_id_from_location_id_cache[ $location_id ] = $term_id;
			return $term_id;
		}

		// Try and see if there is a term slug that contains the location id
		$term_id = $wpdb->get_var(
			$wpdb->prepare(
				"
				SELECT `term_id`
				FROM `{$wpdb->terms}`
				WHERE `slug` LIKE %s
				LIMIT 0,1;
			",
				'%' . $wpdb->esc_like( $location_id ) . '%'
			)
		);
		$term_id = intval( $term_id );
		if ( $term_id ) {
			static::$term_id_from_location_id_cache[ $location_id ] = $term_id;
			return $term_id;
		}

		return 0;
	}

	/**
	 * Normalize location data from a given Location object node
	 *
	 * @param  Object|false  $node Node object from the Instagram API
	 * @return object              JSON data found from scraping the Instagram Location page
	 */
	public static function normalize_location_data( $node = false, $args = array() ) {
		$defaults = array(
			'add_term_if_doesnt_exist' => false,
		);
		$args     = wp_parse_args( $args, $defaults );

		$output = array(
			'location_id'       => '',
			'name'              => '',
			'slug'              => '',
			'blurb'             => '',
			'website'           => '',
			'phone'             => '',

			'latitude'          => '',
			'longitude'         => '',

			'street_address'    => '',
			'city'              => '',
			'county'            => '',
			'state'             => '',
			'postcode'          => '',
			'country'           => '',
			'country_code'      => '',

			'term_id'           => 0,
			'term_last_updated' => false,
		);

		if ( ! $node ) {
			return (object) $output;
		}

		// Check to make sure a raw Instagram JSON response isn't being passed in
		if ( ! empty( $node->entry_data->LocationsPage[0]->graphql->location ) ) {
			$node = $node->entry_data->LocationsPage[0]->graphql->location;
		}

		// Map our node object properties to our output object properties
		$mapping = array(
			'id'      => 'location_id',
			'name'    => 'name',
			'slug'    => 'slug',
			'blurb'   => 'blurb',
			'website' => 'website',
			'phone'   => 'phone',
			'lat'     => 'latitude',
			'lng'     => 'longitude',
		);
		foreach ( $mapping as $node_key => $output_key ) {
			if ( ! empty( $node->{$node_key} ) ) {
				$output[ $output_key ] = $node->{$node_key};
			}
		}

		if ( ! empty( $node->address_json ) ) {
			$address_json = json_decode( $node->address_json );

			if ( ! empty( $address_json->street_address ) ) {
				$output['street_address'] = $address_json->street_address;
			}

			if ( ! empty( $address_json->city_name ) ) {
				$city           = explode( ',', $address_json->city_name );
				$output['city'] = $city[0];
			}

			if ( ! empty( $address_json->zip_code ) ) {
				$output['postcode'] = $address_json->zip_code;
			}

			if ( ! empty( $address_json->country_code ) ) {
				$output['country_code'] = $address_json->country_code;
			}
		}

		if ( ! empty( $output['latitude'] ) && ! empty( $output['longitude'] ) ) {
			$data = static::reverse_geocode( $output['latitude'], $output['longitude'] );

			if ( ! empty( $data->city ) && empty( $output['city'] ) ) {
				$output['city'] = $data->city;
			}

			if ( ! empty( $data->county ) && empty( $output['county'] ) ) {
				$output['county'] = $data->county;
			}

			if ( ! empty( $data->state ) && empty( $output['state'] ) ) {
				$output['state'] = $data->state;
			}

			if ( ! empty( $data->country ) && empty( $output['country'] ) ) {
				$output['country'] = $data->country;
			}

			if ( ! empty( $data->postcode ) && empty( $output['postcode'] ) ) {
				$output['postcode'] = $data->postcode;
			}
		}

		if ( ! empty( $output['location_id'] ) ) {
			$term_id = static::get_term_id_from_location_id( $output['location_id'] );
			if ( absint( $term_id ) > 0 ) {
				$output['term_id']           = $term_id;
				$output['term_last_updated'] = get_term_meta( $term_id, 'instagram-last-updated', true );
				$output['term_last_updated'] = intval( $output['term_last_updated'] );
			}
		}
		return (object) $output;
	}

	/**
	 * Get location data for a given location ID
	 *
	 * @param string $location_id The Instagram location ID to get data for
	 * @param array $args         Arguments for modifying what is returned
	 */
	public static function get_location_data_by_location_id( $location_id = '', $args = array() ) {
		$defaults = array(
			'term_staleness' => 3 * DAY_IN_SECONDS,
			'update_term'    => true,
		);
		$args     = wp_parse_args( $args, $defaults );

		// Check if the data is cached
		if ( ! empty( static::$data_cache[ $location_id ] ) ) {
			return static::$data_cache[ $location_id ];
		}

		$output = array(
			'location_id'       => $location_id,
			'name'              => '',
			'slug'              => '',
			'blurb'             => '',
			'website'           => '',
			'phone'             => '',

			'latitude'          => '',
			'longitude'         => '',

			'street_address'    => '',
			'city'              => '',
			'county'            => '',
			'state'             => '',
			'postcode'          => '',
			'country'           => '',
			'country_code'      => '',

			'term_id'           => 0,
			'term_last_updated' => false,
		);

		$term_id       = static::get_term_id_from_location_id( $location_id );
		$is_term_stale = false;
		if ( absint( $term_id ) > 0 ) {
			$output['term_id']           = $term_id;
			$output['term_last_updated'] = get_term_meta( $term_id, 'instagram-last-updated', true );
			$output['term_last_updated'] = intval( $output['term_last_updated'] );

			if ( $output['term_last_updated'] < time() - $args['term_staleness'] ) {
				update_term_meta( $term_id, 'location-needs-updating', $location_id );
			}
			$output                             = static::get_location_data_by_term_id( $term_id );
			static::$data_cache[ $location_id ] = (object) $output;
			return (object) $output;
		}

		// Note that we need to create a location term via a separate process
		if ( $output['term_id'] === 0 && $args['update_term'] ) {
			$option_key               = 'instagram-location-ids-needing-terms';
			$instagram_location_ids   = get_option( $option_key, array() );
			$instagram_location_ids[] = $location_id;
			$instagram_location_ids   = array_unique( $instagram_location_ids );
			update_option( $option_key, $instagram_location_ids, $autoload = false );
		}

		static::$data_cache[ $location_id ] = (object) $output;
		return (object) $output;
	}

	/**
	 * Get meta data for given location term
	 *
	 * @param  string $term_id WordPress Term or term ID
	 * @return array           Term meta data
	 */
	public static function get_location_data_by_term_id( $term_id = '' ) {
		$the_term = false;
		$the_term = get_term( $term_id, 'location' );
		if ( is_object( $the_term ) && isset( $term->term_id ) ) {
				$term_id = $term->term_id;
		}

		$output = array(
			'location_id'       => '',
			'name'              => '',
			'slug'              => '',
			'blurb'             => '',
			'website'           => '',
			'phone'             => '',

			'latitude'          => '',
			'longitude'         => '',

			'street_address'    => '',
			'city'              => '',
			'county'            => '',
			'state'             => '',
			'postcode'          => '',
			'country'           => '',
			'country_code'      => '',

			'term_id'           => $term_id,
			'term_last_updated' => false,
		);

		if ( ! $term_id && ! $the_term ) {
			return (object) $output;
		}
		$output['name'] = apply_filters( 'the_title', $the_term->name );
		$term_meta      = get_term_meta( $term_id );
		foreach ( array_keys( $output ) as $key ) {
			if ( ! empty( $term_meta[ $key ] ) && ! empty( $term_meta[ $key ][0] ) ) {
				$output[ $key ] = $term_meta[ $key ][0];
			}
		}
		if ( ! empty( $term_meta['instagram-location-id'][0] ) ) {
			$output['location_id'] = intval( $term_meta['instagram-location-id'][0] );
		}
		if ( ! empty( $term_meta['instagram-last-updated'][0] ) ) {
			$output['term_last_updated'] = intval( $term_meta['instagram-last-updated'][0] );
		}

		return (object) $output;
	}

	/**
	 * Associate location data terms with a given post ID
	 *
	 * @param integer $location_id The location ID to get data for
	 * @param integer $post_id     The WordPress Post ID to associate location terms with
	 */
	public static function associate_location_with_post( $location_id = 0, $post_id = 0, $location_data = false ) {
		// Verify $post_id is a valid WordPress post or -1 if we want to add all of the terms and not associate with a post
		if ( $post_id !== -1 ) {
			$post    = get_post( $post_id );
			$post_id = $post->ID;
		}
		if ( ! $post_id ) {
			return;
		}

		if ( empty( $location_data ) ) {
			$location_data = static::get_location_data_by_location_id( $location_id );
		}

		// var_dump( $location_data );
		// die();

		if ( ! empty( $location_data->latitude ) ) {
			update_post_meta( $post_id, 'latitude', $location_data->latitude );
		}
		if ( ! empty( $location_data->longitude ) ) {
			update_post_meta( $post_id, 'longitude', $location_data->longitude );
		}

		if ( ! empty( $location_data->name ) ) {
			$custom_slug      = $location_data->slug . '-' . $location_data->location_id;
			$args             = array(
				'term_name'        => $location_data->name,
				'term_description' => $location_data->blurb,
				'slug'             => $custom_slug,
				'taxonomy'         => 'location',
				'post_id'          => $post_id,
			);
			$location_term_id = static::maybe_add_term_and_associate_with_post( $args );
			if ( ! empty( $location_term_id ) ) {
				$blacklisted_meta_keys = array(
					'name',
					'location_id',
					'term_id',
					'term_last_updated',
				);
				foreach ( $location_data as $meta_key => $meta_value ) {
					if ( in_array( $meta_key, $blacklisted_meta_keys, true ) ) {
						continue;
					}
					if ( empty( $meta_value ) ) {
						continue;
					}
					update_term_meta( $location_term_id, $meta_key, $meta_value );
				}
				update_term_meta( $location_term_id, 'instagram-location-id', $location_data->location_id );
				$term_last_updated = time();
				update_term_meta( $location_term_id, 'instagram-last-updated', $term_last_updated );
				delete_term_meta( $location_term_id, 'location-needs-updating' );

				// Remove location ID from the list of location IDs needing terms
				$option_key                 = 'instagram-location-ids-needing-terms';
				$location_ids_needing_terms = get_option( $option_key, array() );
				$location_ids_needing_terms = array_diff(
					$location_ids_needing_terms,
					array( $location_data->location_id )
				);
				update_option( $option_key, $location_ids_needing_terms, $autoload = false );
			}
		}

		if ( ! empty( $location_data->postcode ) ) {
			$description = $location_data->city . ', ' . static::get_state_abbreviation( $location_data->state );
			$args        = array(
				'term_name'        => $location_data->postcode,
				'term_description' => $description,
				'taxonomy'         => 'zip-code',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data->state ) ) {
			$args = array(
				'term_name'        => $location_data->state,
				'term_description' => '',
				'taxonomy'         => 'state',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data->county ) ) {
			$term_name          = str_replace( 'County', '', $location_data->county );
			$state_abbreviation = static::get_state_abbreviation( $location_data->state );
			$term_slug          = $term_name . ' ' . $state_abbreviation;
			$description        = $location_data->county . ', ' . $state_abbreviation;
			$args               = array(
				'term_name'        => $term_name,
				'slug'             => $term_slug,
				'term_description' => $description,
				'taxonomy'         => 'county',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data->country ) ) {
			$args = array(
				'term_name'        => $location_data->country,
				'term_description' => '',
				'taxonomy'         => 'country',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}
	}

	/**
	 * Reverse geocode a pair of lat/long coordinates
	 *
	 * @param  string $lat  Latitude coordinates
	 * @param  string $long Longitude coordinates
	 * @return array        Geocode data
	 */
	public static function reverse_geocode( $lat = '', $long = '' ) {
		$output = array(
			'locality'     => '',
			'city'         => '',
			'county'       => '',
			'state'        => '',
			'postcode'     => '',
			'country'      => '',
			'country_code' => '',
		);
		if ( empty( $lat ) || empty( $long ) ) {
			return (object) $output;
		}
		$query_args = array(
			'format'         => 'json',
			'lat'            => $lat,
			'lon'            => $long,
			'zoom'           => 80,
			'addressdetails' => 1,
		);
		$request    = add_query_arg( $query_args, 'https://nominatim.openstreetmap.org/reverse' );
		$response   = wp_remote_get( $request );
		$body       = $response['body'];
		if ( ! is_string( $body ) || empty( $body ) ) {
			return (object) $output;
		}
		$json = json_decode( $response['body'] );
		if ( isset( $json->address ) ) {
			$output = wp_parse_args( $json->address, $output );
		}
		if ( isset( $output['postcode'] ) ) {
			// Get city/state data via zip code
			$request  = 'https://api.zippopotam.us/us/' . $output['postcode'];
			$response = wp_remote_get( $request );
			$body     = $response['body'];
			if ( is_string( $body ) && ! empty( $body ) ) {
				$json = json_decode( $body );
				if ( isset( $json->places[0]->{'place name'} ) ) {
					$output['city'] = $json->places[0]->{'place name'};
				}
				if ( isset( $json->places[0]->state ) ) {
					$output['state'] = $json->places[0]->state;
				}
			}
		}
		return (object) $output;
	}

	/**
	 * Given a state return the state abbreviation
	 *
	 * @param  string $state Full state name
	 * @return string|False  State abbreviation or false if not found
	 */
	public static function get_state_abbreviation( $state = '' ) {
		$key = ucwords( strtolower( $state ) );
		// via https://gist.github.com/maxrice/2776900#gistcomment-1172963
		$states = array(
			'Alabama'              => 'AL',
			'Alaska'               => 'AK',
			'Arizona'              => 'AZ',
			'Arkansas'             => 'AR',
			'California'           => 'CA',
			'Colorado'             => 'CO',
			'Connecticut'          => 'CT',
			'Delaware'             => 'DE',
			'District of Columbia' => 'DC',
			'Florida'              => 'FL',
			'Georgia'              => 'GA',
			'Hawaii'               => 'HI',
			'Idaho'                => 'ID',
			'Illinois'             => 'IL',
			'Indiana'              => 'IN',
			'Iowa'                 => 'IA',
			'Kansas'               => 'KS',
			'Kentucky'             => 'KY',
			'Louisiana'            => 'LA',
			'Maine'                => 'ME',
			'Maryland'             => 'MD',
			'Massachusetts'        => 'MA',
			'Michigan'             => 'MI',
			'Minnesota'            => 'MN',
			'Mississippi'          => 'MS',
			'Missouri'             => 'MO',
			'Montana'              => 'MT',
			'Nebraska'             => 'NE',
			'Nevada'               => 'NV',
			'New Hampshire'        => 'NH',
			'New Jersey'           => 'NJ',
			'New Mexico'           => 'NM',
			'New York'             => 'NY',
			'North Carolina'       => 'NC',
			'North Dakota'         => 'ND',
			'Ohio'                 => 'OH',
			'Oklahoma'             => 'OK',
			'Oregon'               => 'OR',
			'Pennsylvania'         => 'PA',
			'Rhode Island'         => 'RI',
			'South Carolina'       => 'SC',
			'South Dakota'         => 'SD',
			'Tennessee'            => 'TN',
			'Texas'                => 'TX',
			'Utah'                 => 'UT',
			'Vermont'              => 'VT',
			'Virginia'             => 'VA',
			'Washington'           => 'WA',
			'West Virginia'        => 'WV',
			'Wisconsin'            => 'WI',
			'Wyoming'              => 'WY',
		);

		if ( isset( $states[ $key ] ) ) {
			return $states[ $key ];
		}
		return false;
	}

}
Daddio_Instagram_Locations::get_instance();
