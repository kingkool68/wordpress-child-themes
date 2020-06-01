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
		// Custom image sizes
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
	public function filter_img_caption_shortcode( $string = '', $attr = array(), $content = null ) {
		$atts = shortcode_atts(
			array(
				'id'      => '',
				'align'   => 'alignnone',
				'width'   => '',
				'caption' => '',
				'class'   => '',
			),
			$attr
		);

		if ( (int) $atts['width'] < 1 || empty( $atts['caption'] ) ) {
			return $content;
		}

		$described_by = '';
		$id           = '';
		if ( $atts['id'] ) {
			// Underscores in attributes are yucky.
			$id_attr      = str_replace( '_', '-', esc_attr( $atts['id'] ) );
			$described_by = 'aria-describedby="' . $id_attr . '"';
			$id           = 'id="' . esc_attr( $id_attr ) . '" ';
		}

		$inline_width   = '';
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
				if ( ! empty( $data->html ) && is_string( $data->html ) && ! in_array( $data->provider_url, $not_rich_embeds, true ) ) {
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
		$post     = get_post( $post_id );
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
	 * @param  integer $post_id Post ID of Instagram post
	 * @return string           HTML of media
	 */
	public static function get_the_instagram_media( $post_id = 0, $position = 1 ) {
		$post              = get_post( $post_id );
		$featured_image_id = get_post_thumbnail_id( $post->ID );
		$featured_video_id = get_post_meta( $post->ID, '_video_id', true );
		if ( $featured_video_id ) {
			$video_src  = wp_get_attachment_url( $featured_video_id );
			$video_meta = wp_get_attachment_metadata( $featured_video_id );
			$poster     = wp_get_attachment_image_src( $featured_image_id, 'full' );
			if ( ! empty( $poster[0] ) ) {
				$poster = $poster[0];
			}
			if ( $video_src && $poster ) {
				$args = array(
					'src'    => $video_src,
					'poster' => $poster,
				);

				// Set the width and height to null to make the player responsive
				// We store the src_width and src_height in case we want to
				// calculate a proper aspect ratio for responsive scaling
				if ( ! empty( $video_meta['width'] ) ) {
					$args['src_width'] = $video_meta['width'];
					$args['width']     = null;
				}
				if ( ! empty( $video_meta['height'] ) ) {
					$args['src_height'] = $video_meta['height'];
					$args['height']     = null;
				}
				$output = wp_video_shortcode( $args );

				// Set the width and height of the <video> element to 100%
				// CSS is already in place to make videos responsive
				// and the 100% values make poster images responsive as well
				if ( ! empty( $args['src_width'] ) && ! empty( $args['src_height'] ) ) {
					$aspect_ratio = $args['src_height'] / $args['src_width'] * 100;
					$aspect_ratio = round( $aspect_ratio, 2 );
					$output       = str_replace( 'width="0"', 'data-aspect-ratio="' . $aspect_ratio . '%" width="100%"', $output );
					$output       = str_replace( 'height="0"', 'height="100%"', $output );
				}

				return $output;
			}
		}

		$loading = 'lazy';
		if ( $position < 2 ) {
			$loading = 'eager';
		}
		$img_attrs = array(
			'class'   => 'aligncenter from-instagram',
			'alt'     => '',
			'loading' => $loading,
			'sizes' => '(min-width: 740px) 644px, calc(92.38vw - 21px)',
		);
		return wp_get_attachment_image( $featured_image_id, 'full', false, $img_attrs );
	}
}
Daddio_Media::get_instance();
