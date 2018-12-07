<?php
class Daddio_Post_Galleries {

	private static $cache_key   = 'daddio_post_galleries_nav';
	private static $cache_group = 'daddio_post_galleries_cache_group';

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
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );
		add_action( 'wpseo_head', array( $this, 'action_wpseo_head' ) );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'generate_rewrite_rules', array( $this, 'filter_generate_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'redirect_canonical', array( $this, 'filter_redirect_canonical' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'filter_template_include' ) );
		add_filter( 'wpseo_canonical', array( $this, 'filter_wpseo_canonical' ) );
		add_filter( 'post_gallery', array( $this, 'filter_post_gallery' ), 10, 2 );
	}

	/**
	 * Setup a non persistant cache group
	 */
	public function action_init() {
		// Setup a non-persistant caching group
		wp_cache_add_non_persistent_groups( self::$cache_group );

		// Register script
		wp_register_script(
			$handle    = 'post-gallery',
			get_template_directory_uri() . '/js/post-gallery' . Daddio_Scripts_Styles::get_js_suffix(),
			$deps      = array( 'jquery' ),
			$ver       = null,
			$in_footer = true
		);
	}

	/**
	 * Make URLs like /category/gallery/ work due to the rewrite rules
	 * which tell WordPress the request is a post_gallery when it is really not.
	 *
	 * @param  WP_Query $query WP_Query to modify
	 */
	public function action_parse_request( $query ) {
		if ( empty( $query->query_vars['name'] ) ) {
			return;
		}

		if (
			'category' == $query->query_vars['name']
			&& '1' == $query->query_vars['post_gallery']
		) {
			$query->query_vars['name'] = '';
			$query->query_vars['post_gallery'] = '';
			$query->query_vars['category_name'] = 'gallery';
		}
	}

	/**
	 * If the request is a post gallery and not for an attachment then redirect
	 */
	public function action_template_redirect() {
		if ( self::is_post_gallery() && ! get_query_var( 'attachment' ) ) {
			wp_redirect( get_permalink( get_the_ID() ), 301 );
			die();
		}
	}

	/**
	 * Juggle some query vars for post gallery requests
	 *
	 * @param  WP_Query $query WP_Query to modify
	 */
	public function action_pre_get_posts( $query ) {
		if (
			self::is_post_gallery()
			&& get_query_var( 'attachment' )
			&& $query->is_main_query()
		) {
			$query->set( 'original_name', get_query_var( 'name' ) );
			$query->set( 'name', get_query_var( 'attachment' ) );
		}
	}

	/**
	 * Add noindex to pages that have the size query var added
	 */
	public function action_wpseo_head() {
		if ( self::is_post_gallery() && get_query_var( 'size' ) ) {
			echo '<meta name="robots" content="noindex">' . PHPEOL;
		}
	}

	/**
	 * Add rewrite rules for post galleries.
	 *
	 * @param  object $wp_rewrite WP_Rewrite object to modify
	 */
	public function filter_generate_rewrite_rules( $wp_rewrite ) {
		$new_rules = array(
			'([^/]+)/gallery/([^/]+)?/size/([^/]+)/?' => 'index.php?name=$matches[1]&post_gallery=1&attachment=$matches[2]&size=$matches[3]',
			'([^/]+)/gallery/([^/]+)?/?' => 'index.php?name=$matches[1]&post_gallery=1&attachment=$matches[2]',
			'([^/]+)/gallery/?$' => 'index.php?name=$matches[1]&post_gallery=1&attachment=',
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	/**
	 * Make WordPress aware of our custom query vars
	 *
	 * @param  array  $query_vars Previously registered query vars
	 * @return array              Modified query_vars
	 */
	public function filter_query_vars( $query_vars = array() ) {
		$query_vars[] = 'post_gallery';
		$query_vars[] = 'size';
		return $query_vars;
	}

	/**
	 * If the request is for a post gallery then kill the canonical redirect
	 *
	 * @param  string $redirect_url  URL WordPress is trying to redirect to
	 * @param  string $requested_url The requested URL
	 * @return string|false          The URL to redirect to or false to cancel the redirect
	 */
	public function filter_redirect_canonical( $redirect_url = '', $requested_url = '' ) {
		if ( self::is_post_gallery() ) {
			return false;
		}
		return $redirect_url;
	}

	/**
	 * Load the appropriate template for a post gallery request
	 * Defaults to the attachment template and fallsback to the single template
	 *
	 * @param  string $orig_template The original template to be used
	 * @return string                The possibly modified template to use
	 */
	public function filter_template_include( $orig_template = '' ) {
		if ( self::is_post_gallery() ) {
			if ( $new_template = get_attachment_template() ) {
				return $new_template;
			}

			if ( $new_template = get_single_template() ) {
				return $new_template;
			}
		}
		return $orig_template;
	}

	/**
	 * For post_gallery requests set the canonical URL to the post gallery link
	 *
	 * @param  string $canonical The canonical URL
	 * @return string            The modified canonical URL
	 */
	public function filter_wpseo_canonical( $canonical = '' ) {
		$post = get_post();
		if ( self::is_post_gallery() ) {
			$nav = self::get_nav();
			return self::get_post_gallery_link( $nav->parent->ID, $post->post_name );
		}
		return $canonical;
	}

	/**
	 * Is the request a post gallery request?
	 *
	 * @return boolean Whether the request is a post gallery request
	 */
	public static function is_post_gallery() {
		if ( get_query_var( 'post_gallery' ) == '1' ) {
			return true;
		}
		return false;
	}

	/**
	 * Get data about all of the gallery posts associated with a given post ID
	 *
	 * @param  integer $post_id ID of the post to get data for
	 * @return object           Post gallery data
	 */
	public static function get_gallery_posts( $post_id = 0 ) {
		if ( ! self::is_post_gallery() || ! get_query_var( 'attachment' ) ) {
			return array();
		}

		$post    = get_post( $post_id );
		$post_id = $post->ID;

		$parent_post = get_page_by_path( get_query_var( 'original_name' ), 'OBJECT', get_post_types() );

		$defaults = array(
			'order'                  => 'ASC',
			'orderby'                => 'post__in',
			'id'                     => $parent_post->ID,
			'ids'                    => '',
			'include'                => '',
			'exclude'                => '',
			'numberposts'            => 99,

			// For performance. See https://10up.github.io/Engineering-Best-Practices/php/
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		// Find all [gallery] shortcodes in the parent post and process them
		// to see which gallery the current $post belongs to
		preg_match_all( '/\[gallery(.+)?\]/i', $parent_post->post_content, $matches );
		foreach ( $matches[1] as $atts ) {
			$atts      = shortcode_parse_atts( $atts );
			$sort_args = wp_parse_args( $atts, $defaults );
			if ( ! empty( $sort_args['ids'] ) ) {
				$haystack = $sort_args['ids'];
				// Make it easier to match IDs while avoiding partial matches.
				// Searching for "1" in "9,10,11" would be a false positive
				// so we normalize everything with a trailing comma.
				$haystack .= ',';
				$needle    = $post_id . ',';
				$we_good   = strstr( $haystack, $needle );
				if ( $we_good ) {
					// We found the right gallery, break out of the loop...
					break;
				}
			}
		}

		$post_in = explode( ',', $sort_args['ids'] );
		$args    = array(
			'post_type'   => 'attachment',
			'orderby'     => $sort_args['orderby'],
			'order'       => $sort_args['order'],
			'post__in'    => $post_in,
			'numberposts' => $sort_args['numberposts'],
		);

		$output = (object) array(
			'parent_id'   => $parent_post->ID,
			'attachments' => array(),
		);
		foreach ( get_posts( $args ) as $post ) :
			$output->attachments[] = (object) array(
				'ID'               => $post->ID,
				'post_name'        => $post->post_name,
				'post_title'       => $post->post_title,
				'post_url'         => get_permalink( $post->ID ),
				'post_gallery_url' => self::get_post_gallery_link( $parent_post->ID, $post->post_name ),
			);
		endforeach;

		return $output;
	}

	/**
	 * Get information about the next / previous gallery items
	 *
	 * @return object Gallery post navigation data
	 */
	public static function get_nav() {
		// Inter request caching
		if ( $output = wp_cache_get( self::$cache_key, self::$cache_group ) ) {
			return $output;
		}

		$posts = self::get_gallery_posts();
		if ( ! $posts ) {
			return array();
		}

		$total_attachments = count( $posts->attachments );
		$count             = 0;
		$current           = $next = $prev = 0;
		$post_id           = get_the_ID();
		$attachments       = array();
		foreach ( $posts->attachments as $attachment ) {
			if ( $attachment->ID == $post_id ) {
				$current = $count;

				$next    = $count + 1;
				if ( $next >= $total_attachments ) {
					$next = 0;
				}

				$prev    = $count - 1;
				if ( $prev < 0 ) {
					$prev = $total_attachments - 1;
				}
			}

			$attachments[] = $attachment->post_gallery_url;

			$count++;
		}

		$parent_permalink = get_permalink( $posts->parent_id );
		$next_slug        = $posts->attachments[ $next ]->post_name;
		$prev_slug        = $posts->attachments[ $prev ]->post_name;

		$output = (object) array(
			'attachments'    => $attachments,
			'parent'         => get_post( $posts->parent_id ),
			'next_permalink' => self::get_post_gallery_link( $posts->parent_id, $next_slug ),
			'prev_permalink' => self::get_post_gallery_link( $posts->parent_id, $prev_slug ),
			'total'          => $total_attachments,
			'current'        => $current + 1,
		);
		wp_cache_set( self::$cache_key, $output, self::$cache_group );
		return $output;
	}

	/**
	 * Get the post gallery permalink
	 *
	 * @param  integer $parent_id       ID of the parent post the gallery belongs to
	 * @param  string  $attachment_slug Slug of the gallery item
	 * @return string                   URL of post gallery permalink
	 */
	public static function get_post_gallery_link( $parent_id = 0, $attachment_slug = '' ) {
		if ( ! $parent_id || ! $attachment_slug ) {
			return '';
		}
		return get_permalink( $parent_id ) . 'gallery/' . $attachment_slug . '/';
	}

	/**
	 * Format the post gallery output markup using HTML5 elements
	 *
	 * @param  string $nothing Nohing
	 * @param  array  $attr    Attributes
	 * @return string          HTML of the gallery rendered
	 */
	public function filter_post_gallery( $nothing, $attr = array() ) {
		$post = get_post();

		$instance = 0;
		$instance++;

		if ( ! empty( $attr['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) ) {
				$attr['orderby'] = 'post__in';
			}
			$attr['include'] = $attr['ids'];
		}

		$atts = shortcode_atts( array(
			'order'   => 'ASC',
			'orderby' => 'menu_order ID',
			'id'      => $post ? $post->ID : 0,
			'columns' => 3,
			'size'    => 'thumbnail',
			'include' => '',
			'exclude' => '',
			'link'    => '',
		), $attr, 'gallery' );

		$id = intval( $atts['id'] );

		if ( ! empty( $atts['include'] ) ) {

			$_attachments = get_posts( array(
				'include'        => $atts['include'],
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[ $val->ID ] = $_attachments[ $key ];
			}
		} elseif ( ! empty( $atts['exclude'] ) ) {

			$attachments = get_children( array(
				'post_parent'    => $id,
				'exclude'        => $atts['exclude'],
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			) );

		} else {
			$attachments = get_children( array(
				'post_parent'    => $id,
				'post_status'    => 'inherit',
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'order'          => $atts['order'],
				'orderby'        => $atts['orderby'],
			) );

		}

		if ( empty( $attachments ) ) {
			return '';
		}

		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment ) {
				$output .= $this->get_attachment_link( $att_id, $atts['size'], true ) . PHPEOL;
			}
			return $output;
		}

		$columns    = intval( $atts['columns'] );
		$img_size   = $atts['size'];
		$itemwidth  = $columns > 0 ? floor( 100 / $columns ) : 100;

		$size_class = sanitize_html_class( $atts['size'] );
		$output = "<section class=\"gallery gallery-{$columns}-column gallery-size-{$img_size}\">";

		$i = 0;
		foreach ( $attachments as $id => $attachment ) {

			$attr = array( 'class' => 'attachment-' . $atts['size'] );
			if ( trim( $attachment->post_excerpt ) ) {
				$attr['aria-describedby'] = "selector-$id";
			}

			$image_meta  = wp_get_attachment_metadata( $id );
			$img_width   = $image_meta['width'];
			$img_height  = $image_meta['height'];
			if ( isset( $image_meta['sizes'][ $atts['size'] ] ) ) {
				$img_width  = $image_meta['sizes'][ $atts['size'] ]['width'];
				$img_height = $image_meta['sizes'][ $atts['size'] ]['height'];
			}
			$orientation = '';
			if ( isset( $img_height, $img_width ) ) {
				$orientation = ( $img_height > $img_width ) ? 'portrait' : 'landscape';
				if ( $img_height == $img_width ) {
					$orientation = 'square';
				}
			}

			$attr['class'] .= ' ' . $orientation;

			if ( 0 == $i % $columns ) {
				$attr['class'] .= ' end';
			}

			if ( ! empty( $atts['link'] ) && 'file' === $atts['link'] ) {
				$image_output = $this->get_attachment_link( $id, $atts['size'], false, false, false, $attr );
			} elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
				$image_output = wp_get_attachment_image( $id, $atts['size'], false, $attr );
			} else {
				$image_output = $this->get_attachment_link( $id, $atts['size'], true, false, false, $attr );
			}

			$output .= "$image_output";
			$i++;
		}

		$output .= '</section>';

		return $output;
	}

	/**
	 * Our own version of get_attachment_link that uses the
	 * Daddio Get Post Gallery Link method instead of get_attachment_link
	 *
	 * @param integer      $id        Post ID of the attachment
	 * @param string|array $size      Image size. Accepts any valid image size, or an array of width and height values in pixels (in that order).
	 * @param boolean      $permalink Whether to add permalink to image.
	 * @param boolean      $icon      Whether the attachment is an icon.
	 * @param boolean      $text      Link text to use. Activated by passing a string, false otherwise.
	 * @param string|array $attr      Array or string of attributes.
	 * @return string                 URL of the attachment
	 */
	public function get_attachment_link( $id = 0, $size = 'thumbnail', $permalink = false, $icon = false, $text = false, $attr = '' ) {
		$id = intval( $id );
		$_post = get_post( $id );
		$parent_post = get_post();

		if ( empty( $_post ) || ( 'attachment' != $_post->post_type ) || ! $url = wp_get_attachment_url( $_post->ID ) ) {
			return __( 'Missing Attachment' );
		}
		if ( $permalink ) {
			//$url = get_attachment_link( $_post->ID );
			$url = self::get_post_gallery_link( $parent_post->ID, $_post->post_name );
		}

		if ( $text ) {
			$link_text = $text;
		} elseif ( $size && 'none' != $size ) {
			$link_text = wp_get_attachment_image( $id, $size, $icon, $attr );
		} else {
			$link_text = '';
		}

		if ( trim( $link_text ) == '' ) {
			$link_text = $_post->post_title;
		}

		/**
		 * Filter a retrieved attachment page link.
		 *
		 * @since 2.7.0
		 *
		 * @param string      $link_html The page link HTML output.
		 * @param int         $id        Post ID.
		 * @param string      $size      Image size. Default 'thumbnail'.
		 * @param bool        $permalink Whether to add permalink to image. Default false.
		 * @param bool        $icon      Whether to include an icon. Default false.
		 * @param string|bool $text      If string, will be link text. Default false.
		 */
		return apply_filters( 'wp_get_attachment_link', "<a href='$url'>$link_text</a>", $id, $size, $permalink, $icon, $text );
	}

	/**
	 * Render the parent post link at the top of the attachment template
	 */
	public static function render_parent_post_link() {
		if ( ! self::is_post_gallery() ) {
			return;
		}

		$nav         = self::get_nav();
		$parent_post = $nav->parent;

		$context = array(
			'parent_post_url'   => get_permalink( $parent_post->ID ),
			'parent_post_title' => get_the_title( $parent_post ),
		);
		return Sprig::render( 'post-gallery-parent-post-link.twig', $context );
	}

	/**
	 * Render the gallery navigation at the bottom of the attachment template
	 */
	public static function render_gallery_navigation() {
		if ( ! self::is_post_gallery() ) {
			return;
		}

		$nav = self::get_nav();
		$parent_post = $nav->parent;

		wp_enqueue_script( 'post-gallery' );

		$context = array(
			'next_url'          => $nav->next_permalink . '#content',
			'prev_url'          => $nav->prev_permalink . '#content',
			'current'           => $nav->current,
			'total'             => $nav->total,
			'post_gallery_urls' => implode( ' ', $nav->attachments ),

		);
		return Sprig::render( 'post-gallery-navigation.twig', $context );
	}
}
Daddio_Post_Galleries::get_instance();
