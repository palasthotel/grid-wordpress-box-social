<?php
/**
 * Plugin Name: Grid Social Boxes
 * Plugin URI: https://github.com/palasthotel/wordpress-grid-box-social
 * Description: Some social network boxes. Facebook, Twitter, Instagram and Youtube.
 * Version: 1.6.1
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

namespace Palasthotel\Grid\SocialBoxes;

// If this file is called directly, abort.
use Abraham\TwitterOAuth\TwitterOAuth;
use Facebook\Facebook;
use Google_Service_YouTube;
use MetzWeb\Instagram\Instagram;
use MetzWeb\Instagram\InstagramException;

if ( ! defined( 'WPINC' ) ) {
	die;
}


include_once dirname( __FILE__ ) . '/settings.php';

/**
 * @property string dir
 * @property string url
 * @property Settings settings
 * @property OEmbed oembed
 */
class Plugin {

	const DOMAIN = "grid-social-boxes";

	const HANDLE_API_JS = "grid-social-boxes-api";
	const HANDLE_FACEBOOK_JS = "grid-social-boxes-facebook";

	const FILTER_FACEBOOK_JS_ARGS = "grid_social_boxes_facebook_js_args";

	/**
	 * construct
	 */
	function __construct() {

		require_once dirname(__FILE__)."/vendor/autoload.php";

		load_plugin_textdomain(
			Plugin::DOMAIN,
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);

		/**
		 * base paths
		 */
		$this->dir = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);
		
		add_action("grid_load_classes", array($this,"load_classes") );
		add_filter("grid_templates_paths", array($this,"template_paths") );

		$this->oembed = new OEmbed();
		$this->settings = new Settings( $this );

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
	 */
	public function load_classes() {

		/**
		 * twitter box
		 */
		if($this->get_twitter_api() != NULL){
			require_once dirname(__FILE__) . '/grid/grid_wp_twitterboxes.php';
		}
		
		/**
		 * facebook box
		 */
		require('grid/grid_fb_like_box_box.php');
		if ( $this->get_facebook_api() != NULL ) {
			require_once dirname(__FILE__) . '/grid/grid_facebook_feed_box.php';
		}


		/**
		 * instagram box
		 */
		if ( $this->get_instagram_api() != NULL ) {
			require_once dirname(__FILE__) . '/grid/grid_instagram_box.php';
		}

		/**
		 * youtube box
		 */
		require_once dirname(__FILE__)."/grid/grid_youtube_feed_box.php";
		if ( $this->get_youtube_api() != NULL ) {
			require_once dirname(__FILE__) . '/grid/grid_youtube_box.php';
		}

		/**
		 * social timeline
		 */
		require_once dirname(__FILE__)."/grid/grid_social_timeline_box.php";

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
	 * @return TwitterOAuth|null
	 */
	public function get_twitter_api() {
		/**
		 * @var Type\Twitter $settings
		 */
		$settings = $this->settings->pages[ Settings::TYPE_TWITTER ];

		return $settings->getApi();
	}

	/**
	 * @return Instagram|null
	 * @throws InstagramException
	 */
	public function get_instagram_api() {
		/**
		 * @var Type\Instagram $settings
		 */
		$settings = $this->settings->pages[Settings::TYPE_INSTAGRAM ];

		return $settings->getApi();
	}

	/**
	 * @return Facebook
	 */
	public function get_facebook_api() {
		/**
		 * @var Type\Facebook $settings
		 */
		$settings = $this->settings->pages[ Settings::TYPE_FACEBOOK ];
		return $settings->getApi();
	}

	/**
	 * @return Google_Service_YouTube
	 */
	public function get_youtube_api() {
		/**
		 * @var Type\Youtube $settings
		 */
		$settings = $this->settings->pages[Settings::TYPE_YOUTUBE ];

		return $settings->getYoutube();
	}

	/**
	 * singleton
	 *
	 * @var Plugin
	 */
	private static $instance = NULL;

	public static function instance() {
		if ( self::$instance == NULL ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

}

Plugin::instance();

require_once dirname( __FILE__ ) . '/public-functions.php';

?>
