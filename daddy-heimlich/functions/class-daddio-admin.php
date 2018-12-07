<?php
class Daddio_Admin {
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
		add_action( 'wp_dashboard_setup', array( $this, 'action_wp_dashboard_setup' ), 999 );
		add_action( 'wp_before_admin_bar_render', array( $this, 'action_wp_before_admin_bar_render' ), 0 );
	}

	/**
	 * Remove Dashboard widgets we don't want
	 */
	public function action_wp_dashboard_setup() {
		global $wp_meta_boxes;
		$widgets = array(
			'normal' => array(
				'dashboard_activity',
				'wpseo-dashboard-overview', // Yoast SEO
				'monsterinsights_reports_widget',
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
	public function action_wp_before_admin_bar_render() {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu( 'wp-logo' );
	}
}
Daddio_Admin::get_instance();
