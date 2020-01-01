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
		add_action( 'wp_ajax_daddio_private_location_sync', array( $this, 'action_ajax_daddio_private_location_sync' ) );
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
	 * Handle associating terms and post meta for location data for a given Instagram node
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

		$location_data = false;
		if ( ! empty( $node->location_id ) ) {
			$location_data = static::get_location_data_by_location_id( $node->location_id );
		}

		if ( empty( $location_data->location_id ) ) {
			$args = array(
				'term_name'        => 'None',
				'term_description' => 'No location set',
				'taxonomy'         => 'location',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
			update_post_meta( $post_id, 'instagram_location_id', 0 );
			return;
		}

		update_post_meta( $post_id, 'instagram_location_id', $location_data->location_id );

		if ( ! empty( $location_data->term_id ) ) {
			wp_set_object_terms(
				$post_id,
				$location_data->term_id,
				'location',
				$append = true
			);
		}

		if ( ! empty( $location_data->latitude ) ) {
			update_post_meta( $post_id, 'latitude', $location_data->latitude );
		}
		if ( ! empty( $location_data->longitude ) ) {
			update_post_meta( $post_id, 'longitude', $location_data->longitude );
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
		// TODO Use Sprig for this
		ob_start();
		?>
		<tr class="form-field form-required term-name-wrap">
			<th scope="row">
				<label><?php echo $label; ?></label>
			</th>
			<td>
				<?php echo $value; ?>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Add Private Sync submenu
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=instagram',
			'Private Location Sync',
			'Private Location Sync',
			'manage_options',
			'instagram-private-location-sync',
			array( $this, 'handle_private_location_sync_submenu' )
		);
	}
	public function handle_private_location_sync_submenu() {
		// Get all published instagram posts that have a needs-location-data meta key set...
		$args    = array(
			'post_type'      => 'instagram',
			'post_parent'    => 0,
			'post_status'    => 'public',
			'posts_per_page' => 15,
			'meta_query'     => array(
				array(
					'key'     => 'needs-location-data',
					'value'   => '1',
					'compare' => '=',
				),
			),
		);
		$query   = new WP_Query( $args );
		$context = array(
			'found_posts' => $query->found_posts,
			'posts'       => array(),
		);
		foreach ( $query->posts as $post ) {
			$post_id            = $post->ID;
			$guid               = $post->guid;
			$parts              = explode( 'com/p/', $guid );
			$code               = $parts[1];
			$code               = str_replace( '/', '', $code );
			$featured_image_id  = get_post_thumbnail_id( $post_id );
			$img_attrs          = array(
				'class' => 'alignleft',
				'alt'   => 'Instagram Thumbnail',
			);
			$thumbnail          = wp_get_attachment_image( $featured_image_id, 'thumbnail', false, $img_attrs );
			$context['posts'][] = (object) array(
				'thumbnail'     => $thumbnail,
				'instagram_url' => $guid,
				'post_id'       => $post_id,
				'edit_link'     => get_edit_post_link( $post_id, 'url' ),
			);
		}
		wp_enqueue_script(
			'daddio-private-location-sync-submenu',
			get_template_directory_uri() . '/js/admin-private-location-sync-submenu.js',
			array( 'jquery' )
		);
		echo $this->render_private_location_sync_submenu( $context );
	}
	public function render_private_location_sync_submenu( $data = array() ) {
		$defaults = array(
			'found_posts' => 0,
			'posts'       => array(),
		);
		$x        = wp_parse_args( $data, $defaults );
		ob_start();
		?>
		<style>
			.post {
				overflow: auto;
				padding: 15px;
				margin-bottom: 30px;
			}
			.post.loading {
				opacity: 0.35;
			}
			.post.success {
				background-color: green;
				color: #fff;
			}
			.post.fail {
				background-color: red;
				color: #fff;
			}
			.post .text-fields {
				overflow: auto;
				padding: 0 15px;
			}
			.post .instagram-url,
			.post textarea {
				margin-bottom: 15px;
				width: 99%;
			}
		</style>
		<h1>Privte Instagram Posts that Need Location Data Synced</h1>
		<?php foreach ( $x['posts'] as $post ) : ?>
			<div class="post">
				<a href="<?php echo esc_url( $post->edit_link ); ?>" target="_blank">
					<?php echo $post->thumbnail; ?>
				</a>
				<div class="text-fields">
					<input type="text" class="instagram-url" value="view-source:<?php echo esc_url( $post->instagram_url ); ?>" onfocus="this.select();" readonly>
					<input type="hidden" class="post-id" name="post-id" value="<?php echo absint( $post->post_id ); ?>">
					<textarea rows="5" class="instagram-source"></textarea>
				</div>
			</div>
		<?php endforeach; ?>
		<p><?php echo $x['found_posts']; ?> private Instagram posts need location data</p>
		<?php
		return ob_get_clean();
	}

	public function action_ajax_daddio_private_location_sync() {
		$post_id = absint( $_POST['post-id'] );
		if ( ! $post_id ) {
			wp_send_json_fail( array( 'message' => 'Bad post-id' ) );
		}
		$post = get_post( $post_id );
		while ( $post->post_parent !== 0 ) {
			$post_id = $post->post_parent;
			$post    = get_post( $post->post_parent );
		}
		$args     = array(
			'post_parent' => $post_id,
			'post_type'   => 'instagram',
			'fields'      => 'ids',
		);
		$post_ids = get_children( $args );
		if ( ! is_array( $post_ids ) ) {
			$post_ids = array();
		}
		$post_ids[] = $post_id;
		$html       = wp_unslash( $_POST['instagram-source'] );
		$json       = Daddio_Instagram::get_instagram_json_from_html( $html );
		if ( isset( $json->graphql->shortcode_media ) ) {
			$node = $json->graphql->shortcode_media;
			$node = Daddio_Instagram::normalize_instagram_data( $node );
			foreach ( $post_ids as $post_id ) {
				do_action( 'daddio_after_instagram_inserted', $post_id, $node );
				wp_remove_object_terms( $post_id, array( 'none' ), 'location' );
				delete_post_meta( $post_id, 'needs-location-data' );
			}
		}
		wp_send_json_success( array( 'message' => 'Success!' ) );
		die();
	}

	/**
	 * Add a term if it doesn't exist and associate with a post ID
	 *
	 * @param array $args Arguments of the term to maybe create
	 *                    and the post ID to associate the term with
	 */
	public static function maybe_add_term_and_associate_with_post( $args = array() ) {
		$default_args = array(
			'term_name'        => '',
			'term_description' => '',
			'slug'             => '',
			'taxonomy'         => '',
			'post_id'          => 0,
		);
		$args         = wp_parse_args( $args, $default_args );
		$term         = get_term_by(
			$field    = 'name',
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
			if ( ! is_wp_error( $term ) ) {
				$term = (object) $term;
			}
		}
		if ( isset( $term->term_id ) ) {
			wp_set_object_terms(
				$args['post_id'],
				$term->term_id,
				$args['taxonomy'],
				$append = true
			);
		}
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
	 * Fetch HTML for a given Instagram location ID
	 *
	 * @param  string $location_id Instagram Location ID to fetch data for
	 * @return object              JSON data found from scraping the Instagram Location page
	 */
	public static function fetch_location_data_by_location_id( $location_id = '' ) {
		$output = array(
			'location_id'    => $location_id,
			'name'           => '',
			'slug'           => '',
			'blurb'          => '',
			'website'        => '',
			'phone'          => '',

			'latitude'       => '',
			'longitude'      => '',

			'street_address' => '',
			'city'           => '',
			'county'         => '',
			'state'          => '',
			'postcode'       => '',
			'country'        => '',
			'country_code'   => '',
		);

		if ( ! $location_id ) {
			return (object) $output;
		}

		$output['latitude']  = $location->getLat();
		$output['longitude'] = $location->getLng();
		$output['name']      = $location->getName();
		$output['slug']      = $location->getSlug();

		$location_data = Daddio_Helpers::get_protected_object_property( $location, 'data' );

		$mapping = array(
			'blurb',
			'website',
			'phone',
		);
		foreach ( $mapping as $key ) {
			if ( ! empty( $location_data[ $key ] ) ) {
				$output[ $key ] = $location_data[ $key ];
			}
		}

		if ( ! empty( $location_data['address_json'] ) ) {
			$address_json = json_decode( $location_data['address_json'] );

			if ( ! empty( $address_json->street_address ) ) {
				$output['street_address'] = $address_json->street_address;
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
		return (object) $output;
	}

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
			$output['term_last_updated'] = get_term_meta( $term_id, 'term_last_updated', true );
			$output['term_last_updated'] = intval( $output['term_last_updated'] );

			if ( $output['term_last_updated'] < time() - $args['term_staleness'] ) {
				update_term_meta( $term_id, 'location_needs_updating', true );
			}
			$output = static::get_location_data_by_term_id( $term_id );
			static::$data_cache[ $location_id ] = (object) $output;
			return (object) $output;
		}

		// Pretty sure the rest of this code will be pointless since we can't fetch location data from Instagram
		$location_data = static::fetch_location_data_by_location_id( $location_id );
		if ( ! empty( $location_data ) ) {
			foreach ( $location_data as $key => $val ) {
				$output[ $key ] = $val;
			}
		}

		if ( $output['term_id'] === 0 && $args['update_term'] ) {
			$custom_slug = $output['slug'] . '-' . $output['location_id'];
			$term_args   = array(
				'description' => $output['blurb'],
				'slug'        => $custom_slug,
			);
			$term        = wp_insert_term( $output['name'], $taxonomy = 'location', $term_args );
			if ( is_array( $term ) && ! empty( $term['term_id'] ) ) {
				$output['term_id'] = $term['term_id'];
				$is_term_stale     = true;
			}
		}

		if (
			! empty( $output['term_id'] )
			&& $is_term_stale
			&& $args['update_term']
		) {
			$blacklisted_meta_keys = array(
				'name',
				'location_id',
				'term_id',
				'term_last_updated',
			);
			foreach ( $output as $meta_key => $meta_value ) {
				if ( in_array( $meta_key, $blacklisted_meta_keys, true ) ) {
					continue;
				}
				update_term_meta( $output['term_id'], $meta_key, $meta_value );
			}
			update_term_meta( $output['term_id'], 'instagram-location-id', $output['location_id'] );
			$term_last_updated = time();
			update_term_meta( $output['term_id'], 'instagram-last-updated', $term_last_updated );
			$output['term_last_updated'] = $term_last_updated;
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
