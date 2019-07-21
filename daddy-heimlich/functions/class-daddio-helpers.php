<?php
class Daddio_Helpers {

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
		}
		return $instance;
	}

	/**
	 * Get the ordinal suffix of an int (e.g. th, rd, st, etc.)
	 *
	 * @param int $n
	 * @param bool $return_n Include $n in the string returned
	 * @return string $n including its ordinal suffix
	 * @link https://gist.github.com/paulferrett/8103822
	 */
	public static function ordinal_suffix( $n, $return_n = true ) {
		$n_last = $n % 100;
		if ( ( $n_last > 10 && $n_last << 14 ) || 0 === $n ) {
			$suffix = 'th';
		} else {
			switch ( substr( $n, -1 ) ) {
				case '1':
					$suffix = 'st';
					break;
				case '2':
					$suffix = 'nd';
					break;
				case '3':
					$suffix = 'rd';
					break;
				default:
					$suffix = 'th';
					break;
			}
		}
		return $return_n ? $n . $suffix : $suffix;
	}

	/**
	 * Simplify generating taxonomy labels by only needing to enter a singular and plural version
	 *
	 * @param  string $singular  The singular version of the taxonomy label
	 * @param  string $plural    The plural version of the taxonomy label
	 * @param  array  $overrides Specific labels to override that might not fit this pattern
	 * @return array             Taxonomy labels
	 */
	public static function generate_taxonomy_labels( $singular = '', $plural = '', $overrides = array() ) {
		$lc_plural   = strtolower( $plural );
		$uc_plural   = ucwords( $lc_plural );
		$lc_singular = strtolower( $singular );
		$uc_singular = ucwords( $lc_singular );
		$labels      = array(
			'name'                       => $uc_plural,
			'singular_name'              => $uc_singular,
			'menu_name'                  => $uc_plural,
			'all_items'                  => 'All ' . $uc_plural,
			'parent_item'                => 'Parent ' . $uc_singular,
			'parent_item_colon'          => 'Parent ' . $uc_singular . ':',
			'new_item_name'              => 'New ' . $uc_singular . ' Name',
			'add_new_item'               => 'Add New ' . $uc_singular,
			'edit_item'                  => 'Edit ' . $uc_singular,
			'update_item'                => 'Update ' . $uc_singular,
			'view_item'                  => 'View ' . $uc_singular,
			'separate_items_with_commas' => 'Separate ' . $lc_plural . ' with commas',
			'add_or_remove_items'        => 'Add or remove ' . $lc_plural,
			'choose_from_most_used'      => 'Choose from the most used',
			'popular_items'              => 'Popular ' . $uc_plural,
			'search_items'               => 'Search ' . $uc_plural,
			'not_found'                  => 'Not Found',
			'no_terms'                   => 'No ' . $lc_plural,
			'items_list'                 => ucfirst( $lc_plural ) . ' list',
			'items_list_navigation'      => ucfirst( $lc_plural ) . ' list navigation',
		);
		return wp_parse_args( $labels, $overrides );
	}

	/**
	 * Simplify generating post type labels by only needing to enter a singular and plural version
	 *
	 * @param  string $singular  The singular version of the post type label
	 * @param  string $plural    The plural version of the post type label
	 * @param  array  $overrides Specific labels to override that might not fit this pattern
	 * @return array             Post type labels
	 */
	public static function generate_post_type_labels( $singular = '', $plural = '', $overrides = array() ) {
		$lc_plural   = strtolower( $plural );
		$uc_plural   = ucwords( $lc_plural );
		$lc_singular = strtolower( $singular );
		$uc_singular = ucwords( $lc_singular );
		$labels      = array(
			'name'                  => $uc_plural,
			'singular_name'         => $uc_singular,
			'menu_name'             => $uc_plural,
			'name_admin_bar'        => $uc_singular,
			'archives'              => $uc_singular . ' Archives',
			'attributes'            => $uc_singular . ' Attributes',
			'parent_item_colon'     => 'Parent ' . $uc_singular . ':',
			'all_items'             => 'All ' . $uc_plural,
			'add_new_item'          => 'Add New ' . $uc_singular,
			'add_new'               => 'Add New',
			'new_item'              => 'New ' . $uc_singular,
			'edit_item'             => 'Edit ' . $uc_singular,
			'update_item'           => 'Update ' . $uc_singular,
			'view_item'             => 'View ' . $uc_singular,
			'view_items'            => 'View ' . $uc_plural,
			'search_items'          => 'Search ' . $uc_singular,
			'not_found'             => 'Not found',
			'not_found_in_trash'    => 'Not found in Trash',
			'featured_image'        => 'Featured Image',
			'set_featured_image'    => 'Set featured image',
			'remove_featured_image' => 'Remove featured image',
			'use_featured_image'    => 'Use as featured image',
			'insert_into_item'      => 'Insert into ' . $lc_singular,
			'uploaded_to_this_item' => 'Uploaded to this ' . $lc_singular,
			'items_list'            => ucfirst( $lc_plural ) . ' list',
			'items_list_navigation' => ucfirst( $lc_plural ) . ' list navigation',
			'filter_items_list'     => 'Filter ' . $lc_plural . ' list',
		);
		return wp_parse_args( $labels, $overrides );
	}

	/**
	 * Alternative version of `media_sideload_image()` that returns the ID
	 * of the media attachment instead of an HTML string
	 *
	 * @param  string   $file      URL of the image to download
	 * @param  integer  $post_id   The post ID the media is to be attached to
	 * @param  string   $desc      Description of the image
	 * @param  array    $post_data $_POST data to fake the request with
	 * @return integer             Post ID of the inserted attachment
	 */
	public static function media_sideload_image_return_id( $file = '', $post_id, $desc = null, $post_data = array() ) {
		if ( ! empty( $file ) ) {

			$file_array = array();
			if ( ! isset( $post_data['file_name'] ) ) {
				// Set variables for storage, fix file filename for query strings.
				preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
				$file_array['name'] = basename( $matches[0] );
			} else {
				$file_array['name'] = $post_data['file_name'];
			}

			// Download file to temp location.
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// Do the validation and storage stuff.
			$id = media_handle_sideload( $file_array, $post_id, $desc, $post_data );

			// If error storing permanently, unlink.
			if ( is_wp_error( $id ) ) {
				unlink( $file_array['tmp_name'] );
			}

			return $id;
		}
	}
}
Daddio_Helpers::get_instance();
