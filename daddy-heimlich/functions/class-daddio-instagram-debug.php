<?php
class Daddio_Instagram_Debug {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup();
			$instance->setup_actions();
		}
		return $instance;
	}

	public function setup() {
		$this->instagram_class = Daddio_Instagram::get_instance();
		$this->instagram_location_class = Daddio_Instagram_Locations::get_instance();
	}

	/**
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
	}

	/**
	 * Add Private Sync submenu
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

	public function handle_debug_instagram_submenu() {
		$result = '';
		if (
			isset( $_POST['instagram-source'] )
			&& ! empty( $_POST['instagram-source'] )
			&& check_admin_referer( 'zah-instagram-private-sync' )
		) {
			$instagram_source = wp_unslash( $_POST['instagram-source'] );
			$json             = $this->instagram_class->get_instagram_json_from_html( $instagram_source );

			// It's a Tag page
			if ( isset( $json->entry_data->TagPage[0] ) ) {
				$top_posts = array();
				$other     = array();

				if ( isset( $json->entry_data->TagPage[0]->tag ) ) {
					$top_posts = $json->entry_data->TagPage[0]->tag->top_posts->nodes;
					$other     = $json->entry_data->TagPage[0]->tag->media->nodes;
				}

				// New format I detected on 01/02/2018
				if ( isset( $json->entry_data->TagPage[0]->graphql->hashtag ) ) {
					$top_posts = $json->entry_data->TagPage[0]->graphql->hashtag->edge_hashtag_to_top_posts->edges;
					$other     = $json->entry_data->TagPage[0]->graphql->hashtag->edge_hashtag_to_media->edges;
				}
				$nodes = array_merge( $top_posts, $other );
			}

			// It's a single Post page
			if ( isset( $json->entry_data->PostPage[0] ) ) {
				$nodes = array( $json->entry_data->PostPage[0]->graphql->shortcode_media );
			}
			foreach ( $nodes as $node ) :
				$node           = $this->instagram_class->normalize_instagram_data( $node );
				$instagram_link = 'https://www.instagram.com/p/' . $node->code . '/';

				wp_dump( $node );
				$location_data = $this->instagram_location_class->get_location_data_from_node( $node );
				wp_dump( $location_data );
			endforeach;
		}
		?>
		<style>
			#instagram-source {
				display: block;
				max-width: 800px;
				width: 95%;
			}
			.children {
				padding-left: 25px;
			}
			.delete-link {
				color: red;
			}
		</style>
		<div class="wrap">
			<?php
			if ( $result ) {
				echo $result; }
			?>

			<h1>Instagram Debug</h1>
			<p>Paste the HTML source of the private Instagram post to scrape and sync it with this site.</p>
			<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=instagram&page=instagram-debug' ) ); ?>" method="post">
				<?php wp_nonce_field( 'zah-instagram-private-sync' ); ?>
				<label for="instagram-source">HTML Source</label>
				<textarea name="instagram-source" id="instagram-source" rows="5"></textarea>
				<?php submit_button( 'Debug', 'primary' ); ?>
			</form>
		</div>
		<?php
		// $context = array();
		// Sprig::out( 'admin/instagram-debug-submenu.twig', $context );
	}
}
Daddio_Instagram_Debug::get_instance();
