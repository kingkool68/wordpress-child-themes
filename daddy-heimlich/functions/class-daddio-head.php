<?php
class Daddio_Head {

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
		add_action( 'wp_head', array( $this, 'action_wp_head' ) );
	}

	/**
	 * Render dynamic content for the <head>
	 */
	public function action_wp_head() {
		$context = array(
			'rss_url'        => get_bloginfo( 'rss2_url' ),
			'site_name'      => get_bloginfo( 'name' ),
			'stylesheet_url' => get_stylesheet_directory_uri(),
			'theme_color'    => CHILD_THEME_COLOR,
			'child_name'     => CHILD_NAME,
		);
		Sprig::out( 'head.twig', $context );
	}
}
Daddio_Head::get_instance();
