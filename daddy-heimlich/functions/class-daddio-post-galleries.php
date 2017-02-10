<?php
class Daddio_Post_Galleries {

	private $cache_key = 'daddio_post_galleries_nav';
	private $cache_group = 'daddio_post_galleries_cache_group';

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
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'parse_request', array( $this, 'action_parse_request' ) );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );
		add_action( 'daddio_attachment_before_template_part', array( $this, 'action_daddio_attachment_before_template_part' ) );
		add_action( 'daddio_attachment_after_article', array( $this, 'action_daddio_attachment_after_article' ) );
		add_action( 'wpseo_head', array( $this, 'action_wpseo_head' ) );

		add_filter( 'generate_rewrite_rules', array( $this, 'filter_generate_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'redirect_canonical', array( $this, 'filter_redirect_canonical' ), 10, 2 );
		add_filter( 'template_include', array( $this, 'filter_template_include' ) );
		add_filter( 'wpseo_canonical', array( $this, 'filter_wpseo_canonical' ) );
		add_filter( 'post_gallery', array( $this, 'filter_post_gallery' ), 10, 2 );
	}

	public function action_init() {
		// Setup a non-persistant caching group
		wp_cache_add_non_persistent_groups( $this->cache_group );
	}

	// URLs like /category/gallery/ wouldn't work because our rewrite rules tell WordPress this is a post_gallery when it is really not intended that way. This function sets everything right. Sort of.
	public function action_parse_request( $query ) {
		if ( ! isset( $query->query_vars['name'] ) ) {
			return;
		}

		if ( 'category' == $query->query_vars['name'] ) {
			$query->query_vars['name'] = '';
			$query->query_vars['post_gallery'] = '';
			$query->query_vars['category_name'] = 'gallery';
		}
	}

	public function action_template_redirect() {
		if ( $this->is_post_gallery() && ! get_query_var( 'attachment' ) ) {
			$post = get_post();
			wp_redirect( get_permalink( $post->ID ), 301 );
			die();
		}
	}

	public function action_pre_get_posts( $query ) {
		if ( $this->is_post_gallery() && get_query_var( 'attachment' ) && $query->is_main_query() ) {
			$query->set( 'original_name', get_query_var( 'name' ) );
			$query->set( 'name', get_query_var( 'attachment' ) );
		}
	}

	public function action_daddio_attachment_before_template_part( $post ) {
		if ( ! $this->is_post_gallery() ) {
			return;
		}

		$nav = $this->get_nav();
		$parent_post = $nav->parent;
	?>

	<p class="parent-post">
		<a href="<?php echo get_permalink( $parent_post->ID ); ?>">
			<span class="arrow">&larr;</span> <?php echo $parent_post->post_title; ?>
		</a>
	</p>

	<?php
	}

	public function action_daddio_attachment_after_article( $post ) {
		if ( ! $this->is_post_gallery() ) {
			return;
		}

		$nav = $this->get_nav();
		$parent_post = $nav->parent;

		wp_enqueue_script( 'post-gallery' );
	?>

	<nav>
		<a href="<?php echo $nav->next_permalink ?>#content" class="next rounded-button">Next <span class="arrow">&rarr;</span></a>
		<a href="<?php echo $nav->prev_permalink ?>#content" class="prev rounded-button"><span class="arrow">&larr;</span> Prev</a>
		<p class="progress"><?php echo $nav->current;?>/<?php echo $nav->total;?></p>
	</nav>

	<input type="hidden" id="post-gallery-urls" value="<?php esc_attr_e( implode( ' ', $nav->attachments ) ); ?>">
	<?php
	}

	// Add noindex to pages that have the size query var added
	public function action_wpseo_head() {
		if ( $this->is_post_gallery() && get_query_var( 'size' ) ) {
			echo '<meta name="robots" content="noindex">' . PHPEOL;
		}
	}

	public function filter_generate_rewrite_rules( $wp_rewrite ) {
		$new_rules = array(
			'([^/]+)/gallery/([^/]+)?/size/([^/]+)/?' => 'index.php?name=$matches[1]&post_gallery=1&attachment=$matches[2]&size=$matches[3]',
			'([^/]+)/gallery/([^/]+)?/?' => 'index.php?name=$matches[1]&post_gallery=1&attachment=$matches[2]',
			'([^/]+)/gallery/?$' => 'index.php?name=$matches[1]&post_gallery=1&attachment=',
		);
		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
	}

	public function filter_query_vars( $query_vars = array() ) {
		$query_vars[] = 'post_gallery';
		$query_vars[] = 'size';
		return $query_vars;
	}

	public function filter_redirect_canonical( $redirect_url, $requested_url ) {
		if ( $this->is_post_gallery() ) {
			return false;
		}
		return $redirect_url;
	}

	public function filter_template_include( $orig_template = '' ) {
		if ( $this->is_post_gallery() ) {
			if ( $new_template = get_attachment_template() ) {
				return $new_template;
			}

			if ( $new_template = get_single_template() ) {
				return $new_template;
			}
		}
		return $orig_template;
	}

	public function filter_wpseo_canonical( $canonical = '' ) {
		$post = get_post();
		if ( $this->is_post_gallery() ) {
			$nav = $this->get_nav();
			return $this->get_post_gallery_link( $nav->parent->ID, $post->post_name );
		}
		return $canonical;
	}

	/**
	 * Helper Functions
	 */

	public function is_post_gallery() {
		if ( get_query_var( 'post_gallery' ) == '1' ) {
			return true;
		}
		return false;
	}

	public function get_gallery_posts( $post_id = 0 ) {
		if ( ! $this->is_post_gallery() || ! get_query_var( 'attachment' ) ) {
			return array();
		}

		$post_id = intval( $post_id );
		if ( ! $post_id ) {
			$post = get_post();
			$post_id = $post->ID;
		}

		$parent_post = get_page_by_path( get_query_var( 'original_name' ), 'OBJECT', get_post_types() );

		$defaults = array(
			'order' => 'ASC',
			'orderby' => 'post__in',
			'id' => $parent_post->ID,
			'ids' => '',
			'include' => '',
			'exclude' => '',
			'numberposts' => -1,

			// For performance. See https://10up.github.io/Engineering-Best-Practices/php/
			'no_found_rows' => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);

		// Find all [gallery] shortcodes in the parent post and process them to see which gallery the current $post belongs to
		preg_match_all( '/\[gallery(.+)?\]/i', $parent_post->post_content, $matches );
		foreach ( $matches[1] as $atts ) {
			$atts = shortcode_parse_atts( $atts );
			$sort_args = wp_parse_args( $atts, $defaults );
			if ( $haystack = $sort_args['ids'] ) {
				// Make it easier to match IDs while avoiding partial matches. Searching for "1" in "9,10,11" would be a false positive so we normalize everything with a trailing comma.
				$haystack .= ',';
				$needle = $post_id . ',';
				$we_good = strstr( $haystack, $needle );
				if ( $we_good ) {
					// We found the right gallery, break out of the loop...
					break;
				}
			}
		}

		$post_in = explode( ',', $sort_args['ids'] );
		$args = array(
			'post_type' => 'attachment',
			'orderby' => $sort_args['orderby'],
			'order' => $sort_args['order'],
			'post__in' => $post_in,
			'numberposts' => $sort_args['numberposts'],
		);

		$output = (object) array(
			'parent_id' => $parent_post->ID,
			'attachments' => array(),
		);
		foreach ( get_posts( $args ) as $post ) :
			$output->attachments[] = (object) array(
				'ID' => $post->ID,
				'post_name' => $post->post_name,
				'post_title' => $post->post_title,
				'post_url' => get_permalink( $post->ID ),
				'post_gallery_url' => $this->get_post_gallery_link( $parent_post->ID, $post->post_name ),
			);
		endforeach;

		return $output;
	}

	public function get_nav() {
		// Inter request caching
		if ( $output = wp_cache_get( $this->cache_key, $this->cache_group ) ) {
			return $output;
		}

		$posts = $this->get_gallery_posts();
		if ( ! $posts ) {
			return array();
		}

		$total_attachments = count( $posts->attachments );
		$count = 0;
		$current = $next = $prev = 0;
		$post_id = get_the_ID();
		$attachments = array();
		foreach ( $posts->attachments as $attachment ) {
			if ( $attachment->ID == $post_id ) {
				$current = $count;
				$next = $count + 1;
				$prev = $count - 1;
				if ( $prev < 0 ) {
					$prev = $total_attachments - 1;
				}
				if ( $next >= $total_attachments ) {
					$next = 0;
				}
			}

			$attachments[] = $attachment->post_gallery_url;

			$count++;
		}

		$parent_permalink = get_permalink( $posts->parent_id );
		$next_slug = $posts->attachments[ $next ]->post_name;
		$prev_slug = $posts->attachments[ $prev ]->post_name;

		$output = (object) array(
			'attachments' => $attachments,
			'parent' => get_post( $posts->parent_id ),
			'next_permalink' => $this->get_post_gallery_link( $posts->parent_id, $next_slug ),
			'prev_permalink' => $this->get_post_gallery_link( $posts->parent_id, $prev_slug ),
			'total' => $total_attachments,
			'current' => $current + 1,
		);
		wp_cache_set( $this->cache_key, $output, $this->cache_group );
		return $output;
	}

	public function get_post_gallery_link( $parent_id = 0, $attachment_slug = '' ) {
		if ( ! $parent_id || ! $attachment_slug ) {
			return '';
		}
		return get_permalink( $parent_id ) . 'gallery/' . $attachment_slug . '/';
	}

	public function filter_post_gallery( $nothing, $attr = array() ) {
		$post = get_post();

		static $instance = 0;
		$instance++;

		if ( ! empty( $attr['ids'] ) ) {
			// 'ids' is explicitly ordered, unless you specify otherwise.
			if ( empty( $attr['orderby'] ) ) {
				$attr['orderby'] = 'post__in';
			}
			$attr['include'] = $attr['ids'];
		}

		$atts = shortcode_atts( array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			'columns'    => 3,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'link'       => '',
		), $attr, 'gallery' );

		$id = intval( $atts['id'] );

		if ( ! empty( $atts['include'] ) ) {

			$_attachments = get_posts( array(
				'include' => $atts['include'],
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'order' => $atts['order'],
				'orderby' => $atts['orderby'],
			) );

			$attachments = array();
			foreach ( $_attachments as $key => $val ) {
				$attachments[ $val->ID ] = $_attachments[ $key ];
			}

		} elseif ( ! empty( $atts['exclude'] ) ) {

			$attachments = get_children( array(
				'post_parent' => $id,
				'exclude' => $atts['exclude'],
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'order' => $atts['order'],
				'orderby' => $atts['orderby'],
			) );

		} else {
			$attachments = get_children( array(
				'post_parent' => $id,
				'post_status' => 'inherit',
				'post_type' => 'attachment',
				'post_mime_type' => 'image',
				'order' => $atts['order'],
				'orderby' => $atts['orderby'],
			) );

		}

		if ( empty( $attachments ) ) {
			return '';
		}

		if ( is_feed() ) {
			$output = "\n";
			foreach ( $attachments as $att_id => $attachment ) {
				$output .= daddio_get_attachment_link( $att_id, $atts['size'], true ) . PHPEOL;
			}
			return $output;
		}

		$columns = intval( $atts['columns'] );
		$img_size = $atts['size'];
		$itemwidth = $columns > 0 ? floor( 100 / $columns ) : 100;

		$size_class = sanitize_html_class( $atts['size'] );
		$output = "<section class=\"gallery gallery-{$columns}-column gallery-size-{$img_size}\">";

		$i = 0;
		foreach ( $attachments as $id => $attachment ) {

			$attr = array( 'class' => 'attachment-' . $atts['size'] );
			if ( trim( $attachment->post_excerpt ) ) {
				$attr['aria-describedby'] = "selector-$id";
			}

			$image_meta  = wp_get_attachment_metadata( $id );
			$img_width = $image_meta['width'];
			$img_height = $image_meta['height'];
			if ( isset( $image_meta['sizes'][ $atts['size'] ] ) ) {
				$img_width = $image_meta['sizes'][ $atts['size'] ]['width'];
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
				$image_output = daddio_get_attachment_link( $id, $atts['size'], false, false, false, $attr );
			} elseif ( ! empty( $atts['link'] ) && 'none' === $atts['link'] ) {
				$image_output = wp_get_attachment_image( $id, $atts['size'], false, $attr );
			} else {
				$image_output = daddio_get_attachment_link( $id, $atts['size'], true, false, false, $attr );
			}

			$output .= "$image_output";
			$i++;
		}

		$output .= '</section>';

		return $output;
	}
}
Daddio_Post_Galleries::get_instance();

function daddio_get_post_gallery_link( $parent_id = 0, $attachment_slug = '' ) {
	$instance = Daddio_Post_Galleries::get_instance();
	return $instance->get_post_gallery_link( $parent_id, $attachment_slug );
}
