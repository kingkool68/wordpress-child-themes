<?php
class Daddio_Weather {
	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			// Late static binding (PHP 5.3+)
			$instance = new static();
			$instance->setup_hooks();
		}
		return $instance;
	}

	/**
	 * Hook in to WordPress via actions and filters
	 */
	public function setup_hooks() {
		add_action( 'init', array( $this, 'action_init' ) );
	}

	/**
	 * Setup taxonomies
	 */
	public function action_init() {
		$args = array(
			'labels'            => daddio_generate_taxonomy_labels( 'weather', 'weather' ),
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		);
		register_taxonomy( 'weather', array( 'instagram' ), $args );
	}

	public function fetch_weather( $args = array() ) {
		$defaults = array(
			'latitude'  => '',
			'longitude' => '',
			'time'      => '',
		);
		$args = wp_parse_args( $args, $defaults );
		if ( ! empty( $args['time'] ) ) {
			$args['time'] = strtotime( $args['time'] );
		}
		$request_args_order = array_keys( $defaults );
		$request_args = array();
		foreach ( $request_args_order as $key ) {
			if ( ! empty( $args[ $key ] ) ) {
				$request_args[ $key ] = $args[ $key ];
			}
		}

		$request = 'https://api.darksky.net/forecast/' . DARK_SKY_API_KEY . '/' . implode( ',', $request_args );
		$response = wp_remote_get( $request );
		$api_response = json_decode( wp_remote_retrieve_body( $response ), true );
		// Todo: Parse the response and figure out what data block corresponds to the hour of the post in local time ???
		return $api_response;
	}
}
Daddio_Weather::get_instance();
