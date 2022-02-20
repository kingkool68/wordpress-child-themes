<?php
class Instagram_Scraper {

	public $page_type = '';

	public $page_info = array();

	public $page_url = '';

	public $recent_next_page_url = '';

	public $items = array();

	public function __construct( $html = '' ) {
		$raw_json = $this->parse_from_html( $html );

		// Determine what kind of page this is
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
	}

	/**
	 * Given an HTML page from Instagram, return a JSON of the data from that page
	 *
	 * @link https://github.com/raiym/instagram-php-scraper/blob/849f464bf53f84a93f86d1ecc6c806cc61c27fdc/src/InstagramScraper/Instagram.php#L32
	 *
	 * @param  string $html HTML from an Instagram URL
	 * @return json         Instagram data embedded in the page
	 */
	public function parse_from_html( $html = '' ) {
		// Parse the page response and extract the JSON string
		$arr = explode( 'window.__additionalDataLoaded(', $html );
		if ( ! empty( $arr[1] ) ) {
			$parts    = explode( "/',{", $arr[1] );
			$json_str = '{' . $parts[1];
			$json_str = explode( ');</script>', $json_str );
			$json_str = $json_str[0];
			return json_decode( $json_str );
		}

		$arr = explode( 'window._sharedData = ', $html );
		if ( ! empty( $arr[1] ) ) {
			$json = explode( ';</script>', $arr[1] );
			$json = $json[0];
			return json_decode( $json );
		}
	}

	public function parse_location_page_json( $raw_json = '' ) {
		if ( empty( $raw_json->entry_data->LocationsPage[0]->native_location_data ) ) {
			wp_die( 'Bad Location Page Data' );
		}
		$this->page_type = 'location-page';

		$root = $raw_json->entry_data->LocationsPage[0]->native_location_data;

		$page_info = array();
		if ( ! empty( $root->location_info ) ) {
			$location_info = $root->location_info;
			// Map our location_info object properties to our page_info properties
			$mapping = array(
				'location_id'      => 'location_id',
				'name'             => 'name',
				'phone'            => 'phone',
				'website'          => 'website',
				'category'         => 'category',
				'lat'              => 'latitude',
				'lng'              => 'longitude',
				'location_address' => 'street_address',
				'location_city'    => 'city',
				'location_region'  => 'region',
				'location_zip'     => 'postcode',
			);
			foreach ( $mapping as $ig_key => $key ) {
				$page_info[ $key ] = '';
				if ( ! empty( $location_info->{$ig_key} ) ) {
					$page_info[ $key ] = $location_info->{$ig_key};
				}
			}
		}
		$this->page_info = $page_info;

		$sections = array();
		$output   = array();
		$sections = array_merge( $root->ranked->sections, $sections );
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

	public function parse_tag_page_json( $raw_json = '' ) {
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

	public function parse_tagged_user_page_json( $raw_json = '' ) {
		if ( empty( $raw_json->entry_data->ProfilePage[0] ) ) {
			wp_die( 'Bad Tagged User Page Data' );
		}
		$this->page_type = 'user-page';

		$root = $raw_json->entry_data->ProfilePage[0]->graphql->user;
		var_dump( $root->edge_owner_to_timeline_media->edges );
		return;
		$media_posted_by_user = $root->edge_owner_to_timeline_media->edges;
		$sections             = array();
		$output               = array();
		$sections             = array_merge( $root->top->sections, $sections );
		$sections             = array_merge( $root->recent->sections, $sections );
		foreach ( $sections as $section ) {
			if ( ! empty( $section->layout_content->medias ) ) {
				$medias = $section->layout_content->medias;
				foreach ( $medias as $item ) {
					$node     = $item->media;
					$output[] = $this->normalize_node( $node );
				}
			}
		}
	}

	public function parse_single_post_json( $raw_json = '' ) {
		if ( empty( $raw_json->items[0] ) ) {
			wp_die( 'Bad Single Post Data' );
		}
		$this->page_type = 'single-page';

		$root   = $raw_json->items;
		$output = array();
		foreach ( $root as $node ) {
			$output[] = $this->normalize_node( $node );
		}
		return $output;
	}

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
				'id'            => '',
				'src'           => '',
				'thumbnail_src' => '',
				'video_src'     => '',
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

		return $output;
	}
}
