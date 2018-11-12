<?php
class Daddio_Media {
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
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'image_size_names_choose', array( $this, 'filter_image_size_names_choose' ) );
		add_filter( 'img_caption_shortcode', array( $this, 'filter_img_caption_shortcode' ), 1, 3 );
		add_filter( 'oembed_dataparse', array( $this, 'filter_oembed_dataparse' ), 10, 3 );
		add_filter( 'attachment_link', array( $this, 'filter_attachment_link' ), 2, 10 );
		add_filter( 'shortcode_atts_video', array( $this, 'filter_shortcode_atts_video' ), 10, 4 );
	}

	/**
	 * Setup custom image sizes
	 */
	public function action_init() {
		//Custom image sizes
		add_image_size( '320-wide', 320 );
		add_image_size( '360-wide', 360 );
		add_image_size( '480-wide', 480 );
		add_image_size( '640-wide', 640 );
		add_image_size( '800-wide', 800 );
	}

	/**
	 * Add image sizes to the list of image sizes in the media library
	 *
	 * @param  array  $sizes Sizes to add to the list
	 * @return array         Modified list of sizes
	 */
	public function filter_image_size_names_choose( $sizes = array() ) {
		$addsizes = array(
			'350-wide' => '350 Wide',
		);
		return array_merge( $sizes, $addsizes );
	}

	/**
	 * Make image captions use figure and figcaption markup and use aria attributes for accessibility
	 *
	 * @param  string $string  Original image caption
	 * @param  array  $attr    Attributes
	 * @param  string $content Content inside of the caption shortcode
	 * @return string          Modified image caption
	 */
	function filter_img_caption_shortcode( $string = '', $attr = array(), $content = null ) {
		$atts = shortcode_atts( array(
			'id'    => '',
			'align' => 'alignnone',
			'width' => '',
			'caption' => '',
			'class' => '',
		), $attr );

		if ( (int) $atts['width'] < 1 || empty( $atts['caption'] ) ) {
			return $content;
		}

		$described_by = '';
		$id = '';
		if ( $atts['id'] ) {
			// Underscores in attributes are yucky.
			$id_attr = str_replace( '_', '-', esc_attr( $atts['id'] ) );
			$described_by = 'aria-describedby="' . $id_attr . '"';
			$id = 'id="' . esc_attr( $id_attr ) . '" ';
		}

		$inline_width = '';
		if ( 'alignleft' === $atts['align'] || 'alignright' === $atts['align'] ) {
			// $inline_width = 'style="width: '. (10 + (int) $width) . 'px"';
		}
		$atts['class'] .= ' wp-caption ' . esc_attr( $atts['align'] );

		return '<figure ' . $described_by . 'class="' . $atts['class'] . '" ' . $inline_width . '>' .
			do_shortcode( $content ) .
			'<figcaption class="wp-caption-text" ' . $id . '>' . $atts['caption'] . '</figcaption>' .
			'</figure>';
	}

	/**
	 * Wrap embeds in an element to make them responsive via CSS
	 *
	 * @param  string $return Embed code to return
	 * @param  object $data   Data about the embed
	 * @param  string $url    URL of the embed
	 * @return string         Modified embed code
	 */
	public function filter_oembed_dataparse( $return = '', $data, $url ) {
		$not_rich_embeds = array( 'https://twitter.com' );
		switch ( $data->type ) {
			case 'video':
			case 'rich':
				if ( ! empty( $data->html ) && is_string( $data->html ) && ! in_array( $data->provider_url, $not_rich_embeds ) ) {
					$return = '<div class="responsive-embed">' . $return . '</div>';
				}
			break;
		}
		return $return;
	}

	/**
	 * Modify the URL of attachment links so they can be unique
	 *
	 * @param  string $link     Original attachment link
	 * @param  integer $post_id Post ID of the attachemnt
	 * @return string           New attachment link
	 */
	public function filter_attachment_link( $link, $post_id ) {
		$post = get_post( $post_id );
		$new_link = get_site_url() . '/attachment/' . $post->post_name . '/';
		return $new_link;
	}

	/**
	 * Modify shortcode attributes for [video]
	 *
	 * Settng preload to none improves load time performance
	 *
	 * @param  array $out       Shortcode attributes to modify
	 * @return array            Modified shortcode attributes
	 */
	public function filter_shortcode_atts_video( $out ) {
		$out['preload'] = 'none';
		return $out;
	}

	/**
	 * Display Instagram photo or video for a given Instagram post
	 *
	 * @TODO: Make this return a string 
	 *
	 * @param  integer $post_id Post ID of Instagram post
	 * @return string           HTML of media
	 */
	public function the_instagram_media( $post_id = 0 ) {
		$post_id = absint( $post_id );
		if ( ! $post_id ) {
			$post_id = '';
		}
		$post = get_post( $post_id );
		$featured_image_id = get_post_thumbnail_id( $post->ID );
		$featured_video_id = get_post_meta( $post->ID, '_video_id', true );
		if ( $featured_video_id ) {
			$video_src = wp_get_attachment_url( $featured_video_id );
			$poster = wp_get_attachment_image_src( $featured_image_id, 'full' );
			if ( ! empty( $poster[0] ) ) {
				$poster = $poster[0];
			}
			if ( $video_src && $poster ) {
				$args = array(
					'src' => $video_src,
					'poster' => $poster,
				);
				echo wp_video_shortcode( $args );
				return;
			}
		}

		$img_attrs = array(
			'class' => 'aligncenter from-instagram',
			'alt'   => '',
		);
		echo wp_get_attachment_image( $featured_image_id, 'full', false, $img_attrs );
	}
}
Daddio_Media::get_instance();

/* Helpers */

function daddio_get_attachment_link( $id = 0, $size = 'thumbnail', $permalink = false, $icon = false, $text = false, $attr = '' ) {
	$id = intval( $id );
	$_post = get_post( $id );
	$parent_post = get_post();

	if ( empty( $_post ) || ( 'attachment' != $_post->post_type ) || ! $url = wp_get_attachment_url( $_post->ID ) ) {
		return __( 'Missing Attachment' );
	}
	if ( $permalink ) {
		//$url = get_attachment_link( $_post->ID );
		$url = daddio_get_post_gallery_link( $parent_post->ID, $_post->post_name );
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

function daddio_svg_icon( $icon = '' ) {
	if ( ! $icon ) {
		return;
	}
	$icon = esc_attr( $icon );

	return '<svg class="icon icon-' . $icon . '" role="img"><use xlink:href="#icon-' . $icon . '"></use></svg>';
}

function the_instagram_media( $post_id = 0 ) {
	$instance = Daddio_Media::get_instance();
	return $instance->the_instagram_media( $post_id );
}
