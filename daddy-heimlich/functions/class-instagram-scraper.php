<?php
use InstagramScraper\Instagram;
use GuzzleHttp\Client;
/**
 * For scraping and normalizing Instagram data from a page's HTML
 */
class Instagram_Scraper {

	/**
	 * The type of page that is being scraped
	 *
	 * @var string
	 */
	public $page_type = '';

	/**
	 * Extra meta data about the given page
	 *
	 * @var array
	 */
	public $page_info = array();

	/**
	 * The URL of the current page being scraped
	 *
	 * @var string
	 */
	public $page_url = '';

	/**
	 * The URL to fetch the next page of results
	 *
	 * @var string
	 */
	public $recent_next_page_url = '';

	/**
	 * The items that were scraped
	 *
	 * @var array
	 */
	public $items = array();

	private $scrapers = array();

	/**
	 * Determine what type of page should be processed
	 *
	 * @param string $url The Instagram URL to scrape
	 */
	public function __construct( $url = '', $sessionid = '' ) {
		// Figure out which way to parse the URL for data
		$url_parts = parse_url( $url );
		if ( empty( $url_parts['host'] ) || ! str_contains( $url_parts['host'], 'instagram' ) ) {
			wp_die( esc_url( $url ) . ' is not an Instagram URL!' );
		}
		$path_parts = explode( '/', trim( $url_parts['path'], '/' ) );

		// Location: https://www.instagram.com/explore/locations/503859773449516/facebook-headquarters/
		if ( ! empty( $url_parts['path'] ) && str_starts_with( $url_parts['path'], '/explore/locations/' ) ) {
			$location_id = $path_parts[2];
			$this->items = $this->parse_location_url( $location_id );
		}

		if ( ! empty( $url_parts['path'] ) && str_starts_with( $url_parts['path'], '/explore/tags/' ) ) {
			// Tag page
		}

		// Post: https://www.instagram.com/p/BsOGulcndj-/
		if ( ! empty( $url_parts['path'] ) && str_starts_with( $url_parts['path'], '/p/' ) ) {
			$this->parse_post_url( $url );
			// $this->items = $this->parse_post_url( $url );
		}

		// Can we parse a URL from a tagged user?

		if ( ! empty( $url_parts['path'] ) && (
			! str_starts_with( $url_parts['path'], '/explore/' ) &&
			! str_starts_with( $url_parts['path'], '/p/' )
		) ) {
			// Profile page
		}

		/*
		if ( ! empty( $raw_json->entry_data->LocationsPage ) ) {
			$this->items = $this->parse_location_page_json( $raw_json );
		}

		if ( ! empty( $raw_json->entry_data->TagPage ) ) {
			$this->items = $this->parse_tag_page_json( $raw_json );
		}

		// Tagged user / user post?
		if ( ! empty( $raw_json->entry_data->ProfilePage ) ) {
			$this->items = $this->parse_tagged_user_page_json( $raw_json );
		}

		if ( ! empty( $raw_json->items[0] ) ) {
			$this->items = $this->parse_single_post_json( $raw_json );
		}
		*/
	}

	public function get_scraper( $sessionid = '' ) {
		if ( empty( $sessionid ) ) {
			$sessionid = get_option( 'instagram-sessionid' );
		}
		if ( empty( $sessionid ) ) {
			wp_die( 'No Instagram login sessionid provided to scraper' );
		}
		if ( ! empty( $this->scrapers[ $sessionid ] ) ) {
			return $this->scrapers[ $sessionid ];
		}
		$instagram = Instagram::withCredentials( new Client(), '', '', null );
		$instagram->loginWithSessionId( $sessionid );
		$this->scrapers[ $sessionid ] = $instagram;
		return $instagram;
	}

	/**
	 * Process data from a location id
	 *
	 * @param string $location_id The Instagram ID of the location
	 */
	public function parse_location_url( $location_id = '' ) {
		if ( empty( $location_id ) ) {
			wp_die( 'Bad Location ID: ' . $location_id );
		}
		$this->page_type = 'location';

		$scraper  = $this->get_scraper();
		$location = $scraper->getLocationById( $location_id );

		$this->page_info = array(
			'location_id'        => $location_id,
			'facebook_places_id' => $location->getFacebookPlacesId(),
			'name'               => $location->getName(),
			// 'phone'          => $location->getId(),
			// 'website'        => $location->getId(),
			// 'category'       => $location->getId(),
			'latitude'           => $location->getLat(),
			'longitude'          => $location->getLng(),
			'street_address'     => $location->getAddress(),
			'city'               => $location->getCity(),
			// 'region'         => $location->getId(),
			// 'postcode'       => $location->getId(),
		);

		$output = array();
		/*
		 Broken at the moment
		$top_media    = $scraper->getCurrentTopMediasByLocationId( $location_id );
		$latest_media = $scraper->getMediasByLocationId( $location_id );
		$medias       = array();
		$medias       = array_merge( $top_media, $medias );
		$medias       = array_merge( $latest_media, $medias );
		foreach( $medias as $media ) {
			$output[] = $this->normalize_media( $media );
		}
		*/
		return $output;
	}

	/**
	 * Process data from a tag page
	 *
	 * @param object $raw_json The raw JSON data found on the page to be scraped
	 */
	public function parse_tag_page_json( $raw_json ) {
		if ( empty( $raw_json->entry_data->TagPage[0]->data ) ) {
			wp_die( 'Bad Tag Page Data' );
		}
		$this->page_type = 'tag-page';

		$root = $raw_json->entry_data->TagPage[0]->data;

		$page_info = array();
			// Map our location_info object properties to our page_info properties
			$mapping = array(
				'id'          => 'id',
				'name'        => 'name',
				'media_count' => 'media_count',
			);
			foreach ( $mapping as $ig_key => $key ) {
				$page_info[ $key ] = '';
				if ( ! empty( $root->{$ig_key} ) ) {
					$page_info[ $key ] = $root->{$ig_key};
				}
			}
			$this->page_info = $page_info;
			if ( ! empty( $root->name ) ) {
				$this->page_url = 'https://www.instagram.com/explore/tags/' . $root->name . '/';
			}

			if ( ! empty( $root->recent->next_max_id ) ) {
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
				$max_id                     = base64_decode( $root->recent->next_max_id );
				$this->recent_next_page_url = add_query_arg(
					array(
						'__a'    => 1,
						'max_id' => $max_id,
					),
					$this->page_url
				);
			}

			$sections = array();
			$output   = array();
			$sections = array_merge( $root->top->sections, $sections );
			$sections = array_merge( $root->recent->sections, $sections );
			foreach ( $sections as $section ) {
				if ( ! empty( $section->layout_content->medias ) ) {
					$medias = $section->layout_content->medias;
					foreach ( $medias as $item ) {
						$node     = $item->media;
						$output[] = $this->normalize_node( $node );
					}
				}
			}
			return $output;
	}

	/**
	 * Process data from a user page
	 *
	 * @param object $raw_json The raw JSON data found on the page to be scraped
	 */
	public function parse_tagged_user_page_json( $raw_json ) {
		if ( empty( $raw_json->entry_data->ProfilePage[0] ) ) {
			wp_die( 'Bad Tagged User Page Data' );
		}
		$this->page_type = 'user-page';

		$root                 = $raw_json->entry_data->ProfilePage[0]->graphql->user;
		$media_posted_by_user = $root->edge_owner_to_timeline_media->edges;
		$combined_posts       = $root->edge_felix_combined_post_uploads->edges;
		$sections             = array();
		$output               = array();
		$sections             = array_merge( $media_posted_by_user, $sections );
		$sections             = array_merge( $combined_posts, $sections );
		foreach ( $sections as $section ) {
			$output[] = $this->normalize_node( $section->node );
		}
		// Dedupe
		$unique_ids = array();
		foreach ( $output as $key => $item ) {
			$the_id = $item['id'];
			if ( in_array( $the_id, $unique_ids, true ) ) {
				unset( $output[ $key ] );
			} else {
				$unique_ids[] = $the_id;
			}
		}
		return $output;
	}

	/**
	 * Process data from a post
	 *
	 * @param string $url The Instagram URL of a post to be parsed
	 */
	public function parse_post_url( $url = '' ) {
		if ( empty( $url ) ) {
			wp_die( 'Bad Instagram URL: ' . $url );
		}
		$this->page_type = 'post';
		$scraper         = $this->get_scraper();

		$output = array();
		$media  = $scraper->getMediaByUrl( $url );
		$medias = array( $media );
		foreach ( $medias as $media ) {
			$output = $this->normalize_media( $media );
		}
		return $output;
	}

	public function normalize_media( $media ) {
		$owner = $media->getOwner();

		$output = array(
			'_normalized'      => true,
			'id'               => $media->getId(),
			'code'             => $media->getShortCode(),
			'instagram_url'    => $media->getLink(),
			'caption'          => $media->getCaption(),
			'timestamp'        => $media->getCreatedTime(), // In GMT time
			'owner_id'         => $media->getOwnerId(),
			'owner_username'   => $owner->getUsername(),
			'owner_full_name'  => $owner->getFullName(),

			'location_id'      => $media->getLocationId(),
			'location_name'    => $media->getLocationName(),
			'location_address' => '',
			'location_city'    => '',
			'latitude'         => '',
			'longitude'        => '',
			'media'            => array(),
		);
		$address = $media->getLocationAddress();
		if ( ! empty( $address ) ) {

		}
		return $output;
	}

	/**
	 * Normalize data from a single media node
	 *
	 * @param object $node The node data to process
	 */
	public function normalize_node( $node ) {
		$output = array(
			'_normalized'      => true,
			'id'               => '',
			'code'             => '',
			'instagram_url'    => '',
			'caption'          => '',
			'timestamp'        => '', // In GMT time
			'owner_id'         => '',
			'owner_username'   => '',
			'owner_full_name'  => '',

			'location_id'      => '',
			'location_name'    => '',
			'location_address' => '',
			'location_city'    => '',
			'latitude'         => '',
			'longitude'        => '',
			'media'            => array(),
		);

		if ( ! empty( $node->id ) ) {
			$output['id'] = $node->id;
		}

		if ( ! empty( $node->code ) ) {
			$output['code']          = $node->code;
			$output['instagram_url'] = 'https://instagram.com/p/' . $output['code'] . '/';
		}

		if ( ! empty( $node->caption->text ) ) {
			$output['caption'] = $node->caption->text;
		}

		if ( ! empty( $node->caption->created_at ) ) {
			$output['timestamp'] = absint( $node->caption->created_at );
		}

		if ( ! empty( $node->caption->user_id ) ) {
			$output['owner_id'] = absint( $node->caption->user_id );
		}

		if ( ! empty( $node->user->username ) ) {
			$output['owner_username'] = $node->user->username;
		}

		if ( ! empty( $node->user->full_name ) ) {
			$output['owner_full_name'] = $node->user->full_name;
		}

		if ( ! empty( $node->lat ) ) {
			$output['latitude'] = $node->lat;
		}

		if ( ! empty( $node->lng ) ) {
			$output['longitude'] = $node->lng;
		}

		if ( ! empty( $node->location->pk ) ) {
			$output['location_id'] = $node->location->pk;
		}

		if ( ! empty( $node->location->name ) ) {
			$output['location_name'] = $node->location->name;
		}

		if ( ! empty( $node->location->address ) ) {
			$output['location_address'] = $node->location->address;
		}

		if ( ! empty( $node->location->city ) ) {
			$output['location_city'] = $node->location->city;
		}

		$items = array();
		if ( ! empty( $node->image_versions2->candidates ) ) {
			$items = array( $node );
		}
		if ( ! empty( $node->carousel_media ) ) {
			$items = $node->carousel_media;
		}

		foreach ( $items as $item ) {
			$media_item = array(
				'id'        => '',
				'src'       => '',
				'video_src' => '',
			);

			if ( ! empty( $item->id ) ) {
				$media_item['id'] = $item->id;
			}
			if ( ! empty( $item->image_versions2->candidates ) ) {
				$media_item['src'] = $item->image_versions2->candidates[0]->url;
			}
			if ( ! empty( $item->video_versions[0]->url ) ) {
				$media_item['video_src'] = $item->video_versions[0]->url;
			}
			$output['media'][] = $media_item;
		}
		if ( ! empty( $node->__typename ) ) {
			$output = static::normalize_old_node( $node, $output );
		}
		return $output;
	}

	/**
	 * Normalize data from a single media node in an old format
	 * Currently this is only for user pages
	 *
	 * @param object $node The node data to process
	 * @param array $output The current output format to return the data as
	 */
	public static function normalize_old_node( $node, $output = array() ) {
		if ( ! empty( $node->shortcode ) ) {
			$output['code']          = $node->shortcode;
			$output['instagram_url'] = 'https://instagram.com/p/' . $output['code'] . '/';
		}

		if ( ! empty( $node->id ) && ! empty( $node->owner->id ) ) {
			$output['id'] = $node->id . '_' . $node->owner->id;
		}

		if ( ! empty( $node->taken_at_timestamp ) ) {
			$output['timestamp'] = $node->taken_at_timestamp;
		}

		if ( ! empty( $node->edge_media_to_caption->edges[0]->node->text ) ) {
			$output['caption'] = $node->edge_media_to_caption->edges[0]->node->text;
		}

		if ( ! empty( $node->owner->id ) ) {
			$output['owner_id'] = $node->owner->id;
		}

		if ( ! empty( $node->owner->username ) ) {
			$output['owner_username'] = $node->owner->username;
		}

		if ( ! empty( $node->location->id ) ) {
			$output['location_id'] = $node->location->id;
		}

		$media_item = array(
			'id'        => '',
			'src'       => '',
			'video_src' => '',
		);

		if ( ! empty( $node->id ) ) {
			$media_item['id'] = $node->id . '_' . $node->owner->id;
		}

		if ( ! empty( $node->display_url ) ) {
			$media_item['src'] = $node->display_url;
		}

		if ( ! empty( $node->video_url ) ) {
			$media_item['video_src'] = $node->video_url;
		}
		$output['media'][] = $media_item;

		if ( ! empty( $node->edge_sidecar_to_children ) ) {
			// Reset media items since they are duplicated in this node
			$output['media'] = array();

			foreach ( $node->edge_sidecar_to_children as $child_nodes ) {
				if ( ! empty( $child_nodes ) ) {
					foreach ( $child_nodes as $child_node ) {
						$child_node = $child_node->node;

						$media_item = array(
							'id'        => '',
							'src'       => '',
							'video_src' => '',
						);

						if ( ! empty( $child_node->id ) ) {
							$media_item['id'] = $child_node->id . '_' . $child_node->owner->id;
						}

						if ( ! empty( $child_node->display_url ) ) {
							$media_item['src'] = $child_node->display_url;
						}

						if ( ! empty( $child_node->video_url ) ) {
							$media_item['video_src'] = $child_node->video_url;
						}
						$output['media'][] = $media_item;
					}
				}
			}
		}
		return $output;
	}
}
