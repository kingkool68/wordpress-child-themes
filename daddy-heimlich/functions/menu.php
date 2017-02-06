<?php
register_nav_menus(
	array(
		'main-menu' => 'Main Menu',
		'more-menu' => 'Main Menu - More'
	)
);

/**
 * Enqueue the JavaScript and neccessary dependencies to make the menu work.
 */
function daddio_menu_wp_enqueue_scripts() {
	wp_enqueue_script( 'zah-menu', get_template_directory_uri() . '/js/menu.js', array('jquery'), NULL, true );
}
add_action( 'wp_enqueue_scripts', 'daddio_menu_wp_enqueue_scripts' );

/**
 * Output the markup for the menu in the footer of the site using a custom action called 'daddio_footer'
 */
function daddio_menu_footer() {
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
				<a href="https://github.com/kingkool68/zadieheimlich" class="github" title="The code that powers this site is on GitHub" data-ga-category="nav" data-ga-label="GitHub Icon"><?php echo daddio_svg_icon( 'github' ); ?></a>
				<a href="https://www.instagram.com/<?php echo esc_attr( CHILD_INSTAGRAM_HANDLE ); ?>/" class="instagram" rel="me" title="Follow <?php echo esc_attr( CHILD_NAME ); ?> on Instagram @<?php echo esc_attr( CHILD_INSTAGRAM_HANDLE ); ?>" data-ga-category="nav" data-ga-label="Instagram Icon"><?php echo daddio_svg_icon( 'instagram' ); ?></a>
				<a href="<?php echo esc_url( CHILD_FACEBOOK_URL ); ?>" title="<?php echo esc_attr( CHILD_NAME ); ?> is on Facebook" data-ga-category="nav" data-ga-label="Facebook Icon"><?php echo daddio_svg_icon( 'facebook' ); ?></a>
			</p>
		</section>
	</nav>
<?php
}
add_action( 'daddio_footer', 'daddio_menu_footer' );

function daddio_filter_nav_menu_items( $items, $args ) {
	if ( ! is_object( $args ) || ! isset( $args->theme_location ) || $args->theme_location != 'main-menu' ) {
		return $items;
	}
	$items .= '<li><a href="#more-menu" class="more-nav" data-ga-category="nav" data-ga-label="More +">More +</a></li>';
	return $items;
}
add_filter( 'wp_nav_menu_items', 'daddio_filter_nav_menu_items', 10, 2 );

function daddio_nav_menu_css_class($class, $item, $args){
	return array();
}
add_filter('nav_menu_css_class' , 'daddio_nav_menu_css_class' , 10 , 3);

function daddio_nav_menu_item_id($id) {
	return '';
}
add_filter('nav_menu_item_id', 'daddio_nav_menu_item_id');

function daddio_nav_menu_link_attributes( $attr = array(), $item ) {
	$attr['data-ga-category'] = 'nav';
	$attr['data-ga-label'] = $item->title;
	return $attr;
}
add_filter('nav_menu_link_attributes', 'daddio_nav_menu_link_attributes', 10, 2);
