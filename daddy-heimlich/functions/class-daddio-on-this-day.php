<?php
class Daddio_On_This_Day {

	private $pagename = 'on-this-day';

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
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ), 9 );
		add_action( 'daddio_before_content', array( $this, 'action_daddio_before_content' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'action_wp_enqueue_scripts' ) );
	}

	/**
	 * Hook in to WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'query_vars', array( $this, 'filter_query_vars' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'filter_rewrite_rules_array' ) );
		add_filter( 'template_include', array( $this, 'filter_template_include' ) );
	}

	/**
	 * Make WordPress aware of our custom query vars
	 *
	 * @param  array $vars Query vars to modify
	 * @return array       Modified query vars
	 */
	public function filter_query_vars( $vars = array() ) {
		$vars[] = 'zah-on-this-month';
		$vars[] = 'zah-on-this-day';

		return $vars;
	}

	/**
	 * Add custom rewrite rules so the On This Day feature works
	 *
	 * @param  array $rules Rewrite rules
	 * @return array        Modified rewrite rules
	 */
	public function filter_rewrite_rules_array( $rules = array() ) {
		global $wp_rewrite;

		$root = $wp_rewrite->root . $this->pagename;
		$new_rules = array(
			$root . '/([0-9]{2})/([0-9]{2})/?' => 'index.php?pagename=' . $this->pagename . '&zah-on-this-month=$matches[1]&zah-on-this-day=$matches[2]',
		);

		return $new_rules + $rules;
	}

	/**
	 * Handle selecting the proper template for a request
	 */
	public function action_template_redirect() {
		$pagename = get_query_var( 'pagename' );
		$month    = get_query_var( 'zah-on-this-month' );
		$day      = get_query_var( 'zah-on-this-day' );

		if ( $day && $month ) {
			$days_in_month = 31;
			switch (  $month ) {
				// 30 days
				case '04':
				case '06':
				case '09':
				case '11':
					$days_in_month = 30;
				break;

				// 29 days because of leap years
				case '02':
					$days_in_month = 29;
				break;
			}
			if ( intval( $day ) > $days_in_month ) {
				$day         = $days_in_month;
				$redirect_to = get_site_url() . '/' . $this->pagename . '/' . $month . '/' . $day . '/';
				wp_redirect( $redirect_to );
				die();
			}
		}

		// Automatically redirect ugly query strings to pretty permalinks
		$query_string = explode( '?', $_SERVER['REQUEST_URI'] );
		if ( isset( $query_string[1] ) ) {
			$query_string = wp_parse_args( $query_string[1] );
			if ( isset( $query_string['zah-on-this-month'] ) && isset( $query_string['zah-on-this-day'] ) ) {
				$redirect_to = get_site_url() . '/' . $this->pagename . '/' . $month . '/' . $day . '/';
				wp_redirect( $redirect_to );
				die();
			}
		}

		// No pagename so bail.
		if ( ! $pagename || $pagename != $this->pagename ) {
			return;
		}

		// $month and $day aren't set so we're assuming we landed on /on-this-day/ and we need to redirect to today
		if ( ! $month && ! $day ) {
			$this->redirect_to_current_date();
		}
	}

	/**
	 * Handle 404 template for On this Day errors
	 *
	 * @param  string $template The current template to be used as chosen by WordPress
	 * @return string           Maybe modified template
	 */
	public function filter_template_include( $template = '' ) {
		global $wp_query;
		$month = get_query_var( 'zah-on-this-month' );
		$day = get_query_var( 'zah-on-this-day' );

		if ( $month && $day && is_404() ) {
			$template_paths = array(
				'404-' . $this->pagename . '.php',
				'404.php',
			);
			if ( $new_template = locate_template( $template_paths ) ) {
				return $new_template;
			}
		}

		return $template;
	}

	/**
	 * Modify the query for On This Day requests
	 *
	 * @param  WP_Query $query The query
	 */
	public function action_pre_get_posts( $query ) {
		if ( ! $query->is_main_query() || is_admin() ) {
			return;
		}

		$pagename = get_query_var( 'pagename' );
		if ( ! $pagename ) {
			$pagename = get_query_var( 'name' );
		}
		if ( ! $pagename || $pagename != $this->pagename ) {
			return;
		}

		$date_query = array();
		if ( $month = get_query_var( 'zah-on-this-month' ) ) {
			$month  = ltrim( $month, '0' );
			$month  = intval( $month );
			$date_query['month'] = $month;
		}
		if ( $day = get_query_var( 'zah-on-this-day' ) ) {
			$day  = ltrim( $day, '0' );
			$day  = intval( $day );
			$date_query['day'] = $day;
		}

		if ( empty( $date_query ) ) {
			$this->redirect_to_current_date();
		}

		$query->set( 'date_query', $date_query );
		$query->set( 'name', '' );
		$query->set( 'pagename', '' );
		$query->is_page    = false;
		$query->is_404     = false;
		$query->is_date    = true;
		$query->is_archive = true;
	}

	/**
	 * Conditional for determining if the current request is a On This Day request
	 * @return boolean Whether the request is an On This Day request
	 */
	public function is_on_this_day() {
		$month = get_query_var( 'zah-on-this-month' );
		$day = get_query_var( 'zah-on-this-day' );
		return ( $month && $day );
	}

	/**
	 * Handle redirecting to the On This Day page for the current date
	 */
	public function redirect_to_current_date() {
		$redirect_to = get_site_url() . '/' . $this->pagename . '/' . current_time( 'm' ) . '/' . current_time( 'd' ) . '/';
		wp_safe_redirect( $redirect_to );
		die();
	}

	/**
	 * Get the markup and enqueue the Javascript to make the switch date from work
	 *
	 * @return string HTML of the switch date form
	 */
	public static function get_switch_date_form() {
		wp_enqueue_script( 'on-this-day' );

		$months = array(
			'01' => 'January',
			'02' => 'February',
			'03' => 'March',
			'04' => 'April',
			'05' => 'May',
			'06' => 'June',
			'07' => 'July',
			'08' => 'August',
			'09' => 'September',
			'10' => 'October',
			'11' => 'November',
			'12' => 'December',
		);

		$context = array(
			'site_url'  => get_site_url(),
			'months'    => $months,
			'days'      => range(1, 31),
			'the_month' => get_query_var( 'zah-on-this-month' ),
			'the_day'   => get_query_var( 'zah-on-this-day' ),
		);
		return Sprig::render( 'on-this-day-switch-date-form.twig', $context );
	}

	/**
	 * Load On This Day form at the top of the page
	 */
	public function action_daddio_before_content() {
		if ( ! $this->is_on_this_day() ) {
			return;
		}
		echo self::get_switch_date_form();
	}

	/**
	 * Rgister the On This Day JavaScript
	 */
	public function action_wp_enqueue_scripts() {
		wp_register_script( 'on-this-day', get_template_directory_uri() . '/js/on-this-day.js', array( 'jquery' ), null, true );
	}
}
Daddio_On_This_Day::get_instance();
