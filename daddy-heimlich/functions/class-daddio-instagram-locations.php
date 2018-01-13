<?php
class Daddio_Instagram_Locations {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			// Late static binding (PHP 5.3+)
			$instance = new static();
			$instance->setup();
			$instance->setup_hooks();
		}
		return $instance;
	}

	public function setup() {
		$this->instagram_class = Daddio_Instagram::get_instance();
	}

	/**
	 * Hook in to WordPress via actions and filters
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'daddio_after_instagram_inserted', array( $this, 'action_daddio_after_instagram_inserted' ), 10, 2 );
		add_action( 'location_edit_form_fields', array( $this, 'action_location_edit_form_fields' ), 11 );

		add_filter( 'daddio_pre_insert_instagram_post_args', array( $this, 'filter_daddio_pre_insert_instagram_post_args' ), 10, 2 );
	}

	/**
	 * Setup taxonomies
	 */
	function action_init() {
		$args = array(
			'labels'            => daddio_generate_taxonomy_labels( 'location', 'locations' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'location', array( 'instagram' ), $args );

		$args = array(
			'labels'            => daddio_generate_taxonomy_labels( 'zip code', 'zip codes' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'zip-code', array( 'instagram' ), $args );

		$args = array(
			'labels'            => daddio_generate_taxonomy_labels( 'state', 'states' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'state', array( 'instagram' ), $args );

		$args = array(
			'labels'            => daddio_generate_taxonomy_labels( 'county', 'counties' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'county', array( 'instagram' ), $args );

		$args = array(
			'labels'            => daddio_generate_taxonomy_labels( 'country', 'countries' ),
			'hierarchical'      => false,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'country', array( 'instagram' ), $args );
	}

	public function action_daddio_after_instagram_inserted( $post_id, $node ) {
		$post = get_post( $post_id );
		if ( $post->post_type != 'instagram' ) {
			return;
		}
		$location_data = $this->get_location_data_from_node( $node );

		if ( empty( $location_data ) ) {
			$description = 'No location set';
			$args = array(
				'term_name'        => 'None',
				'term_description' => $description,
				'taxonomy'         => 'location',
				'post_id'          => $post_id,
			);
			$this->maybe_add_term_and_associate_with_post( $args );
			update_post_meta( $post_id, 'instagram_location_id', 0 );
			return;
		}

		if ( ! empty( $location_data['id'] ) ) {
			// Get the term id for the location
			$term_id = $this->get_term_id_from_location_id( $location_data['id'] );
			if ( $term_id ) {
				wp_set_object_terms(
					$post_id,
					$term_id,
					'location',
					$append = true
				);
			}
		}

		if ( ! empty( $location_data['postcode'] ) ) {
			$description = $location_data['city'] . ', ' . $this->get_state_abbreviation( $location_data['state'] );
			$args = array(
				'term_name'        => $location_data['postcode'],
				'term_description' => $description,
				'taxonomy'         => 'zip-code',
				'post_id'          => $post_id,
			);
			$this->maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data['state'] ) ) {
			$args = array(
				'term_name'        => $location_data['state'],
				'term_description' => '',
				'taxonomy'         => 'state',
				'post_id'          => $post_id,
			);
			$this->maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data['county'] ) ) {
			$term_name = str_replace( 'County', '', $location_data['county'] );
			$state_abbreviation = $this->get_state_abbreviation( $location_data['state'] );
			$term_slug = $term_name . ' ' . $state_abbreviation;
			$description = $location_data['county'] . ', ' . $state_abbreviation;
			$args = array(
				'term_name'        => $term_name,
				'term_description' => $description,
				'taxonomy'         => 'county',
				'post_id'          => $post_id,
				'slug'             => $term_slug,
			);
			$this->maybe_add_term_and_associate_with_post( $args );
		}

		if ( ! empty( $location_data['country'] ) ) {
			$args = array(
				'term_name'        => $location_data['country'],
				'term_description' => '',
				'taxonomy'         => 'country',
				'post_id'          => $post_id,
			);
			$this->maybe_add_term_and_associate_with_post( $args );
		}
	}

	public function action_location_edit_form_fields( $term ) {
		$location_data = $this->get_location_data( $term );
		wp_log( $location_data );
		if ( ! empty( $location_data['id'] ) ) {
			$url = 'https://www.instagram.com/explore/locations/' . $location_data['id'] . '/';
			$link = '<a href="' . esc_url( $url ) . '" target="_blank">' . $location_data['id'] . '</a>';
			echo $this->edit_form_field(
				'Instagram Location',
				$link
			);
		}

		$query_args = array(
			'format'         => 'json',
			'lat'            => $location_data['lat'],
			'lon'            => $location_data['lng'],
			'zoom'           => 80,
			'addressdetails' => 1,
		);
		$request_url = add_query_arg( $query_args, 'https://nominatim.openstreetmap.org/reverse' );
		$request_link_text = $location_data['lat'] . ', ' . $location_data['lng'];
		$request_link = '<a href="' . esc_url( $request_url ) . '" target="_blank">' . $request_link_text . '</a>';
		echo $this->edit_form_field(
			'Reverse Geocode',
			$request_link
		);

		$blacklisted_keys = array( 'id', 'name', 'has_public_page' );
		$meta_data = array();
		foreach ( $location_data as $key => $val ) {
			if ( in_array( $key, $blacklisted_keys ) ) {
				continue;
			}
			if ( ! empty( $val ) ) {
				$meta_data[] = $key . ': ' . $val;
			}
		}
		echo $this->edit_form_field(
			'Meta Data',
			implode( '<br>', $meta_data )
		);
	}

	public function edit_form_field( $label = '', $value = '' ) {
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

	public function filter_daddio_pre_insert_instagram_post_args( $post_args, $node = array() ) {
		// Store location id as post_meta if there is one
		if ( ! empty( $node->location_id ) ) {
			$post_args['meta_input']['instagram_location_id'] = $node->location_id;
		}

		$location_data = $this->get_location_data_from_node( $node );
		if ( ! empty( $location_data ) ) {
			if ( isset( $location_data['lat'] ) ) {
				$post_args['meta_input']['latitude'] = $location_data['lat'];
			}
			if ( isset( $location_data['lng'] ) ) {
				$post_args['meta_input']['longitude'] = $location_data['lng'];
			}
		}

		return $post_args;
	}

	public function get_location_data_from_node( $node = array() ) {
		$location_term_id = false;
		if ( ! empty( $node->location_id ) ) {
			$location_args = array(
				'id'              => $node->location_id,
				'name'            => $node->location_name,
				'slug'            => $node->location_slug,
				'has_public_page' => $node->location_has_public_page,
			);
			$location_term_id = $this->maybe_add_location( $location_args );
			return $this->get_location_data( $location_term_id );
		}
		return array();
	}

	public function maybe_add_term_and_associate_with_post( $args = array() ) {
		$default_args = array(
			'term_name'        => '',
			'term_description' => '',
			'slug'             => '',
			'taxonomy'         => '',
			'post_id'          => 0,
		);
		$args = wp_parse_args( $args, $default_args );
		$term = get_term_by(
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

	public function maybe_add_location( $args = array() ) {
		global $wpdb;

		$default_args = array(
			'id'              => '',
			'name'            => '',
			'slug'            => '',
			'has_public_page' => '',
		);
		$args = wp_parse_args( $args, $default_args );
		if ( empty( $args['id'] ) ) {
			return false;
		}

		$term_id = $this->get_term_id_from_location_id( $args['id'] );
		if ( $term_id ) {
			return $term_id;
		}
		$raw_location_data = $this->fetch_instagram_location( $args['id'] );
		$location_data = array(
			'id'              => '',
			'name'            => '',
			'has_public_page' => '',
			'lat'             => '',
			'lng'             => '',
			'slug'            => '',
		);
		if ( isset( $raw_location_data->entry_data->LocationsPage[0]->location ) ) {
			$location_obj = $raw_location_data->entry_data->LocationsPage[0]->location;
			foreach ( $location_data as $key => $val ) {
				if ( isset( $location_obj->{ $key } ) ) {
					$location_data[ $key ] = $location_obj->{ $key };
				}
			}
		}
		$address_data = array();
		if ( ! empty( $location_data['lat'] ) && ! empty( $location_data['lng'] ) ) {
			$address_data = $this->reverse_geocode( $location_data['lat'], $location_data['lng'] );
		}

		$location_data = array_merge( $location_data, $address_data );
		$custom_slug = $args['slug'] . '-' . $args['id'];
		$term_args = array(
			'slug' => $custom_slug,
		);
		$term = wp_insert_term( $args['name'], $taxonomy = 'location', $term_args );
		if ( ! is_array( $term ) || empty( $term['term_id'] ) || is_wp_error( $term ) ) {
			return 0;
		}
		$term_id = intval( $term['term_id'] );
		add_term_meta( $term_id, 'instagram-location-id', $location_data['id'] );
		add_term_meta( $term_id, 'latitude', $location_data['lat'] );
		add_term_meta( $term_id, 'longitude', $location_data['lng'] );
		add_term_meta( $term_id, 'instagram-last-updated', time() );
		add_term_meta( $term_id, 'location-data', $location_data );
		return $term_id;
	}

	public function get_term_id_from_location_id( $location_id = 0 ) {
		global $wpdb;

		$meta_key = 'instagram-location-id';
		$meta_value = $location_id;

		$term_id = $wpdb->get_var( $wpdb->prepare(
			"
				SELECT `term_id`
				FROM `{$wpdb->termmeta}`
				WHERE `meta_key` = %s
				AND `meta_value` = %s
				LIMIT 0,1;
			",
			$meta_key,
			$meta_value
		) );
		$term_id = intval( $term_id );
		if ( $term_id ) {
			return $term_id;
		}
		return 0;
	}

	public function fetch_instagram_location( $location_id = '' ) {
		if ( ! $location_id ) {
			return false;
		}
		$request = 'https://www.instagram.com/explore/locations/' . $location_id . '/';
		$response = wp_remote_get( $request );

		return $this->instagram_class->get_instagram_json_from_html( $response['body'] );
	}

	public function get_location_data( $term = '' ) {
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

	public function reverse_geocode( $lat = '', $long = '' ) {
		$output = array(
			'locality'      => '',
			'city'          => '',
			'county'        => '',
			'state'         => '',
			'postcode'      => '',
			'country'       => '',
			'country_code'  => '',
		);
		if ( empty( $lat ) || empty( $long ) ) {
			return $output;
		}
		$query_args = array(
			'format'         => 'json',
			'lat'            => $lat,
			'lon'            => $long,
			'zoom'           => 80,
			'addressdetails' => 1,
		);
		$request = add_query_arg( $query_args, 'https://nominatim.openstreetmap.org/reverse' );
		$response = wp_remote_get( $request );
		$body = $response['body'];
		if ( ! is_string( $body ) || empty( $body ) ) {
			return $output;
		}
		$json = json_decode( $response['body'] );
		if ( isset( $json->address ) ) {
			$output = wp_parse_args( $json->address, $output );
		}
		if ( isset( $output['postcode'] ) ) {
			// Get city/state data via zipcode
			$request = 'https://api.zippopotam.us/us/' . $output['postcode'];
			$response = wp_remote_get( $request );
			$body = $response['body'];
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
		return $output;
	}

	public function get_state_abbreviation( $state = '' ) {
		$key = ucwords( strtolower( $state ) );
		// via https://gist.github.com/maxrice/2776900#gistcomment-1172963
		$states = array(
			'Alabama'        => 'AL',
			'Alaska'         => 'AK',
			'Arizona'        => 'AZ',
			'Arkansas'       => 'AR',
			'California'     => 'CA',
			'Colorado'       => 'CO',
			'Connecticut'    => 'CT',
			'Delaware'       => 'DE',
			'District of Columbia' => 'DC',
			'Florida'        => 'FL',
			'Georgia'        => 'GA',
			'Hawaii'         => 'HI',
			'Idaho'          => 'ID',
			'Illinois'       => 'IL',
			'Indiana'        => 'IN',
			'Iowa'           => 'IA',
			'Kansas'         => 'KS',
			'Kentucky'       => 'KY',
			'Louisiana'      => 'LA',
			'Maine'          => 'ME',
			'Maryland'       => 'MD',
			'Massachusetts'  => 'MA',
			'Michigan'       => 'MI',
			'Minnesota'      => 'MN',
			'Mississippi'    => 'MS',
			'Missouri'       => 'MO',
			'Montana'        => 'MT',
			'Nebraska'       => 'NE',
			'Nevada'         => 'NV',
			'New Hampshire'  => 'NH',
			'New Jersey'     => 'NJ',
			'New Mexico'     => 'NM',
			'New York'       => 'NY',
			'North Carolina' => 'NC',
			'North Dakota'   => 'ND',
			'Ohio'           => 'OH',
			'Oklahoma'       => 'OK',
			'Oregon'         => 'OR',
			'Pennsylvania'   => 'PA',
			'Rhode Island'   => 'RI',
			'South Carolina' => 'SC',
			'South Dakota'   => 'SD',
			'Tennessee'      => 'TN',
			'Texas'          => 'TX',
			'Utah'           => 'UT',
			'Vermont'        => 'VT',
			'Virginia'       => 'VA',
			'Washington'     => 'WA',
			'West Virginia'  => 'WV',
			'Wisconsin'      => 'WI',
			'Wyoming'        => 'WY',
		);

		if ( isset( $states[ $key ] ) ) {
			return $states[ $key ];
		}
		return false;
	}

}
Daddio_Instagram_Locations::get_instance();
