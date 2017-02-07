<?php
class Daddio_Admin {
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
	 * Setup hooks to manipulate
	 */
	public function setup_hooks() {
		add_action( 'wp_dashboard_setup', array( $this, 'clear_dashboard_widgets' ), 999 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'remove_wp_menu_from_admin_bar' ), 0 );
	}

	/**
	 * Remove Dashboard widgets we don't want
	 */
	public function clear_dashboard_widgets() {
		global $wp_meta_boxes;
		$widgets = array(
			'normal' => array(
				'dashboard_activity',
				'wpseo-dashboard-overview', // Yoast SEO
			),
			'side' => array(
				'dashboard_primary',
				'dashboard_quick_press',
			),
		);

		foreach ( $widgets as $priotity => $keys ) {
			foreach ( $keys as $key ) {
				unset( $wp_meta_boxes['dashboard'][ $priotity ]['core'][ $key ] );
			}
		}
	}

	/**
	 * Remove WP logo from admin bar
	 */
	public function remove_wp_menu_from_admin_bar() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'wp-logo' );
	}
}
new Daddio_Admin;
