<?php
class Daddio_Scripts_Style {
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

	public function setup_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
		add_action( 'after_setup_theme', array( $this, 'action_after_setup_theme' ) );

		add_filter( 'style_loader_tag', array( $this, 'filter_style_loader_tag' ), 10, 4 );
		add_filter( 'script_loader_tag', array( $this, 'filter_script_loader_tag' ), 10, 3 );
		add_filter( 'body_class', array( $this, 'filter_body_class' ), 10, 1 );
	}

	/**
	 * Loads styles and scripts
	 */
	public function action_wp_enqueue_scripts() {
		// CSS
		$css_suffix = '.min.css';
		if ( isset( $_GET['debug-css'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
			$css_suffix = '.css';
		}

		// The mediaelement styles are rolled in to the theme CSS file via Gulp
		if ( ! is_admin() ) {
			wp_deregister_style( 'wp-mediaelement' );
		}

		// This needs to be absctracted out so child themes can use it,
		// also need to let other functions determine if they should be minfied or not.
		$js_suffix = '.min.js';
		if ( isset( $_GET['debug-js'] ) || ( function_exists( 'rh_is_dev' ) && rh_is_dev() ) ) {
			$js_suffix = '.js';
		}

		// JavaScript
		wp_register_script( 'post-gallery', get_template_directory_uri() . '/js/post-gallery' . $js_suffix, array( 'jquery' ), null, true );

		// Global JavaScript files bundled into one that gets loaded on every single page
		wp_register_script( 'daddio-global-scripts', get_template_directory_uri() . '/js/global.min.js', array( 'jquery' ), null, true );
		if ( $this->maybe_use_global_script_file() ) {
			add_filter( 'script_loader_tag', array( $this, 'dont_load_bundled_scripts' ), 10, 3 );
			wp_enqueue_script( 'daddio-global-scripts' );
		}
	}

	/**
	 * Move any scripts enquued to the wp_head action to the wp_footer action for performance reasons.
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
	 * Adds a pre-connect <link> element to start establishing a connection for Google Fonts to speed up page rendering.
	 * @param  [type] $html   HTML to be printed
	 * @param  [type] $handle Handle of the style being filtered
	 * @param  [type] $href   href attribute of the style being printed
	 * @param  [type] $media  media attribute of the style being printed
	 * @return [type]         HTML to be printed
	 * @see https://www.igvita.com/2015/08/17/eliminating-roundtrips-with-preconnect/
	 */
	public function filter_style_loader_tag( $html, $handle, $href, $media ) {
		if ( 'zah-google-fonts' != $handle ) {
			return $html;
		}

		return '<link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>' . PHP_EOL . $html;
	}

	/**
	 * Serve jQuery via conditional comments so IE 8 and below get jQuery 1.x and everyone else is served jQuery 2.x
	 * @param  [string] $script_element		<script> element to be rendered
	 * @param  [string] $handle 			script handle that was registered
	 * @param  [string] $script_src			src sttribute of the <script>
	 * @return [string]						New <script> element
	 */
	public function filter_script_loader_tag( $script_element, $handle, $script_src ) {
		if ( is_admin() ) {
			return $script_element;
		}

		if ( 'jquery-core' == $handle || 'jquery' == $handle ) {
			$new_script_element = '';

			// jQuery 1.x gets served to IE8 and below...
			$new_script_element .= '<!--[if lt IE 9]>';
			$new_script_element .= $script_element;
			$new_script_element .= '<![endif]-->';

			// jQuery 2.x gets served to everyone else...
			$jquery2_src = apply_filters( 'script_loader_src', get_template_directory_uri() . '/js/jquery-2.min.js' );
			$new_script_element .= '<!--[if (gte IE 9) | (!IE)]><!-->';
			$new_script_element .= "<script type='text/javascript' src='" . $jquery2_src . "'></script>";
			$new_script_element .= '<!--<![endif]-->';

			return $new_script_element;
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
	 * @return [boolean]
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
	 * If we're loading a bundled version of scripts then we don't want to load individual JavaScript files for certain script handles.
	 * @param  [string] $script_element		<script> element to be rendered
	 * @param  [string] $handle 			script handle that was registered
	 * @param  [string] $script_src			src sttribute of the <script>
	 * @return [string]						New <script> element
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
			'mediaelement',
			'wp-mediaelement',
		);
		if ( in_array( $handle, $blacklisted ) ) {
			return '';
		}

		return $script_element;
	}
}
Daddio_Scripts_Style::get_instance();
