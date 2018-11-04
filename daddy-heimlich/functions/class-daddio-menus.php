<?php
class Daddio_Menus {
	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_hooks();
		}
		return $instance;
	}

	public function setup_hooks() {
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
		add_action( 'daddio_footer', array( $this, 'action_daddio_footer' ) );

		add_filter( 'wp_nav_menu_items', array( $this, 'filter_wp_nav_menu_items' ), 10, 2 );
		add_filter( 'nav_menu_css_class' , array( $this, 'filter_nav_menu_css_class' ) , 10 , 3 );
		add_filter( 'nav_menu_item_id', array( $this, 'filter_nav_menu_item_id' ) );
		add_filter( 'nav_menu_link_attributes', array( $this, 'filter_nav_menu_link_attributes' ), 10, 2 );
	}

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
		wp_enqueue_script( 'daddio-menu', get_template_directory_uri() . '/js/menu.js', array( 'jquery' ), null, true );
	}

	/**
	 * Output the markup for the menu in the footer of the site using a custom action called 'daddio_footer'
	 */
	public function action_daddio_footer() {
	?>
		<nav id="more-menu" class="more-menu">
			<section>
				<h2 class="title">Main Menu</h2>
				<a href="#close" class="close" data-ga-category="nav" data-ga-label="Close">
					<span aria-hidden="true">&times;</span>
					<span class="alt-text">Close</span>
				</a>
				<?php
				$args = array(
					'theme_location' => 'more-menu',
					'container' => false,
					'menu_class' => false,
					'menu_id' => false,
				);
				wp_nav_menu( $args );
				?>
				<p class="social-links">
					<a href="https://github.com/kingkool68/wordpress-child-themes" class="github u-url" title="The code that powers this site is on GitHub" data-ga-category="nav" data-ga-label="GitHub Icon">
						<?php echo daddio_svg_icon( 'github' ); ?>
					</a>
					<a href="https://www.instagram.com/<?php echo esc_attr( CHILD_INSTAGRAM_HANDLE ); ?>/" class="instagram u-url" rel="me" title="Follow <?php echo esc_attr( CHILD_NAME ); ?> on Instagram @<?php echo esc_attr( CHILD_INSTAGRAM_HANDLE ); ?>" data-ga-category="nav" data-ga-label="Instagram Icon">
						<?php echo daddio_svg_icon( 'instagram' ); ?>
					</a>
					<a href="<?php echo esc_url( CHILD_FACEBOOK_URL ); ?>" title="<?php echo esc_attr( CHILD_NAME ); ?> is on Facebook" data-ga-category="nav" data-ga-label="Facebook Icon" class="u-url">
						<?php echo daddio_svg_icon( 'facebook' ); ?>
					</a>
				</p>
			</section>
		</nav>
	<?php
	}

	public function filter_wp_nav_menu_items( $items, $args ) {
		if ( ! is_object( $args ) || ! isset( $args->theme_location ) || 'main-menu' != $args->theme_location ) {
			return $items;
		}
		$items .= '<li><a href="#more-menu" class="more-nav" data-ga-category="nav" data-ga-label="More +">More +</a></li>';
		return $items;
	}

	public function filter_nav_menu_css_class( $class, $item, $args ) {
		return array();
	}

	public function filter_nav_menu_item_id( $id ) {
		return '';
	}

	public function filter_nav_menu_link_attributes( $attr = array(), $item ) {
		$attr['data-ga-category'] = 'nav';
		$attr['data-ga-label'] = $item->title;
		return $attr;
	}
}
Daddio_Menus::get_instance();
