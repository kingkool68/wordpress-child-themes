<?php
class Daddio_Scripts_Styles {
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
		add_action( 'init', array( $this, 'action_init_register_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
		add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ) );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'style_loader_tag', array( $this, 'filter_style_loader_tag' ), 10, 4 );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );
		add_filter( 'body_class', array( $this, 'filter_body_class' ), 10, 1 );
	}

	/**
	 * Register scripts early enough for other hooks to make use of them
	 */
	public function action_init_register_scripts() {
		// The mediaelement styles are rolled in to the theme CSS file via Gulp
		if ( ! is_admin() ) {
			wp_deregister_style( 'wp-mediaelement' );
		}

		// Global JavaScript files bundled into one that gets loaded on every single page
		wp_register_script( 'daddio-global-scripts', get_template_directory_uri() . '/js/global' . self::get_js_suffix(), array( 'jquery' ), null, true );
	}

	/**
	 * Enqueue scripts at the right time
	 */
	public function action_wp_enqueue_scripts() {
		if ( $this->maybe_use_global_script_file() ) {
			add_filter( 'script_loader_tag', array( $this, 'dont_load_bundled_scripts' ), 10, 3 );
			// wp_enqueue_script( 'daddio-global-scripts' );
		}

		// Don't load the frontend Block Editor styles
		wp_dequeue_style( 'wp-block-library' );
	}

	/**
	 * Move any scripts enquued to the wp_head action to the wp_footer action for performance reasons
	 *
	 * @see http://www.kevinleary.net/move-javascript-bottom-wordpress/
	 */
	public function action_after_setup_theme() {
		// Disable printing the WP Emjoi styles injected into the <head>. They're bundled into our compiled stylesheet.
		remove_action( 'wp_print_styles', 'print_emoji_styles' );

		remove_action( 'wp_head', 'wp_print_scripts' );
		remove_action( 'wp_head', 'wp_print_head_scripts', 9 );
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

		add_action( 'wp_footer', 'wp_print_scripts', 11 );
		add_action( 'wp_footer', 'wp_print_head_scripts', 11 );
		add_action( 'wp_footer', 'print_emoji_detection_script', 7 );
	}

	/**
	 * Adds a pre-connect <link> element to start establishing a connection for Google Fonts to speed up page rendering
	 *
	 * @see https://www.igvita.com/2015/08/17/eliminating-roundtrips-with-preconnect/
	 *
	 * @param  [type] $html   HTML to be printed
	 * @param  [type] $handle Handle of the style being filtered
	 * @param  [type] $href   href attribute of the style being printed
	 * @param  [type] $media  media attribute of the style being printed
	 * @return [type]         HTML to be printed
	 */
	public function filter_style_loader_tag( $html, $handle, $href, $media ) {
		if ( 'zah-google-fonts' !== $handle ) {
			return $html;
		}

		return '<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>' . PHP_EOL . $html;
	}

	/**
	 * Serve jQuery via conditional comments so IE 8 and below get jQuery 1.x and everyone else is served jQuery 2.x
	 *
	 * @param  string $script_element  <script> element to be rendered
	 * @param  string $handle          script handle that was registered
	 * @param  string $script_src      src sttribute of the <script>
	 * @return string                  New <script> element
	 */
	public function filter_script_loader_tag( $script_element, $handle, $script_src ) {
		if ( is_admin() ) {
			return $script_element;
		}

		if ( 'jquery-core' === $handle || 'jquery' === $handle ) {
			$new_script_element = '';

			// jQuery 1.x gets served to IE8 and below...
			$new_script_element .= '<!--[if lt IE 9]>';
			$new_script_element .= $script_element;
			$new_script_element .= '<![endif]-->';

			// jQuery 3.x gets served to everyone else...
			$jquery3_src         = apply_filters( 'script_loader_src', get_template_directory_uri() . '/js/jquery-3.min.js' );
			$new_script_element .= '<!--[if (gte IE 9) | (!IE)]><!-->';
			$new_script_element .= "<script type='text/javascript' src='" . $jquery3_src . "' defer></script>";
			$new_script_element .= '<!--<![endif]-->';

			return $new_script_element;
		}

		$defer_handles = array(
			'daddio-global-scripts',
		);
		if ( in_array( $handle, $defer_handles, true ) ) {
			$script_element = str_replace( ' src', ' defer src', $script_element );
		}

		return $script_element;
	}

	/**
	 * Check if ?debug-ga is set and add a body class for debugging Google Analytics events
	 *
	 * @param  string $body_class Body class to filter
	 * @return string             Filtered body class
	 */
	public function filter_body_class( $body_class = '' ) {
		if ( isset( $_GET['debug-ga'] ) ) {
			$body_class[] = 'debug-ga';
		}
		return $body_class;
	}

	/**
	 * Conditional helper to determine if we should use the concatenated global JavaScript file built by Gulp.js
	 *
	 * @return boolean
	 */
	public function maybe_use_global_script_file() {
		if ( is_admin() ) {
			return false;
		}
		if ( function_exists( 'rh_is_prod' ) && rh_is_prod() ) {
			return true;
		}

		return false;
	}

	/**
	 * If we're loading a bundled version of scripts then we don't want to load individual JavaScript files for certain script handles
	 *
	 * @param  string $script_element     <script> element to be rendered
	 * @param  string $handle             script handle that was registered
	 * @param  string $script_src         src sttribute of the <script>
	 * @return string                     New <script> element
	 */
	public function dont_load_bundled_scripts( $script_element, $handle, $script_src ) {
		if ( ! $this->maybe_use_global_script_file() ) {
			return $script_element;
		}

		// These scripts are bundled together in 'daddio-global-scripts' so they don't need to be printed to the screen.
		$blacklisted = array(
			'jquery-migrate',
			'wp-embed',
			'daddio-menu',
			'mediaelement-core',
			'mediaelement-migrate',
			'wp-mediaelement',
			'mediaelement-vimeo',
		);
		if ( in_array( $handle, $blacklisted, true ) ) {
			return '';
		}

		return $script_element;
	}

	/**
	 * Get the CSS suffix depending on the environment
	 *
	 * Production should use *.min.css
	 * Local development should use *.css which includes sourcemaps
	 *
	 * @return string CSS file suffix
	 */
	public static function get_css_suffix() {
		if ( isset( $_GET['debug-css'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
			return '.css';
		}
		return '.min.css';
	}

	/**
	 * Get the JavaScript suffix depending on the environment
	 *
	 * Production should use *.min.js
	 * Local development should use *.js
	 *
	 * @return string CSS file suffix
	 */
	public static function get_js_suffix() {
		if ( isset( $_GET['debug-js'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
			return '.js';
		}
		return '.min.js';
	}
}
Daddio_Scripts_Styles::get_instance();
