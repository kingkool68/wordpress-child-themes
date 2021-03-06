<?php
class Daddio_Infinite_Scroll {

	private $cache_key   = 'daddio_infinite_scroll_page_details';
	private $cache_group = 'daddio_infinite_scroll_cache_group';

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
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'pre_get_posts', array( $this, 'action_pre_get_posts' ) );
		add_action( 'wp', array( $this, 'action_wp' ) );
	}

	/**
	 * Hook into WordPress via filters
	 */
	public function setup_filters() {
		add_filter( 'post_limits', array( $this, 'filter_post_limits' ) );
		add_filter( 'redirect_canonical', array( $this, 'filter_redirect_canonical' ), 10, 2 );
		add_filter( 'get_pagenum_link', array( $this, 'filter_get_pagenum_link' ) );
	}

	/**
	 * Add the /pages/ rewrite endpoint and set up non-persistant caching groups
	 */
	public function action_init() {
		// Make /pages/ rewrite rules work
		add_rewrite_endpoint( 'pages', EP_ALL );

		// Setup a non-persistant caching group
		wp_cache_add_non_persistent_groups( $this->cache_group );
	}

	/**
	 * Modify the query if pages query var is set
	 *
	 * @param  WP_Query $query The WP_Query being run
	 */
	public function action_pre_get_posts( $query ) {
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->is_archive() && ( $pages = $this->get_pages() ) ) {
			$start = $pages->start;

			if ( -1 == $start ) {
				$query->set( 'nopaging', true );
			} else {
				$query->set( 'paged', $start );
			}
			return;
		}
	}

	/**
	 * Modify the paged value of global variables
	 */
	public function action_wp() {
		global $wp_query, $paged;
		$pages = $this->get_pages();
		if ( ! $pages || ! $pages->end ) {
			return;
		}

		$wp_query->set( 'paged', $pages->end );
		$paged = get_query_var( 'paged' );
	}

	/**
	 * Adjust the limit clause of the main SQL query
	 *
	 * @param  string $limit Limit clause of SQL query
	 * @return string        Modified limit clause
	 */
	public function filter_post_limits( $limit = '' ) {
		global $wp_query;
		if ( is_admin() || ! $wp_query->is_main_query() ) {
			return $limit;
		}

		if ( $pages = $this->get_pages() ) {
			if ( -1 == $pages->start ) {
				return $limit;
			}
			$new_limit = $pages->diff * $pages->posts_per_page + $pages->posts_per_page;
			$limit     = str_replace( ', ' . $pages->posts_per_page, ', ' . $new_limit, $limit );
		}
		return $limit;
	}

	/**
	 * Kill the canonical 'paged' redirect if the pages query var is set.
	 * In other words we don't want /category/publications/?pages=1-10 to redirect to /category/publications/page/10/?pages=1-10 which is what would happen by default
	 *
	 * @param  string $redirect_url  The URL WordPress is planning to redirect to
	 * @param  string $requested_url The requested URL to WordPress
	 * @return string                The modified redirect URL
	 */
	public function filter_redirect_canonical( $redirect_url = '', $requested_url = '' ) {
		if ( get_query_var( 'pages' ) ) {
			$redirect_url = trailingslashit( $requested_url );
		}
		return $redirect_url;
	}

	/**
	 * Strip out pages values from pagenum links
	 *
	 * @TODO: Inspect this method more closely to see what it does
	 *
	 * @param  string $result The pagenum URL
	 * @return string         The modified pagenum URL
	 */
	public function filter_get_pagenum_link( $result = '' ) {
		if ( $pages = $this->get_pages() ) {
			// Strip out anything that matches pages/{any digit or hypen or the word all}/
			$result = preg_replace( '/pages\/([\d\-]+|all)\//i', '', $result );
		}
		return $result;
	}

	/**
	 * Get various details about the paging logic for the current request
	 *
	 * @return object|false Details about the paging variables for the current request
	 */
	private function get_pages() {
		$pages_var = get_query_var( 'pages' );
		if ( ! $pages_var ) {
			return false;
		}

		// Inter request caching
		if ( $output = wp_cache_get( $this->cache_key, $this->cache_group ) ) {
			return $output;
		}

		$start = 1;
		$end   = false;
		$pages = explode( '-', $pages_var );

		if ( count( $pages ) < 2 && isset( $pages[0] ) && ! empty( $pages[0] ) ) {
			if ( strtolower( $pages[0] ) == 'all' ) {
				$start = -1;
				$end   = false;
			} else {
				$end = intval( $pages[0] );
			}
		} else {
			$pages = array_map( 'intval', $pages );
			if ( isset( $pages[0] ) && ! empty( $pages[0] ) ) {
				$start = $pages[0];
			}

			if ( isset( $pages[1] ) && ! empty( $pages[1] ) ) {
				$end = $pages[1];
			}
		}

		$output = (object) array(
			'start'          => $start,
			'end'            => $end,
			'diff'           => abs( $end - $start ),
			'posts_per_page' => intval( get_option( 'posts_per_page' ) ),
		);
		wp_cache_set( $this->cache_key, $output, $this->cache_group );
		return $output;
	}

}
Daddio_Infinite_Scroll::get_instance();
