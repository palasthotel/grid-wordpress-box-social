<?php

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress
 */

class grid_twitter_box extends grid_list_box {

	public function __construct() {
		parent::__construct();
		$this->content->limit = 5;
		$this->content->user = '';
		$this->content->retweet = 'timeline';
		$this->content->exclude_replies = false;
		$this->content->include_rts = false;
	}

	public function type() {
		return 'twitter';
	}

	protected function prebuild() {
		if ( $this->content->user == '' ) {
			return '';
		}
		return null;
	}

	/**
	 * @param TwitterOAuth $connection
	 *
	 * @return mixed
	 */
	protected function fetch( $connection ) {
		if ( 'retweets' == $this->content->retweet ) {
			$result = $connection->get( 'search/tweets', array(
				'src'=>'typd',
				'q'=> $this->content->user,
			));
			$result = $result->statuses;
		} else {
			$args = [];
			$args['screen_name'] = $this->content->user;
			$args['tweet_mode'] = "extended";
			$args['count'] = $this->content->limit;

			if( ! isset( $this->content->exclude_replies ) ) {
				$this->content->exclude_replies = false;
			}
			$args['exclude_replies'] = $this->content->exclude_replies;

			if( ! isset( $this->content->include_rts ) ) {
				$this->content->include_rts = false;
			}
			$args['include_rts'] = $this->content->include_rts;

			$result = $connection->get( 'statuses/user_timeline', $args );
		}

		return $result;
	}

	public function build( $editmode ) {
		if ( $editmode ) {
			$this->content->title = t("Twitter by User");
			return $this->content;
		} else {
			$prebuild = $this->prebuild();
			if ( $prebuild != null ) {
				return $prebuild;
			} else {
				$grid_social_boxes = grid_social_boxes_plugin();
				if($grid_social_boxes == null){
					return "<p>no API found for Twitter</p>";
				}
				
				$connection = $grid_social_boxes->get_twitter_api();
				
				$result = $this->fetch( $connection );
				if ( count( $result ) > $this->content->limit ) {
					$result = array_slice( $result, 0, $this->content->limit );
				}
				ob_start();
				$content = $result;
				$templatePath = $this->template::getPath('grid_twitterbox.tpl.php');
				if ( file_exists( $templatePath ) ) {
					require ( $templatePath );
				} else {
					require(dirname(__FILE__) . '/../templates/grid_twitterbox.tpl.php');
				}
				$result = ob_get_clean();
				return $result;
			}
		}
	}
	
	public function getConnection(){
		
	}

	public function contentStructure () {
		$cs = parent::contentStructure();
		return array_merge($cs , array(
			array(
				'key' => 'limit',
				'type' => 'number',
				'label' => 'Anzahl der Einträge',
			),
			array(
				'key' => 'user',
				'type' => 'text',
				'label' => 'User',
			),
			array(
				'key' => 'exclude_replies',
				'type' => 'checkbox',
				'label' => 'Exclude replies',
			),
			array(
				'key' => 'include_rts',
				'type' => 'checkbox',
				'label' => 'Include retweets',
			),
			array(
				'key' => 'retweet',
				'type' => 'select',
				'label' => t( 'Type' ),
				'selections' => array(
					array(
						'key' => 'timeline',
						'text' => 'Timeline',
					),
					array(
						'key' => 'retweets',
						'text' => 'Retweets',
					),
				)
			),
		));
	}

	public function metaSearch( $criteria, $query ) {
		if ( get_site_option( 'grid_twitterbox_consumer_key', '' ) == '' || get_site_option( 'grid_twitterbox_consumer_secret', '' ) == '' || get_site_option( 'grid_twitterbox_accesstoken', '' ) == '' ) {
			return array();
		}
		return array( $this );
	}

}

class grid_twitter_hashtag_box extends grid_twitter_box {

	public function __construct() {
		parent::__construct();
		$this->content->limit = 5;
		$this->content->hashtag = '';
	}

	public function type() {
		return 'twitter_hashtag';
	}

	/**
	 * @param TwitterOAuth $connection
	 *
	 * @return array
	 */
	public function fetch( $connection ) {
		$output = $connection->get( 'search/tweets', array(
				'q' => $this->content->hashtag,
				"tweet_mode" => "extended",
				"count" => $this->content->limit,
			)
		);
		if ( isset( $output->statuses ) ) {
			$result = $output->statuses;
		} else {
			$result = array();
		}
		return $result;
	}

	protected function prebuild() {
		if ( $this->content->hashtag == '' ) {
			return '';
		}
		return null;
	}

	public function build( $editmode ) {
		if ( $editmode ) {
			$this->content->title = "Twitter by Hashtag";
			return $this->content;
		} else {
			return parent::build( $editmode );
		}
	}

	public function contentStructure () {
		return array(
			array(
				'key' => 'limit',
				'label' => 'Limit',
				'type' => 'number',
			),
			array(
				'key' => 'hashtag',
				'label' => 'Hashtag',
				'type' => 'text',
			),
		);
	}
}
