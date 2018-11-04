<?php
class Daddio_Archive {

	public $is_date_query = false;

	/**
	 * Get an instance of this class
	 */
	public static function get_instance() {
		static $instance = null;
		if ( null === $instance ) {
			$instance = new static();
			$instance->setup_hooks();
		}
		return $instance;
	}

	public function setup_hooks() {
		add_action( 'daddio_before_content', array( $this, 'daddio_before_content' ) );
		add_action( 'parse_query', array( $this, 'action_parse_query' ) );

		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
		add_filter( 'template_include', array( $this, 'filter_template_include' ) );
	}

	public function daddio_before_content( $post ) {
		global $wp_query;
		$obj = get_queried_object();
		$heading = '';
		$subheading = array();
		$found = '';
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

		$year = get_query_var( 'year' );
		$month = get_query_var( 'monthnum' );
		$day = get_query_var( 'day' );
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
			$age = preg_replace( '/(\d+)/', ' $1 ', $age );
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
			$suffix = Daddio_Helpers::ordinal_suffix( $page, false );
			$subheading[] = $page . '<sup>' . $suffix . '</sup> page';
		}

		$archive_header = '';
		if ( $heading ) {
			$heading = wptexturize( $heading );
			$archive_header .= '<h1 class="heading">' . $heading . '</h1>';

			if ( ! empty( $subheading ) ) {
				$separator = ' <span class="separator" aria-hidden="true">&bull;</span> ';
				$archive_header .= '<p class="subheading">' . implode( $separator, $subheading ) . '</p>';
			}
		}

		if ( $archive_header ) {
		?>
			<section class="archive-header"><?php echo $archive_header; ?></section>
		<?php
		}
	}

	public function action_parse_query( $query ) {
		if ( isset( $query->is_date ) ) {
			$this->is_date_query = $query->is_date;
		}
	}

	public function filter_query_vars( $vars = array() ) {
		$vars[] = 'daddio-archives-page';
		return $vars;
	}

	public function filter_rewrite_rules_array( $rules = array() ) {
		global $wp_rewrite;

		$new_rules = array(
			$wp_rewrite->root . 'archives/?' => 'index.php?daddio-archives-page=1',
		);
		return $new_rules + $rules;
	}

	public function filter_template_include( $template = '' ) {
		global $wp_query;
		$template_paths = array();
		if ( '1' == get_query_var( 'daddio-archives-page' ) ) {
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
}
Daddio_Archive::get_instance();
