<?php
class Daddio_Dates {

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
		add_action( 'init', array( $this, 'init_add_rewrite_tags' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );
	}

	/**
	 * Hook into WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'the_time', array( $this, 'filter_the_time' ) );
		add_filter( 'get_the_time', array( $this, 'filter_the_time' ) );
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
	}

	/**
	 * Setup rewrite tags for age
	 */
	public function init_add_rewrite_tags() {
		global $wp_rewrite;

		$rewrite_tags = array(
			array( '%age%', 'age/([^/]+)', 'age=' ),
		);
		foreach ( $rewrite_tags as $tag ) {
			 $wp_rewrite->add_rewrite_tag( $tag[0], $tag[1], $tag[2] );
		}
	}

	/**
	 * Modify the query if it is an age-based query
	 *
	 * @param  WP_Query $query The WP_Query being run
	 */
	public function action_pre_get_posts( $query ) {
		$age = get_query_var( 'age' );
		if ( ! $age || ! $query->is_main_query() ) {
			return;
		}

		$unit = self::get_smallest_time_unit( $age );
		$relative_date = strtotime( $age );
		$offset = $relative_date - date( 'U' );
		$ending_offset = 0;
		switch ( $unit ) {
			case 'years':
			case 'year':
				$ending_offset = YEAR_IN_SECONDS;
			 	break;

			case 'months':
			case 'month':
				$ending_offset = MONTH_IN_SECONDS;
				break;

			case 'weeks':
			case 'week':
				$ending_offset = WEEK_IN_SECONDS;
				break;

			case 'days':
			case 'day':
				$ending_offset = DAY_IN_SECONDS;
				break;

			case 'hours':
			case 'hour':
				$ending_offset = HOUR_IN_SECONDS;
				break;

			case 'minutes':
			case 'minute':
				$ending_offset = MINUTE_IN_SECONDS;
				break;
		}
		$start = self::get_childs_birthday() + $offset; // In seconds
		$end = $start + $ending_offset;
		$date_query = array(
			array(
				'before' => date( 'Y-m-d g:ia', $end ),
				'after' => date( 'Y-m-d g:ia', $start ),
				'inclusive' => false,
			),
		);
		$query->set( 'date_query', $date_query );
	}

	/**
	 * Wrap different parts of the time for styling purposes
	 *
	 * @param  string $time The time to be displayed
	 * @return string       The time with additional markup added
	 */
	public function filter_the_time( $time = '' ) {
		return preg_replace( '/(\d+):(\d+) (am|pm)/i', '<span class="time">$1<span class="colon">:</span>$2 <span class="am-pm">$3</span></span>', $time );
	}

	/**
	 * Make WordPress aware of our custom query vars
	 *
	 * @param  array  $vars Query vars previously registered
	 * @return array        Modified query vars
	 */
	public function filter_query_vars( $vars = array() ) {
		$vars[] = 'age';
		return $vars;
	}

	/**
	 * Add rewrite rules for age based URLs
	 *
	 * @param  array  $rules Previously registered rewrite rules
	 * @return array         Modified rewrite rules
	 */
	public function filter_rewrite_rules_array( $rules = array() ) {
		global $wp_rewrite;
		$root = $wp_rewrite->root;
		$permalink_structures = array(
			$root . '/%age%/',
		);

		foreach ( $permalink_structures as $struc ) {
			$rules = $wp_rewrite->generate_rewrite_rules( $struc, $ep_mask = EP_ALL_ARCHIVES ) + $rules;
		}

		return $rules;
	}

	/**
	 * My own human time diff function from http://www.php.net/manual/en/ref.datetime.php#90989
	 *
	 * @param  integer       $levels Precision of time diff
	 * @param  integer       $from   Time to start at in seconds
	 * @param  integer|false $to     Time to end at in seconds
	 * @return string                Human-friendly time diff
	 */
	public static function human_time_diff( $levels = 2, $from, $to = false ) {
		if ( ! $to ) {
			$to = current_time( 'U' );
		}
		$blocks = array(
			array( 'name' => 'year',   'amount' => 60 * 60 * 24 * 365 ),
			array( 'name' => 'month',  'amount' => 60 * 60 * 24 * 31 ),
			array( 'name' => 'week',   'amount' => 60 * 60 * 24 * 7 ),
			array( 'name' => 'day',    'amount' => 60 * 60 * 24 ),
			array( 'name' => 'hour',   'amount' => 60 * 60 ),
			array( 'name' => 'minute', 'amount' => 60 ),
			array( 'name' => 'second', 'amount' => 1 ),
		);

		$diff = abs( $from - $to );

		$current_level = 1;
		$result = array();
		foreach ( $blocks as $block ) {
			if ( $current_level > $levels ) { break; }
			if ( $diff / $block['amount'] >= 1 ) {
				$amount = floor( $diff / $block['amount'] );
				$plural = '';
				if ( $amount > 1 ) {
					$plural = 's';
				}
				$result[] = $amount . ' ' . $block['name'] . $plural;
				$diff -= $amount * $block['amount'];
				$current_level++;
			}
		}

		return implode( ' ', $result );
	}

	/**
	 * Get the datetime of the child's date of birth in seconds
	 *
	 * @return integer Datetime of child's birth since epoch
	 */
	public static function get_childs_birthday() {
		return strtotime( CHILD_DATE_OF_BIRTH );
	}

	/**
	 * Get huma-friendly time difference between child's birthdate and the datetime of the current post
	 *
	 * @param  integer $levels      Level of precision of the time difference
	 * @param  string  $time_offset Ending time to use for calculations
	 * @return string               Human friendly time differnece between child's birthdate and post's datetime
	 */
	public static function get_childs_birthday_diff( $levels = 2, $time_offset = '' ) {
		if ( ! $time_offset ) {
			$time_offset = get_the_time( 'U' );
		}
		return self::human_time_diff( $levels,  self::get_childs_birthday(), $time_offset );
	}

	/**
	 * Get the child's current age as a human-friendly time difference
	 *
	 * @param  integer $levels Level of precision of the time differecne
	 * @return string          Human friendly time differnece of child's age and now
	 */
	public static function get_childs_current_age( $levels = 2 ) {
		return self::human_time_diff( $levels,  self::get_childs_birthday() );
	}

	/**
	 * Generate a statement about how old the child is
	 *
	 * @return string How old was the child compared to the given datetime of the current post
	 */
	public static function how_old_was_child() {
		if ( get_the_time( 'U' ) < self::get_childs_birthday() ) {
			return self::get_childs_birthday_diff() . ' before ' . CHILD_NAME . ' was born.';
		}
		return CHILD_NAME . ' was ' . self::get_childs_birthday_diff() . ' old.';
	}

	/**
	 * Get the preferred datetime format
	 *
	 * @return string Datetime format
	 */
	public static function get_child_time_format() {
		return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	/**
	 * Get the number of posts grouped by month
	 *
	 * @return object Post counts broken down by year and month
	 */
	public static function get_monthly_post_counts() {
		global $wpdb;
		$where = "WHERE `post_type` IN ( 'instagram', 'post' ) AND `post_status` = 'publish'";
		$query = "SELECT YEAR(`post_date`) AS `year`, MONTH(`post_date`) AS `month`, count(ID) AS count FROM $wpdb->posts $where GROUP BY YEAR(`post_date`), MONTH(`post_date`) ORDER BY `post_date` DESC";
		return $wpdb->get_results( $query );
	}

	/**
	 * Get a breakdown of post counts by year and month between the child's birthdate and the end of the current year
	 *
	 * @return array Post counts by year and month
	 */
	public static function get_monthly_archive_links() {
		$month_format = 'n';
		$months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$months[ $m ] = date( $month_format, mktime( 0, 0, 0, $m, 1, date( 'Y' ) ) );
		}

		$raw_post_data = self::get_monthly_post_counts();
		$post_data = array();
		foreach ( $raw_post_data as $raw ) {
			$post_data[ $raw->year ][ $raw->month ] = $raw->count;
		}

		$start = new DateTime( CHILD_DATE_OF_BIRTH );
		$end = new DateTime( '12/31/' . date( 'Y' ) );
		$interval = DateInterval::createFromDateString( '1 year' );
		$period = new DatePeriod( $start, $interval, $end );
		$output = array();
		foreach ( $period as $dt ) {
			$year = $dt->format( 'Y' );
			$output[ $year ] = array();
			foreach ( $months as $month_num => $month ) {
				$output[ $year ][ $month ] = '';
				if ( isset( $post_data[ $year ][ $month_num ] ) ) {
					$output[ $year ][ $month ] = (object) array(
						'link' => get_month_link( $year, $month_num ),
						'count' => $post_data[ $year ][ $month_num ],
					);
				}
			}
		}
		return array_reverse( $output, $preserve_keys = true );
	}

	/**
	 * Get a collection of date for age archive pages
	 *
	 * @return array Age archive data
	 */
	public static function get_age_archive_data() {
		$month_of_birth = date( 'm', self::get_childs_birthday() );
		$month_after_birth = intval( $month_of_birth ) + 1;
		if ( $month_after_birth > 12 ) {
			$month_after_birth = 1;
		}

		$day_of_birth = date( 'd', self::get_childs_birthday() );
		$day_before_birth = intval( $day_of_birth ) - 1;
		$permalink = get_site_url() . '/age/';

		$raw_post_data = self::get_monthly_post_counts();
		$output = array();
		foreach ( $raw_post_data as $raw ) {
			$time_offset = date( 'U', strtotime( $raw->year . '-' . $raw->month . '-' . $day_before_birth ) );
			if ( $time_offset < self::get_childs_birthday() + MONTH_IN_SECONDS ) {
				continue;
			}
			$levels = 2;
			$has_month = true;
			if ( $month_after_birth == $raw->month ) {
				$levels = 1;
				$has_month = false;
			}

			if ( $time_offset < self::get_childs_birthday() + YEAR_IN_SECONDS ) {
				$levels = 1;
			}
			$diff = self::get_childs_birthday_diff( $levels, $time_offset );
			$diff_slug = str_replace( ' ', '', $diff );
			$output[] = array(
				'timestamp' => $diff,
				'permalink' => $permalink . $diff_slug . '/',
				'has_month' => $has_month,
				'count'     => $raw->count,
				'year'      => $raw->year,
				'month'     => $raw->month - 1,
			);
		}
		return $output;
	}

	/**
	 * Given a string timestamp get the smallest unit of time
	 *
	 * @param  string $timestamp A string of spelled out duration of time (ex. 1 year 3 months)
	 * @return stirng            A string of the last unit of time
	 */
	public static function get_smallest_time_unit( $timestamp = '' ) {
		if ( ! $timestamp ) {
			return false;
		}

		$timestamp       = strtolower( $timestamp );
		// Strip out everything except numbers and letters
		$timestamp       = preg_replace( '/[^\w]+/', '', $timestamp );
		$timestamp_words = preg_replace( '/(\d+)/', '-', $timestamp );
		$timestamp_words = explode( '-', $timestamp_words );
		$last_word       = $timestamp_words[ count( $timestamp_words ) - 1 ];
		return $last_word;
	}
}
Daddio_Dates::get_instance();
