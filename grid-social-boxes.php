<?php
/**
 * Plugin Name: Grid Social Boxes
 * Plugin URI: https://github.com/palasthotel/wordpress-grid-box-social
 * Description: Some social network boxes. Facebook and Twitter for now.
 * Version: 1.3.2
 * Author: Palasthotel <rezeption@palasthotel.de> (in person: Benjamin Birkenhake, Edward Bock, Enno Welbers)
 * Author URI: http://www.palasthotel.de
 * License: http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @copyright Copyright (c) 2014, Palasthotel
 * @package Palasthotel\Grid-WordPress-Box-Social
 */

class GridSocialBoxes{
	public $dir;
	public $url;
	
	/**
	 * construct
	 */
	function __construct(){
		/**
		 * base paths
		 */
		$this->dir = plugin_dir_path(__FILE__);
		$this->url = plugin_dir_url(__FILE__);
		
		add_action("grid_load_classes", array($this,"load_classes") );
		add_filter("grid_templates_paths", array($this,"template_paths") );
		
		require_once $this->dir."/inc/settings.inc";
		$this->settings = new \GridSocialBoxes\Settings($this);
	}

	/**
	 * load grid box classes
	 */
	public function load_classes(){
		
		/**
		 * twitter box
		 */
		$this->include_twitter_api();
		require( 'grid_twitterbox/grid_wp_twitterboxes.php' );
		
		/**
		 * facebook box
		 */
		require( 'grid_facebook_like_box/grid_fb_like_box_box.php' );
		
		/**
		 * instagram box
		 */
		if($this->get_instagram_api() != null){
			require('grid_instagram_box/grid_instagram_box.php');
		}
		
		/**
		 * instagram box
		 */
		if($this->get_youtube_api() != null){
			require('grid_youtube_box/grid_youtube_box.php');
		}
		
		/**
		 * social timeline
		 */
		require "grid_social_timeline_box/grid_social_timeline_box.php";

	}

	/**
	 * add grid templates suggestion path
	 * @param $paths
	 * @return array
	 */
	public function template_paths($paths){
		$paths[] = dirname(__FILE__)."/templates";
		return $paths;
	}
	
	/**
	 * @return \TwitterOAuth|null
	 */
	public function get_twitter_api(){
		/**
		 * @var $settings \GridSocialBoxes\Settings\Twitter
		 */
		$settings = $this->settings->pages[\GridSocialBoxes\Settings::TYPE_TWITTER];
		return $settings->getApi();
	}
	
	/**
	 * include twitter api if not already included
	 */
	public function include_twitter_api(){
		if(!class_exists("TwitterOAuth")){
			require_once 'grid_twitterbox/twitteroauth/twitteroauth.php';
		}
	}
	
	/**
	 * @return \MetzWeb\Instagram\Instagram|null
	 */
	public function get_instagram_api(){
		/**
		 * @var $settings \GridSocialBoxes\Settings\Instagram
		 */
		$settings = $this->settings->pages[\GridSocialBoxes\Settings::TYPE_INSTAGRAM];
		return $settings->getApi();
	}
	
	/**
	 * include instagram api if not already included
	 */
	public function include_instagram_api(){
		if(!class_exists("Instagram")){
			require_once 'grid_instagram_box/instagram-api/InstagramException.php';
			require_once 'grid_instagram_box/instagram-api/Instagram.php';
		}
	}
	
	/**
	 * @return \Google_Service_YouTube
	 */
	public function get_youtube_api(){
		/**
		 * @var $settings \GridSocialBoxes\Settings\Youtube
		 */
		$settings = $this->settings->pages[\GridSocialBoxes\Settings::TYPE_YOUTUBE];
		return $settings->getYoutube();
	}
	
	/**
	 * include youtube api if not already included
	 */
	public function include_youtube_api(){
		if(!class_exists("Google_Service")){
			require_once 'grid_youtube_box/google-api-php-client-2.0.2/vendor/autoload.php';
		}
	}
}
global $grid_social_boxes;
$grid_social_boxes = new GridSocialBoxes();


?>