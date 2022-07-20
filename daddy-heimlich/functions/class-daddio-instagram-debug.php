<?php
/**
 * Handle an Instagram Debug tool to see what data we can actually parse
 */
class Daddio_Instagram_Debug {

	/**
	 * Nonce field for the submenu form
	 *
	 * @var string
	 */
	public static $nonce_field_action = 'instagram-debug';

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
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
	}

	/**
	 * Add Debugger submenu
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=instagram',
			'Debugger',
			'Debugger',
			'manage_options',
			'instagram-debug',
			array( $this, 'handle_debug_instagram_submenu' )
		);
	}

	/**
	 * Render the debugger submenu
	 */
	public function handle_debug_instagram_submenu() {
		$result              = array();
		$instagram_url       = '';
		$instagram_permalink = '';

		// Maybe update the Instagram Sessionid value in the database
		$instagram_sessionid = '';
		if ( ! empty( $_REQUEST['instagram-sessionid'] ) ) {
			$instagram_sessionid = wp_unslash( $_REQUEST['instagram-sessionid'] );
			$instagram_sessionid = sanitize_text_field( $instagram_sessionid );
			update_option( 'instagram-sessionid', $instagram_sessionid, $autoload = false );
		} else {
			$instagram_sessionid = get_option( 'instagram-sessionid' );
		}

		if (
			! empty( $_REQUEST['instagram-url'] )
			&& check_admin_referer( static::$nonce_field_action )
		) {
			$instagram_url = wp_unslash( $_REQUEST['instagram-url'] );
			$instagram     = new Instagram_Scraper( $instagram_url );
			if ( ! empty( $instagram->items ) ) {
				foreach ( $instagram->items as $item ) {
					$result[] = static::render_item( $item );
				}
			}
		}

		$action  = static::$nonce_field_action;
		$name    = '_wpnonce';
		$referer = true;
		$echo    = false;

		$context = array(
			'page_type'           => $instagram->page_type,
			'page_info'           => $instagram->page_info,
			'instagram_permalink' => $instagram_permalink,
			'result'              => implode( "\n", $result ),
			'nonce_field'         => wp_nonce_field(
				$action,
				$name,
				$referer,
				$echo
			),
			'form_action_url'     => admin_url( 'edit.php?post_type=instagram&page=instagram-debug' ),
			'instagram_url'       => $instagram_url,
			'instagram_sessionid' => $instagram_sessionid,
			'submit_button'       => get_submit_button( 'Debug', 'primary' ),
		);
		Sprig::out( 'admin/instagram-debug-submenu.twig', $context );
	}

	/**
	 * Render a given debug item from a given Instgram post
	 *
	 * @param Object $item An item from the results of a Instagram Scraper object
	 */
	public static function render_item( $item ) {
		$item        = (object) $item;
		$location_id = '';
		if ( ! empty( $item->location_id ) ) {
			$location_id = '<a href="https://www.instagram.com/explore/locations/' . $item->location_id . '/" target="_blank">' . $item->location_id . '</a>';
		}

		$timestamp = new DateTime( '@' . $item->timestamp );
		$timestamp->setTimezone( wp_timezone() );

		$context = array(
			'url'    => make_clickable( $item->instagram_url ),
			'medias' => $item->media,
			'items'  => array(
				// Label => Value
				'ID'             => $item->id,
				'Code'           => $item->code,
				'Caption'        => $item->caption,
				'Date'           => $timestamp->format( 'F j, Y g:ia T' ),
				'User'           => $item->owner_username,
				'User Full Name' => $item->owner_full_name,
				'Location'       => $item->location_name,
				'Location ID'    => $location_id,
				'Address'        => $item->location_address,
				'City'           => $item->location_city,
				'Latitude'       => $item->latitude,
				'Longitude'      => $item->longitude,
			),
		);
		return Sprig::render( 'admin/instagram-debug-item.twig', $context );
	}
}
Daddio_Instagram_Debug::get_instance();
