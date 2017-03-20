<?php
class Daddio_Dates {

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
		add_filter( 'the_time', array( $this, 'filter_the_time' ) );
	}

	public function filter_the_time( $time = '' ) {
		return preg_replace( '/(\d+):(\d+) (am|pm)/i', '<span class="time">$1<span class="colon">:</span>$2 <span class="am-pm">$3</span></span>', $time );
	}

	/**
	 * My own human time diff function from http://www.php.net/manual/en/ref.datetime.php#90989
	 *
	 * @param  integer $levels [description]
	 * @param  [type]  $from   [description]
	 * @param  boolean $to     [description]
	 * @return [type]          [description]
	 */
	public function human_time_diff( $levels = 2, $from, $to = false ) {
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

	public function get_childs_birthday() {
		return strtotime( CHILD_DATE_OF_BIRTH );
	}

	public function get_childs_birthday_diff( $levels = 2 ) {
		return $this->human_time_diff( $levels,  get_childs_birthday(), get_the_time( 'U' ) );
	}

	public function get_childs_current_age( $levels = 2 ) {
		return $this->human_time_diff( $levels,  get_childs_birthday() );
	}

	public function how_old_was_child() {
		if ( get_the_time( 'U' ) < $this->get_childs_birthday() ) {
			return $this->get_childs_birthday_diff() . ' before ' . CHILD_NAME . ' was born.';
		}
		return CHILD_NAME . ' was ' . $this->get_childs_birthday_diff() . ' old.';
	}

	public function get_child_time_format() {
		return get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
	}

	public function get_monthly_archive_links() {
		global $wpdb;
		$month_format = 'n';
		$months = array();
		for ( $m = 1; $m <= 12; $m++ ) {
			$months[ $m ] = date( $month_format, mktime( 0, 0, 0, $m, 1, date( 'Y' ) ) );
		}

		// Get Posts
		$order = 'DESC';
		$where = "WHERE `post_type` IN ( 'instagram', 'post' ) AND `post_status` = 'publish'";
		$query = "SELECT YEAR(`post_date`) AS `year`, MONTH(`post_date`) AS `month`, count(ID) AS count FROM $wpdb->posts $where GROUP BY YEAR(`post_date`), MONTH(`post_date`) ORDER BY `post_date` $order";

		$raw_post_data = $wpdb->get_results( $query );
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
}
Daddio_Dates::get_instance();

/**
 * Helper Functions
 */
function how_old_was_child() {
	$instance = Daddio_Dates::get_instance();
	return $instance->how_old_was_child();
}

function get_childs_current_age() {
	$instance = Daddio_Dates::get_instance();
	return $instance->get_childs_current_age();
}

function get_childs_birthday() {
	$instance = Daddio_Dates::get_instance();
	return $instance->get_childs_birthday();
}

function get_childs_birthday_diff() {
	$instance = Daddio_Dates::get_instance();
	return $instance->get_childs_birthday_diff();
}

function get_child_time_format() {
	$instance = Daddio_Dates::get_instance();
	return $instance->get_child_time_format();
}

function get_monthly_archive_links() {
	$instance = Daddio_Dates::get_instance();
	return $instance->get_monthly_archive_links();
}
