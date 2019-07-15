<?php
class Daddio_Archive {

	private $is_date_query = false;

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
	 * Hook into WordPress via actions
	 */
	public function setup_actions() {
		add_action( 'parse_query', array( $this, 'action_parse_query' ) );
	}

	/**
	 * Hook into WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
		add_filter( 'template_include', array( $this, 'filter_template_include' ) );
	}

	/**
	 * Set whether the request is a date query or not
	 *
	 * @param  WP_Query $query The current wp_query object
	 */
	public function action_parse_query( $query ) {
		if ( isset( $query->is_date ) ) {
			$this->is_date_query = $query->is_date;
		}
	}

	/**
	 * Make WordPress aware of our custom query vars
	 *
	 * @param  array  $vars Query vars previously registered
	 * @return array        Modified query vars
	 */
	public function filter_query_vars( $vars = array() ) {
		$vars[] = 'daddio-archives-page';
		return $vars;
	}

	/**
	 * Add archive specific rewrite rules
	 *
	 * @param  array  $rules Current rewrite rules
	 * @return array         Modified rewrite rules
	 */
	public function filter_rewrite_rules_array( $rules = array() ) {
		global $wp_rewrite;

		$new_rules = array(
			$wp_rewrite->root . 'archives/?' => 'index.php?daddio-archives-page=1',
		);
		return $new_rules + $rules;
	}

	/**
	 * Use different templates depending on the query
	 *
	 * @param  string $template Location of the current template WordPress will use
	 * @return string           Modified template location
	 */
	public function filter_template_include( $template = '' ) {
		global $wp_query;
		$template_paths = array();
		if ( '1' === get_query_var( 'daddio-archives-page' ) ) {
			$template_paths = array(
				'archives.php',
			);

		}

		$found_posts = intval( $wp_query->found_posts );

		// Age 404 templates
		if ( ! $found_posts && get_query_var( 'age' ) ) {
			$template_paths = array(
				'404-age-archive.php',
				'404.php',
			);
		}

		// Date 404 templates
		if ( ! $found_posts && $this->is_date_query ) {
			$template_paths = array(
				'404-date-archive.php',
				'404.php',
			);
		}

		if ( $new_template = locate_template( $template_paths ) ) {
			return $new_template;
		}
		return $template;
	}

	/**
	 * Get a header to be displayed before the list of archive items
	 */
	public static function get_archive_header() {
		global $wp_query;
		$obj        = get_queried_object();
		$heading    = '';
		$subheading = array();
		$found      = '';

		if ( is_tag() && $obj && isset( $obj->name ) ) {
			$heading = ucwords( $obj->name );
		}

		if ( is_category() && $obj && isset( $obj->name ) ) {
			$heading = ucwords( $obj->name );
			switch ( $heading ) {
				case 'Video':
					$heading = 'Videos';
					break;

				case 'Gallery':
					$heading = 'Galleries';
					break;
			}
		}

		$year  = get_query_var( 'year' );
		$month = get_query_var( 'monthnum' );
		$day   = get_query_var( 'day' );
		if ( is_year() ) {
			$heading = $year;
		}

		if ( is_month() ) {
			$heading = date( 'F Y', strtotime( $year . '-' . $month . '-01' ) );
		}

		if ( is_day() ) {
			$heading = date( 'F d, Y', strtotime( $year . '-' . $month . '-' . $day ) );
		}

		if ( $age = get_query_var( 'age' ) ) {
			$age     = preg_replace( '/(\d+)/', ' $1 ', $age );
			$heading = ucwords( $age ) . ' Old';
		}

		if ( $found = $wp_query->found_posts ) {
			$found = number_format( $found );
			$label = 'item';
			if ( $found > 1 ) {
				$label .= 's';
			}
			$subheading[] = $found . ' ' . $label;
		}

		$page = intval( get_query_var( 'paged' ) );
		if ( $page > 1 ) {
			$suffix       = Daddio_Helpers::ordinal_suffix( $page, false );
			$subheading[] = $page . '<sup>' . $suffix . '</sup> page';
		}

		if ( ! empty( $subheading ) ) {
			$separator  = ' <span class="separator" aria-hidden="true">&bull;</span> ';
			$subheading = implode( $separator, $subheading );
		}

		if ( empty( $heading ) ) {
			return;
		}

		$context = array(
			'heading'    => $heading,
			'subheading' => $subheading,
		);
		return Sprig::render( 'archive-header.twig', $context );
	}
}
Daddio_Archive::get_instance();
