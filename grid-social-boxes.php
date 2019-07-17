<?php
/**
 * Plugin Name: Grid Social Boxes
 * Plugin URI: https://github.com/palasthotel/wordpress-grid-box-social
 * Description: Some social network boxes. Facebook, Twitter, Instagram and Youtube.
 * Version: 1.4.8
 * Author: Palasthotel <rezeption@palasthotel.de> (in
 * person: Edward Bock, Enno Welbers)
 * Author URI: http://www.palasthotel.de
 * Text Domain: grid-social-boxes Domain
 * Path: /languages
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 *
 * @copyright Copyright (c) 2016, Palasthotel
 * @package Palasthotel\GridSocialBoxes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


include_once dirname( __FILE__ ) . '/settings.php';

/**
 * @property string dir
 * @property string url
 * @property \GridSocialBoxes\Settings settings
 */
class GridSocialBoxes {

	const DOMAIN = "grid-social-boxes";

	const HANDLE_API_JS = "grid-social-boxes-api";
	const HANDLE_FACEBOOK_JS = "grid-social-boxes-facebook";

	const FILTER_FACEBOOK_JS_ARGS = "grid_social_boxes_facebook_js_args";

	/**
	 * construct
	 */
	function __construct() {
		/**
		 * base paths
		 */
		$this->dir = plugin_dir_path( __FILE__ );
		$this->url = plugin_dir_url( __FILE__ );

		load_plugin_textdomain(
			GridSocialBoxes::DOMAIN,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);


		add_action( "grid_load_classes", array( $this, "load_classes" ) );
		add_filter( "grid_templates_paths", array( $this, "template_paths" ) );

		require_once dirname( __FILE__ ) . "/inc/settings.inc";
		$this->settings = new \GridSocialBoxes\Settings( $this );
		$this->dir = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);
		
		add_action("grid_load_classes", array($this,"load_classes") );
		add_filter("grid_templates_paths", array($this,"template_paths") );
		
		require_once  "inc/settings.inc";
		$this->settings = new \GridSocialBoxes\Settings($this);

		/**
		 * on activate or deactivate plugin
		 */
		register_activation_hook( __FILE__, array( $this, "activation" ) );
		register_deactivation_hook( __FILE__, array( $this, "deactivation" ) );

	}

	/**
	 * on plugin activation
	 */
	function activation() {
		$this->settings->twitter->add_endpoint();
		flush_rewrite_rules();
	}

	/**
	 * on plugin deactivation
	 */
	function deactivation() {
		flush_rewrite_rules();
	}

	/**
	 * load grid box classes
	 *
	 * @throws \Facebook\Exceptions\FacebookSDKException
	 * @throws \MetzWeb\Instagram\InstagramException
	 */
	public function load_classes() {

		/**
		 * twitter box
		 */
		$this->include_twitter_api();
		require( 'grid_twitterbox/grid_wp_twitterboxes.php' );

		require( dirname(__FILE__).'/grid_twitterbox/grid_wp_twitterboxes.php' );
		
		/**
		 * facebook box
		 */
		require( 'grid_facebook_box/grid_fb_like_box_box.php' );
		if ( $this->get_facebook_api() != NULL ) {
			require( 'grid_facebook_box/grid_facebook_feed_box.php' );
		}


		/**
		 * instagram box
		 */
		if ( $this->get_instagram_api() != NULL ) {
			require( 'grid_instagram_box/grid_instagram_box.php' );
		}

		/**
		 * youtube box
		 */
		if ( $this->get_youtube_api() != NULL ) {
			require( 'grid_youtube_box/grid_youtube_box.php' );
		}


		/**
		 * social timeline
		 */
		require "grid_social_timeline_box/grid_social_timeline_box.php";

	}

	/**
	 * add grid templates suggestion path
	 *
	 * @param $paths
	 *
	 * @return array
	 */
	public function template_paths( $paths ) {
		$paths[] = dirname( __FILE__ ) . "/templates";

		return $paths;
	}

	/**
	 * @return \Abraham\TwitterOAuth\TwitterOAuth|null
	 */
	public function get_twitter_api() {
		/**
		 * @var $settings \GridSocialBoxes\Settings\Twitter
		 */
		$settings = $this->settings->pages[ \GridSocialBoxes\Settings::TYPE_TWITTER ];

		return $settings->getApi();
	}

	/**
	 * include twitter api if not already included
	 */

	public function include_twitter_api(){
		if(!class_exists("TwitterOAuth")){
			require_once dirname(__FILE__).'/grid_twitterbox/vendor/autoload.php';
		}
	}

	/**
	 * @return \MetzWeb\Instagram\Instagram|null
	 * @throws \MetzWeb\Instagram\InstagramException
	 */
	public function get_instagram_api() {
		/**
		 * @var $settings \GridSocialBoxes\Settings\Instagram
		 */
		$settings = $this->settings->pages[ \GridSocialBoxes\Settings::TYPE_INSTAGRAM ];

		return $settings->getApi();
	}

	/**
	 * include instagram api if not already included
	 */
	public function include_instagram_api() {
		if ( ! class_exists( "Instagram" ) ) {
			require_once 'grid_instagram_box/instagram-api/InstagramException.php';
			require_once 'grid_instagram_box/instagram-api/Instagram.php';
		}
	}

	/**
	 * @return \Facebook\Facebook
	 * @throws \Facebook\Exceptions\FacebookSDKException
	 */
	public function get_facebook_api() {
		/**
		 * @var $settings \GridSocialBoxes\Settings\Facebook
		 */
		$settings = $this->settings->pages[ GridSocialBoxes\Settings::TYPE_FACEBOOK ];

		return $settings->getApi();
	}

	/**
	 * include facebook sdk
	 */
	public function include_facebook_sdk() {
		if ( ! class_exists( "Facebook" ) ) {
			require_once "grid_facebook_box/facebook-sdk-v5/autoload.php";
		}
	}

	/**
	 * @return \Google_Service_YouTube
	 */
	public function get_youtube_api() {
		/**
		 * @var $settings \GridSocialBoxes\Settings\Youtube
		 */
		$settings = $this->settings->pages[ \GridSocialBoxes\Settings::TYPE_YOUTUBE ];

		return $settings->getYoutube();
	}

	/**
	 * include youtube api if not already included
	 */
	public function include_youtube_api() {
		if ( ! class_exists( "Google_Service" ) ) {
			require_once 'grid_youtube_box/google-api-php-client-2.0.2/vendor/autoload.php';
		}
	}

	/**
	 * singleton
	 *
	 * @var \GridSocialBoxes
	 */
	private static $instance = NULL;

	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance = new GridSocialBoxes();
		}

		return self::$instance;
	}

}

GridSocialBoxes::instance();

require_once dirname( __FILE__ ) . '/public-functions.php';

?>
