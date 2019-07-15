<?php
class Daddio_Menus {
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
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
	}

	/**
	 * Hook into WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'wp_nav_menu_items', array( $this, 'filter_wp_nav_menu_items' ), 10, 2 );
		add_filter( 'nav_menu_link_attributes', array( $this, 'filter_nav_menu_link_attributes' ), 10, 2 );
		add_filter( 'nav_menu_item_id', '__return_empty_string' );
		add_filter( 'nav_menu_css_class', '__return_empty_array', 10, 3 );
	}

	/**
	 * Register nav menus
	 */
	public function action_init() {
		register_nav_menus(
			array(
				'main-menu' => 'Main Menu',
				'more-menu' => 'Main Menu - More',
			)
		);
	}

	/**
	 * Enqueue the JavaScript and neccessary dependencies to make the menu work.
	 */
	public function action_wp_enqueue_scripts() {
		wp_enqueue_script( 'daddio-menu', get_template_directory_uri() . '/js/menu' . Daddio_Scripts_Styles::get_js_suffix(), array( 'jquery' ), null, true );
	}

	/**
	 * Add the More menu item to the main menu
	 *
	 * @param  string $items HTML of the menu items
	 * @param  object $args  Arguments about the nav menu
	 * @return string        Modified menu items HTML
	 */
	public function filter_wp_nav_menu_items( $items = '', $args ) {
		if ( ! is_object( $args ) || ! isset( $args->theme_location ) || 'main-menu' !== $args->theme_location ) {
			return $items;
		}
		$items .= '<li><a href="#more-menu" class="more-nav" data-ga-category="nav" data-ga-label="More +">More +</a></li>';
		return $items;
	}

	/**
	 * Add attributes to nav menu link elements
	 *
	 * @param  array  $attr Attributes for the current nav menu link
	 * @param  object $item Details about the item being modified
	 * @return array        Modified attributes for the current nav menu link
	 */
	public function filter_nav_menu_link_attributes( $attr = array(), $item ) {
		$attr['data-ga-category'] = 'nav';
		$attr['data-ga-label']    = $item->title;
		return $attr;
	}

	public static function get_svg_icon( $icon = '' ) {
		if ( ! $icon ) {
			return;
		}

		return '<svg class="icon icon-' . esc_attr( $icon ) . '" role="img"><use xlink:href="#icon-' . esc_attr( $icon ) . '"></use></svg>';
	}

	/**
	 * Output the markup for the More Menu
	 */
	public static function get_more_menu() {
		$args      = array(
			'theme_location' => 'more-menu',
			'container'      => false,
			'menu_class'     => false,
			'menu_id'        => false,
			'echo'           => false,
		);
		$more_menu = wp_nav_menu( $args );

		$context = array(
			'more_menu'              => $more_menu,
			'github_icon'            => self::get_svg_icon( 'github' ),
			'instagram_icon'         => self::get_svg_icon( 'instagram' ),
			'facebook_icon'          => self::get_svg_icon( 'facebook' ),
			'child_name'             => CHILD_NAME,
			'child_instagram_handle' => CHILD_INSTAGRAM_HANDLE,
			'child_facebook_url'     => CHILD_FACEBOOK_URL,

		);
		return Sprig::render( 'more-menu.twig', $context );
	}
}
Daddio_Menus::get_instance();
