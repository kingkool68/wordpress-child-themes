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
	private static $normalized_data_cache = array();

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

		/**
		 * Looks like $node is usually a normalized instagram post node not a location page node. We need to add some logic to the normalize_node_data method to check what kind of node it is and then normalize the data as needed
		 */
		$location_data = static::normalize_node_data( $node );

		if ( empty( $location_data->instagram_location_id ) ) {
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
		// If there is no term ID, insert it
		// If the term data is stale, update it
		if ( ! empty( $location_data['term_id'] ) ) {
			wp_set_object_terms(
				$post_id,
				$location_data['term_id'],
				'location',
				$append = true
			);
			update_post_meta( $post_id, 'instagram_location_id', $location_data['instagram_location_id'] );
		}

		if ( ! empty( $location_data['latitude'] ) ) {
			update_post_meta( $post_id, 'latitude', $location_data['latitude'] );
		}
		if ( ! empty( $location_data['longitude'] ) ) {
			update_post_meta( $post_id, 'longitude', $location_data['longitude'] );
		}

		if ( ! empty( $location_data['postcode'] ) ) {
			$description = $location_data['city'] . ', ' . static::get_state_abbreviation( $location_data['state'] );
			$args        = array(
				'term_name'        => $location_data['postcode'],
				'term_description' => $description,
				'taxonomy'         => 'zip-code',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data['state'] ) ) {
			$args = array(
				'term_name'        => $location_data['state'],
				'term_description' => '',
				'taxonomy'         => 'state',
				'post_id'          => $post_id,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data['county'] ) ) {
			$term_name          = str_replace( 'County', '', $location_data['county'] );
			$state_abbreviation = static::get_state_abbreviation( $location_data['state'] );
			$term_slug          = $term_name . ' ' . $state_abbreviation;
			$description        = $location_data['county'] . ', ' . $state_abbreviation;
			$args               = array(
				'term_name'        => $term_name,
				'term_description' => $description,
				'taxonomy'         => 'county',
				'post_id'          => $post_id,
				'slug'             => $term_slug,
			);
			static::maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data['country'] ) ) {
			$args = array(
				'term_name'        => $location_data['country'],
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
		$location_data = static::get_location_data( $term );

		// Display link to Instagram location page
		if ( ! empty( $location_data['id'] ) ) {
			$url  = 'https://www.instagram.com/explore/locations/' . $location_data['id'] . '/';
			$link = '<a href="' . esc_url( $url ) . '" target="_blank">' . $location_data['id'] . '</a>';
			echo static::edit_form_field(
				'Instagram Location',
				$link
			);
		}

		// Display link to reverse geocode data for lat/long coordinates
		$query_args        = array(
			'format'         => 'json',
			'lat'            => $location_data['lat'],
			'lon'            => $location_data['lng'],
			'zoom'           => 80,
			'addressdetails' => 1,
		);
		$request_url       = add_query_arg( $query_args, 'https://nominatim.openstreetmap.org/reverse' );
		$request_link_text = $location_data['lat'] . ', ' . $location_data['lng'];
		$request_link      = '<a href="' . esc_url( $request_url ) . '" target="_blank">' . $request_link_text . '</a>';
		echo static::edit_form_field(
			'Reverse Geocode',
			$request_link
		);

		// Display Instagram location meta data
		$blacklisted_keys = array( 'id', 'name', 'has_public_page' );
		$meta_data        = array();
		foreach ( $location_data as $key => $val ) {
			if ( in_array( $key, $blacklisted_keys, true ) ) {
				continue;
			}
			if ( ! empty( $val ) ) {
				$meta_data[] = $key . ': ' . $val;
			}
		}
		echo static::edit_form_field(
			'Meta Data',
			implode( '<br>', $meta_data )
		);
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

	public static function normalize_node_data( $node = false ) {
		if ( ! $node ) {
			return false;
		}

		// Check if the data has already been normalized and is cached
		if ( ! empty( $node->location->id ) && ! empty( static::$normalized_data_cache[ $node->location->id ] ) ) {
			return static::$normalized_data_cache[ $node->location->id ];
		}

		// Check if $node is already normalized
		if ( isset( $node->instagram_location_id ) && isset( $node->_normalized ) && $node->_normalized ) {
			return $node;
		}

		if ( ! empty( $node->location_id ) ) {
			// $node = static::fetch_instagram_location_by_id( $node->location_id );
		}

		$output = array(
			'_normalized'            => true,
			'instagram_location_id'  => '',
			'name'                   => '',
			'slug'                   => '',
			'has_public_page'        => '',
			'instagram_location_url' => '',

			'latitude'               => '',
			'longitude'              => '',

			'street_address'         => '',
			'city'                   => '',
			'county'                 => '',
			'state'                  => '',
			'postcode'               => '',
			'country'                => '',
			'country_code'           => '',

			'blurb'                  => '',
			'website'                => '',
			'phone'                  => '',

			'term_id'                => 0,
			'term_last_updated'      => false,
		);
		if ( ! empty( $node->location ) ) {

			if ( ! empty( $node->location->id ) ) {
				$output['instagram_location_id'] = $node->location->id;
			}

			if ( ! empty( $node->location->has_public_page ) ) {
				$output['has_public_page'] = $node->location->has_public_page;
			}

			if ( ! empty( $node->location->name ) ) {
				$output['name'] = $node->location->name;
			}

			if ( ! empty( $node->location->slug ) ) {
				$output['slug'] = $node->location->slug;
			}

			if ( ! empty( $node->location->address_json ) ) {
				$address_json = json_decode( $node->location->address_json );

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
		}

		if ( ! empty( $output['instagram_location_id'] ) ) {
			$term_id = static::get_term_id_from_location_id( $output['instagram_location_id'] );
			if ( absint( $term_id ) > 0 ) {
				$output['term_id']           = $term_id;
				$output['term_last_updated'] = get_term_meta( $term_id, 'instagram-last-updated', true );
			}

			$output['instagram_location_url'] = 'https://www.instagram.com/explore/locations/' . $output['instagram_location_id'] . '/';

			$location_data = static::fetch_instagram_location_by_id( $output['instagram_location_id'] );
			if ( ! empty( $location_data ) ) {
				foreach ( $location_data as $key => $val ) {
					$output[ $key ] = $val;
				}
			}
		}

		if ( ! empty( $output['instagram_location_id'] ) ) {
			static::$normalized_data_cache[ $output['instagram_location_id'] ] = $output;
		}
		return $output;
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
		return 0;
	}

	/**
	 * Fetch HTML for a given Instagram location ID
	 *
	 * @param  string $location_id Instagram Location ID to fetch data for
	 * @return object              JSON data found from scraping the Instagram Location page
	 */
	public static function fetch_instagram_location_by_id( $location_id = '' ) {
		$output = array(
			'latitude'       => '',
			'longitude'      => '',

			'street_address' => '',
			'city'           => '',
			'county'         => '',
			'state'          => '',
			'postcode'       => '',
			'country'        => '',
			'country_code'   => '',

			'blurb'          => '',
			'website'        => '',
			'phone'          => '',
		);

		if ( ! $location_id ) {
			return (object) $output;
		}

		$scraper             = Daddio_Instagram::get_instagram_scraper();
		$location            = $scraper->getLocationById( $location_id );
		$output['latitude']  = $location->getLat();
		$output['longitude'] = $location->getLng();

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

	/**
	 * Get meta data for given location term
	 *
	 * @param  string $term WordPress Term or term ID
	 * @return array        Term meta data
	 */
	public static function get_location_data( $term = '' ) {
		$term_id = false;
		if ( is_numeric( $term ) ) {
			$term_id = $term;
		} else {
			$term = get_term( $term, 'location' );
			if ( is_object( $term ) && isset( $term->term_id ) ) {
				$term_id = $term->term_id;
			}
		}
		if ( ! $term_id ) {
			return array();
		}
		return get_term_meta( $term_id, 'location-data', true );
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
