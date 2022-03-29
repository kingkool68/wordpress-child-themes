<?php

use \ForceUTF8\Encoding;
use \Twitter\Text\Extractor;

/**
 * Handle WordPress Instgram integration
 */
class Daddio_Instagram {

	/**
	 * The submenu slug
	 *
	 * @var string
	 */
	private static $sync_slug = 'instagram-sync';

	/**
	 * The post type key
	 *
	 * @var string
	 */
	public static $post_type = 'instagram';

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
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
		add_action( 'before_delete_post', array( $this, 'action_before_delete_post' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts_with_no_tags' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'action_manage_posts_custom_column' ) );
		add_action( 'restrict_manage_posts', array( $this, 'action_restrict_manage_posts' ) );
		add_action( 'wp_dashboard_setup', array( $this, 'action_wp_dashboard_setup' ) );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'the_content', array( $this, 'filter_the_content' ) );
		add_filter(
			'manage_instagram_posts_columns',
			array( $this, 'filter_manage_instagram_posts_columns' )
		);
	}

	/**
	 * Setup post type
	 */
	public function action_init() {
		$args = array(
			'label'               => static::$post_type,
			'description'         => 'Instagram posts',
			'labels'              => Daddio_Helpers::generate_post_type_labels( 'instagram post', 'instagram posts' ),
			'supports'            => array( 'title', 'editor', 'thumbnail', 'comments' ),
			'taxonomies'          => array( 'category', 'post_tag' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-camera',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'page',
		);
		register_post_type( static::$post_type, $args );
	}

	/**
	 * Add sync submenu
	 */
	public function action_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=instagram',
			'Instagram Sync',
			'Instagram Sync',
			'manage_options',
			static::$sync_slug,
			array( __CLASS__, 'handle_sync_submenu' )
		);

		add_submenu_page(
			null,
			'Modify Synced Instagram Post',
			'Modify Synced Instagram Post',
			'manage_options',
			'zah-instagram-modify-post',
			array( __CLASS__, 'handle_modify_instagram_post_submenu' )
		);
	}

	/**
	 * Delete attached media associated with an Instagram post that is going to be deleted
	 *
	 * @param  integer $post_id ID of the post that is about to be deleted
	 */
	public function action_before_delete_post( $post_id = 0 ) {
		$post = get_post( $post_id );
		if ( static::$post_type !== $post->post_type ) {
			return;
		}
		$args  = array(
			'post_type'   => 'attachment',
			'post_status' => 'any',
			'post_parent' => $post->ID,
			'fields'      => 'ids',
		);
		$query = new WP_Query( $args );
		if ( empty( $query->posts ) ) {
			return;
		}
		foreach ( $query->posts as $attachment_id ) {
			$force_delete = true;
			wp_delete_attachment( $attachment_id, $force_delete );
		}
	}

	/**
	 * Include Instagram posts in the main query under certain conditions
	 *
	 * @param  WP_Query $query The WP_Query object we're modifying
	 */
	public function action_pre_get_posts( $query ) {
		if (
			( $query->is_archive() || $query->is_home() )
			&& $query->is_main_query()
			&& ! is_admin()
		) {
			$query->set( 'post_type', array( 'post', 'instagram' ) );
			$query->set(
				'orderby',
				array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				)
			);
		}
	}

	/**
	 * Return Instagram posts that aren't tagged
	 *
	 * @param  WP_Query $query The WP_Query object we're modifying
	 */
	public function action_pre_get_posts_with_no_tags( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( empty( $_GET['tag-filter'] ) || 'no-tags' !== $_GET['tag-filter'] ) {
			return;
		}

		$tag_ids = get_terms( 'post_tag', array( 'fields' => 'ids' ) );
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'post_tag',
					'field'    => 'id',
					'terms'    => $tag_ids,
					'operator' => 'NOT IN',
				),
			)
		);
	}

	/**
	 * Handle Instagram post type columns
	 *
	 * @param  string  $column  ID of the column to output data for
	 * @param  integer $post_id ID of the Post for the current row
	 */
	public function action_manage_posts_custom_column( $column = '', $post_id = 0 ) {

		$post = get_post( $post_id );
		switch ( $column ) {
			case 'instagram_photo':
				$featured_id = get_post_thumbnail_id( $post->ID );
				if ( ! $featured_id ) {
					// We don't have one so let's try and get a featured image...
					$media       = get_attached_media( 'image', $post->ID );
					$media_ids   = array_keys( $media );
					$featured_id = $media_ids[0];

					add_post_meta( $post->ID, '_thumbnail_id', $featured_id );
				}

				$img = wp_get_attachment_image_src( $featured_id, 'thumbnail' );
				echo '<a href="' . esc_url( get_permalink( $post->ID ) ) . '">';
				echo '<img src="' . esc_url( $img[0] ) . '" width="' . esc_attr( $img[1] ) . '" height="' . esc_attr( $img[2] ) . '" style="height: auto; max-width: 100%;">';
				echo '</a>';

				break;

			case 'instagram_permalink':
				echo '<a href="' . esc_url( $post->guid ) . '" target="_blank">@' . static::get_instagram_username() . '</a>';
				break;
		}
	}

	/**
	 * Add a select menu for showing posts that aren't tagged in the post list screen
	 */
	public function action_restrict_manage_posts() {
		$whitelisted_post_types = array( 'post', static::$post_type );
		if ( ! in_array( get_current_screen()->post_type, $whitelisted_post_types, true ) ) {
			return;
		}

		$selected = ( isset( $_GET['tag-filter'] ) && 'no-tags' === $_GET['tag-filter'] );
		?>
		<select name="tag-filter">
			<option value="">All Tags</option>
			<option value="no-tags" <?php echo selected( $selected ); ?>>No Tags</option>
		</select>
		<?php
	}

	/**
	 * Render the screen for the sync submenu page
	 */
	public static function handle_sync_submenu() {

		$result           = '';
		$form_content     = '';
		$instagram_source = '';

		$title       = 'Instagram Sync';
		$description = 'Paste the HTML source of the Instagram post to scrape and sync it with this site.';

		if (
			! empty( $_POST['instagram-source'] )
			&& check_admin_referer( static::$sync_slug )
		) {
			$instagram_source = wp_unslash( $_POST['instagram-source'] );
			$picked           = array();
			if ( ! empty( $_POST['picked'] ) ) {
				$picked = wp_unslash( $_POST['picked'] );
			}

			if ( empty( $picked ) ) {
				$title        = 'Pick which Instagram items to sync';
				$description  = '';
				$form_content = static::handle_sync_submenu_pick_step( $instagram_source );
			} else {
				$title        = 'Results';
				$form_content = static::handle_sync_submenu_import_step( $instagram_source );
			}
		}
		$form_action_url = add_query_arg(
			array(
				'post_type' => static::$post_type,
				'page'      => static::$sync_slug,
			),
			admin_url( 'edit.php' )
		);
		$context         = array(
			'result'           => $result,
			'title'            => $title,
			'description'      => apply_filters( 'the_content', $description ),
			'form_action_url'  => $form_action_url,
			'form_content'     => $form_content,
			'instagram_source' => $instagram_source,
			'submit_button'    => get_submit_button( 'Sync' ),
			'nonce_field'      => wp_nonce_field(
				static::$sync_slug,
				$name = '_wpnonce',
				$referer = true,
				$echo = false
			),
		);
		Sprig::out( 'admin/instagram-sync-submenu.twig', $context );
	}

	public static function handle_sync_submenu_pick_step( $instagram_source = '' ) {
		$instagram = new Instagram_Scraper( $instagram_source );
		$output    = '';
		if ( ! empty( $instagram->items ) ) :
			foreach ( $instagram->items as $item ) :
				var_dump( $item );
				// Grab all of the IDs to run one SQL query and identify which ones have already been imported
				$context = array(
					'instagram_url' => $item['instagram_url'],
					'caption'       => $item['code'],
				);
				$output .= Sprig::render( 'admin/instagram-sync-submenu--pick-item.twig', $context );
			endforeach;
		endif;

		return $output;
	}

	public static function handle_sync_submenu_import_step( $instagram_source = '' ) {
		$instagram = new Instagram_Scraper( $instagram_source );

		/*
		if ( ! empty( $instagram->items ) ) :
			foreach ( $instagram->items as $item ) :
				$found = static::does_instagram_permalink_exist( $item['instagram_url'] );
				if ( $found ) {
					continue;
				}
				$inserted = static::insert_instagram_post( $item, $post_args = array() );
				$data     = static::handle_instagram_inserted_result( $inserted, $node );
				$result  .= static::render_instagram_inserted_result( $data );
			endforeach;
		endif;
		*/
		return 'Here is where we will import the picked instagram posts';
	}

	/**
	 * Handle requests to modify instagram posts (delete or set to draft)
	 */
	public static function handle_modify_instagram_post_submenu() {
		if ( empty( $_GET['post_id'] ) ) {
			wp_die( 'Missing Post ID parameter' );
		}
		if ( empty( $_GET['action'] ) ) {
			wp_die( 'Missing action parameter' );
		}

		$post_id = absint( $_GET['post_id'] );
		check_admin_referer( 'modify_' . $post_id );

		if ( get_post_type( $post_id ) !== static::$post_type ) {
			wp_die( 'Not an Instagram post' );
		}
		$action = strtolower( $_GET['action'] );

		switch ( $action ) {
			case 'draft':
				$update_args = array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				);
				wp_update_post( $update_args );
				echo '<p>Post set to draft.</p>';
				break;

			case 'delete':
				wp_delete_post( $post_id, true );
				echo '<p>Post deleted.</p>';
				break;
		}
	}

	/**
	 * Display a result depending on the status of inserting an Instagram post during a sync
	 *
	 * @param  object|-1 $result The result of attempting to insert the Instagram post
	 * @param  object $node   Normaized node data for reference
	 * @return array          Options depending on if the post was inserted or not
	 */
	public static function handle_instagram_inserted_result( $result, $node ) {
		$node = static::normalize_instagram_data( $node );

		$output = array(
			'status_message'   => '',
			'permalink'        => 'https://www.instagram.com/p/' . $node->code . '/',
			'thumbnail_src'    => $node->full_src,
			'caption'          => $node->caption,
			'posted_timestamp' => date( 'Y-m-d H:i:s', intval( $node->timestamp ) ), // In GMT time
			'children'         => array(),
			'post_id'          => 0,
		);
		if ( -1 === $result ) {
			// It's a private post that needs to be manually downloaded...
			$output = wp_parse_args(
				array(
					'status_message' => 'Private! Needs to be manually synced.',
				),
				$output
			);
		} elseif ( is_object( $result ) && isset( $result->post_id ) ) {
			// Success!
			$post_id           = $result->post_id;
			$featured_image_id = get_post_thumbnail_id( $post_id );
			$thumbnail_src     = wp_get_attachment_image_src( $featured_image_id, 'thumbnail' );
			$output            = wp_parse_args(
				array(
					'status_message' => 'Success!',
					'permalink'      => get_permalink( $post_id ),
					'post_id'        => $post_id,
					'thumbnail_src'  => $thumbnail_src[0],
				),
				$output
			);
			if ( ! empty( $result->children ) ) {
				foreach ( $result->children as $child_post_id ) {
					$output['children'][] = static::handle_instagram_inserted_result(
						(object) array(
							'post_id'  => $child_post_id,
							'children' => array(),
						),
						$node
					);
				}
			}
		}
		return $output;
	}

	/**
	 * Given an Instagram inserted result, render an output to the sync screen
	 *
	 * @param  array $data Insert data
	 * @return string      HTML output
	 */
	public static function render_instagram_inserted_result( $data ) {
		$defaults = array(
			'status_message'   => '',
			'permalink'        => '',
			'thumbnail_src'    => '',
			'caption'          => '',
			'posted_timestamp' => '',
			'children'         => array(),
			'post_id'          => 0,
		);
		$data     = wp_parse_args( $data, $defaults );
		extract( $data );
		$modify_post_url   = admin_url( 'edit.php?post_type=instagram&page=zah-instagram-modify-post&post_id=' . $post_id );
		$modify_post_url   = wp_nonce_url( $modify_post_url, 'modify_' . $post_id );
		$save_to_draft_url = add_query_arg( 'action', 'draft', $modify_post_url );
		$delete_url        = add_query_arg( 'action', 'delete', $modify_post_url );
		$output            = '';
		ob_start();
		?>
			<h2><?php echo $status_message; ?></h2>
			<p>
				<a href="<?php echo esc_url( $permalink ); ?>" target="_blank">
					<img src="<?php echo esc_url( $thumbnail_src ); ?>" width="150" height="">
				</a>
				<br> <?php echo $caption; ?>
				<br><?php echo get_date_from_gmt( $posted_timestamp, 'F j, Y g:i a' ); ?>
				<br><a href="<?php echo esc_url( $delete_url ); ?>" target="_blank" class="delete-link">Delete</a> | <a href="<?php echo esc_url( $save_to_draft_url ); ?>" target="_blank" class="draft-link">Set to Draft</a>
			</p>
		<?php
		$output .= ob_get_clean();

		if ( $children ) {
			$output .= '<div class="children">';
			foreach ( $children as $child_data ) {
				$output .= static::render_instagram_inserted_result( $child_data );
			}
			$output .= '</div>';
		}
		$output .= '<hr>';
		return $output;
	}

	/**
	 * Filter Instagram content to linkify Instagram usernames and hashtags
	 *
	 * @param  string $content The content to modify
	 * @return string          The modified content
	 */
	public function filter_the_content( $content = '' ) {
		$post = get_post();
		if ( static::$post_type === get_post_type( $post ) && ! empty( $content ) ) {
			// Extract and linkify any hashtags
			$e        = Extractor::create();
			$hashtags = $e->extractHashtags( $content );
			if ( ! empty( $hashtags ) ) {
				foreach ( $hashtags as $hashtag ) {
					$link    = '<a href="https://instagram.com/explore/tags/' . strtolower( $hashtag ) . '/">#' . $hashtag . '</a>';
					$content = str_replace( '#' . $hashtag, $link, $content );
				}
			}

			// Linkify Instagram usernames
			$content = preg_replace( '/(@([0-9a-zA-z.]+))/im', ' <a href="http://instagram.com/$2/">$1</a>', $content );
		}
		return $content;
	}

	/**
	 * Modify the columns for Instagram postl isting screens
	 *
	 * @param  array  $columns The columns to modify
	 * @return array           The modified columns
	 */
	public function filter_manage_instagram_posts_columns( $columns = array() ) {
		$new_columns    = array(
			'cb'                  => $columns['cb'],
			'title'               => $columns['title'],
			'instagram_photo'     => 'Photo',
			'instagram_permalink' => 'Instagram Permalink',
		);
		$remove_columns = array( 'cb', 'title', 'categories' );
		foreach ( $remove_columns as $col ) {
			unset( $columns[ $col ] );
		}

		return array_merge( $new_columns, $columns );
	}

	/**
	 * Setup the sync dahboard widget
	 */
	public function action_wp_dashboard_setup() {
		wp_add_dashboard_widget(
			'instagram-sync',
			'Instagram Sync',
			array( __CLASS__, 'handle_sync_dashboard_widget' )
		);
	}

	/**
	 * Render the sync dashboard widget
	 */
	public static function handle_sync_dashboard_widget() {
		?>
		<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=instagram&page=' . static::$sync_slug ) ); ?>" method="post">
			<input type="submit" class="button button-primary" value="Sync">
		</form>
		<?php
	}

	/**
	 * Given an HTML page from Instagram, return a JSON of the data from that page
	 *
	 * @link https://github.com/raiym/instagram-php-scraper/blob/849f464bf53f84a93f86d1ecc6c806cc61c27fdc/src/InstagramScraper/Instagram.php#L32
	 *
	 * @param  string $html HTML from an Instagram URL
	 * @return json         Instagram data embedded in the page
	 */
	public static function get_instagram_json_from_html( $html = '' ) {
		// Parse the page response and extract the JSON string
		$arr = explode( 'window.__additionalDataLoaded(', $html );
		if ( ! empty( $arr[1] ) ) {
			$parts    = explode( "/',{", $arr[1] );
			$json_str = '{' . $parts[1];
			$json_str = explode( ');</script>', $json_str );
			$json_str = $json_str[0];
			return json_decode( $json_str );
		}

		$arr = explode( 'window._sharedData = ', $html );
		if ( ! empty( $arr[1] ) ) {
			$json = explode( ';</script>', $arr[1] );
			$json = $json[0];
			return json_decode( $json );
		}
	}

	/**
	 * Fetch a single Instagram page/data from a given code
	 *
	 * @param  string $code Instagram short URL code to fetch
	 * @return JSON        JSON data from the tag page
	 */
	public static function fetch_single_instagram( $code = '' ) {
		if ( ! $code ) {
			return array();
		}

		$args     = array();
		$request  = add_query_arg( $args, 'https://www.instagram.com/p/' . $code . '/' );
		$response = wp_remote_get( $request );
		if ( is_wp_error( $response ) ) {
			echo '<p>' . $response->get_error_message() . '</p>';
			return false;
		}

		return static::get_instagram_json_from_html( $response['body'] );
	}

	/**
	 * Normalize different Instagram API versions in one common format
	 *
	 * @param  Object|false  $node Node object from the Instagram API
	 * @return Object|false  Normalized data
	 */
	public static function normalize_instagram_data( $node = false ) {
		if ( ! $node ) {
			return false;
		}

		// Check if $node is already normalized
		// If so just return the $node back
		if ( isset( $node->_normalized ) ) {
			return $node;
		}

		$output = array(
			'_normalized'              => true,
			'typename'                 => false,
			'id'                       => '',
			'code'                     => '',
			'instagram_url'            => '',
			'full_src'                 => '',
			'thumbnail_src'            => '',
			'video_src'                => '',
			'is_video'                 => false,
			'is_private'               => false,
			'caption'                  => '',
			'timestamp'                => '', // In GMT time
			'owner_id'                 => '',
			'owner_username'           => '',
			'owner_full_name'          => '',
			'location_id'              => '',
			'location_name'            => '',
			'location_slug'            => '',
			'location_has_public_page' => false,
			'children'                 => array(),
		);
		if ( isset( $node->node ) ) {
			$node = $node->node;
		}

		// Common properties for both old and new format
		if ( isset( $node->id ) ) {
			$output['id'] = $node->id;
		}
		if ( isset( $node->is_video ) && $node->is_video ) {
			$output['is_video'] = true;
		}
		if ( isset( $node->owner->id ) ) {
			$output['owner_id'] = $node->owner->id;
		}

		// New API format introduced 4/18/2017
		if ( isset( $node->__typename ) ) {
			$output['typename'] = $node->__typename;
		}
		if ( isset( $node->shortcode ) ) {
			$output['code'] = $node->shortcode;
		}
		if ( isset( $node->display_url ) ) {
			$output['full_src'] = $node->display_url;
		}
		if ( isset( $node->video_url ) ) {
			$output['video_src'] = $node->video_url;
		}
		if ( isset( $node->edge_media_to_caption->edges[0]->node->text ) ) {
			$output['caption'] = $node->edge_media_to_caption->edges[0]->node->text;
		}
		if ( isset( $node->taken_at_timestamp ) ) {
			$output['timestamp'] = $node->taken_at_timestamp;
		}
		if ( isset( $node->location->id ) ) {
			$output['location_id'] = $node->location->id;
		}
		if ( isset( $node->location->has_public_page ) ) {
			$output['location_has_public_page'] = $node->location->has_public_page;
		}
		if ( isset( $node->location->name ) ) {
			$output['location_name'] = $node->location->name;
		}
		if ( isset( $node->location->slug ) ) {
			$output['location_slug'] = $node->location->slug;
		}
		if ( isset( $node->owner->username ) ) {
			$output['owner_username'] = $node->owner->username;
		}
		if ( isset( $node->owner->full_name ) ) {
			$output['owner_full_name'] = $node->owner->full_name;
		}
		if ( isset( $node->owner->is_private ) && $node->owner->is_private ) {
			$output['is_private'] = true;
		}

		// Old API format
		if ( isset( $node->code ) ) {
			$output['code'] = $node->code;
		}
		if ( isset( $node->date ) ) {
			$output['timestamp'] = $node->date;
		}
		if ( isset( $node->display_src ) ) {
			$output['full_src'] = $node->display_src;
		}
		if ( isset( $node->thumbnail_src ) ) {
			$output['thumbnail_src'] = $node->thumbnail_src;
		}
		if ( isset( $node->caption ) ) {
			$output['caption'] = $node->caption;
		}

		if ( ! empty( $output['code'] ) ) {
			$output['instagram_url'] = 'https://instagram.com/p/' . $output['code'] . '/';
		}

		// Check if this node has children
		if (
			isset( $node->edge_sidecar_to_children->edges )
			&& ! empty( $node->edge_sidecar_to_children->edges )
		) {
			$children = $node->edge_sidecar_to_children->edges;

			// Check if the first child is a video
			$first_child = static::normalize_instagram_data( $children[0]->node );
			if ( ! empty( $first_child->video_src ) ) {
				$output['is_video']  = $first_child->is_video;
				$output['video_src'] = $first_child->video_src;
			}

			// Remove the first child since it is the same as the parent node
			array_shift( $children );
			foreach ( $children as $child_node ) {
				$new_node = static::normalize_instagram_data( $child_node->node );
				if ( empty( $new_node->location_id ) && ! empty( $output['location_id'] ) ) {
					$new_node->location_id = $output['location_id'];
				}

				if ( empty( $new_node->location_name ) && ! empty( $output['location_name'] ) ) {
					$new_node->location_name = $output['location_name'];
				}

				if ( empty( $new_node->location_slug ) && ! empty( $output['location_slug'] ) ) {
					$new_node->location_slug = $output['location_slug'];
				}

				if ( empty( $new_node->location_has_public_page ) && ! empty( $output['location_has_public_page'] ) ) {
					$new_node->location_has_public_page = $output['location_has_public_page'];
				}

				$output['children'][] = $new_node;
			}
		}

		return (object) $output;
	}

	/**
	 * Makes sure the node data is from a single page otherwise it fetches a single page's data
	 *
	 * @param  object $node Instagram node data
	 * @return object       Instagram node data
	 */
	public static function get_instagram_data_from_node( $node ) {
		$node = static::normalize_instagram_data( $node );
		// Check to see if $node is already a PostPage object. If not, try and fetch a single instagram post.
		if ( ! $node->typename ) {
			$json = static::fetch_single_instagram( $node->code );
			if ( empty( $json ) || ! $json ) {
				return;
			}

			// It's a single Post page
			if ( ! empty( $json->entry_data->PostPage[0] ) ) {
				$node = $json->entry_data->PostPage[0]->graphql->shortcode_media;
			}
			$node = static::normalize_instagram_data( $node );
		}
		return $node;
	}

	/**
	 * Given an Instagram node, insert a WordPress post
	 *
	 * @param  Object  $node                  Normalized Instagram data
	 * @param  boolean $force_publish_status  Force the post to be published
	 * @return boolean                        Whether the post was inserted successfully or not
	 */
	public static function insert_instagram_post( $node, $post_args = array(), $force_publish_status = true ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/admin.php';
		}

		if ( is_array( $node ) ) {
			$node = (object) $node;
		}

		$posted = date( 'Y-m-d H:i:s', intval( $node->timestamp ) ); // In GMT time
		var_dump( $node );
		$username = $node->owner_username;
		if ( empty( $username ) ) {
			return -1;
		}

		$default_post_args = array(
			'post_title'    => $node->caption,
			'post_content'  => $node->caption,
			'post_status'   => 'publish',
			'post_type'     => static::$post_type,
			'post_date'     => get_date_from_gmt( $posted ),
			'post_date_gmt' => $posted,
			'guid'          => $node->instagram_url,
			'post_parent'   => 0,
			'meta_input'    => array(),
		);
		if ( ! $force_publish_status ) {
			$default_post_args['post_status'] = 'pending';
		}
		$post_args = wp_parse_args( $post_args, $default_post_args );
		if ( $post_args['post_parent'] > 0 ) {
			$post_args['post_name'] = $node->code;
		}
		$post_args['post_content'] = wp_encode_emoji( $post_args['post_content'] );
		$post_args['post_title']   = wp_encode_emoji( $post_args['post_title'] );
		// Strip hash tags out of the title
		$post_args['post_title'] = preg_replace( '/\s#\w+/i', '', $post_args['post_title'] );

		$post_args = apply_filters( 'daddio_pre_insert_instagram_post_args', $post_args, $node );
		// $inserted  = wp_insert_post( $post_args );

		// Handle children
		foreach ( $node->media as $index => $media_item ) {
			if ( $index === 0 ) {
				// Download media and associate it with the inserted parent post
			} else {
				// Download and insert in new post that is a child of the parent post
			}
		}
		/*
		$inserted_child_ids = array();
		if ( ! empty( $node->children ) ) {
			foreach ( $node->children as $child_node ) {
				// Augment the child nodes with the parent node details
				foreach ( $node as $node_key => $node_val ) {
					if ( 'children' === $node_key ) {
						continue;
					}
					if ( empty( $child_node->${'node_key'} ) ) {
						$child_node->${'node_key'} = $node_val;
					}
				}
				// Insert child node making the parent node the parent
				$child_post_args    = array(
					'post_parent' => $inserted,
				);
				$inserted_child_obj = static::insert_instagram_post( $child_node, $child_post_args );
				if ( is_object( $inserted_child_obj ) && isset( $inserted_child_obj->post_id ) ) {
					$inserted_child_ids[] = $inserted_child_obj->post_id;
				}
			}
		}
		*/

		if ( ! $inserted ) {
			// Welp... we tried.
			return false;
		}

		$video_id = false;
		if ( $node->is_video ) {
			$video_file = $node->video_src;
			$tmp        = download_url( $video_file );

			$file_array = array(
				'name'     => $node->code . '.mp4',
				'tmp_name' => $tmp,
			);

			// If error storing temporarily, unlink
			if ( is_wp_error( $tmp ) ) {
				unlink( $file_array['tmp_name'] );
				$file_array['tmp_name'] = '';
			}

			// Do the validation and storage stuff
			$video_id = media_handle_sideload( $file_array, $inserted, $caption );

			// If error storing permanently, unlink
			if ( is_wp_error( $video_id ) ) {
				unlink( $file_array['tmp_name'] );
			}

			// Auto-tag this Instagram post as a video
			wp_set_object_terms( $inserted, 'video', 'category' );
		}

		$attachment_data = array(
			'post_content' => $post_args['post_content'],
			'post_title'   => 'Instagram: ' . $node->code,
			'post_name'    => $node->code,
			'file_name'    => $node->code . '.jpg',
		);
		$attachment_id   = Daddio_Helpers::media_sideload_image_return_id( $node->full_src, $inserted, $caption, $attachment_data );
		update_post_meta( $inserted, 'instagram_username', $node->owner_username );

		// Set the featured image
		add_post_meta( $inserted, '_thumbnail_id', $attachment_id );

		// If we have a video id, store it as post meta
		if ( $video_id ) {
			add_post_meta( $inserted, '_video_id', $video_id );
		}

		do_action( 'daddio_after_instagram_inserted', $inserted, $node );

		return (object) array(
			'post_id'  => $inserted,
			'children' => $inserted_child_ids,
		);
	}

	/**
	 * Get an Instagram username from a given post ID
	 *
	 * @param  Integer $post_id  Post ID get the username for
	 * @return String            Instagram username
	 */
	public static function get_instagram_username( $post_id = false ) {
		$post   = get_post( $post_id );
		$output = get_post_meta( $post->ID, 'instagram_username', true );
		if ( ! $output ) {
			$output = '';
		}
		return $output;
	}

	/**
	 * Conditional check if an Instagram post has already been imported or not
	 *
	 * @param  string $permalink Instagram permalink
	 * @return boolean           Whether the Instagram post has been imported or not
	 */
	public static function does_instagram_permalink_exist( $permalink = '' ) {
		global $wpdb;

		$parts = wp_parse_url( $permalink );
		$id    = $parts['path'];
		$id    = str_replace( '/p/', '', $id );
		$id    = str_replace( '/', '', $id );
		// If a public ID goes private, part of the public ID is in
		// the beginning of the new private ID
		$id = substr( $id, 0, 10 );

		$query = 'SELECT `ID` FROM `' . $wpdb->posts . '` WHERE `guid` LIKE "%' . $id . '%" LIMIT 0,1;';
		return $wpdb->get_var( $query );
	}
}

Daddio_Instagram::get_instance();
