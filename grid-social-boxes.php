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
		new \GridSocialBoxes\Settings($this);
	}

	/**
	 * load grid box classes
	 */
	public function load_classes(){
		
		// TODO: only add boxes if configured
		
		/**
		 * twitter box
		 */
		$this->social_boxes_include_twitter_api();
		require( 'grid_twitterbox/grid_wp_twitterboxes.php' );
		
		/**
		 * facebook box
		 */
		require( 'grid_facebook_like_box/grid_fb_like_box_box.php' );
		
		/**
		 * instagram box
		 */

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
	 * include twitter api if not already included
	 */
	public function social_boxes_include_twitter_api(){
		if(!class_exists("TwitterOAuth")){
			require_once 'grid_twitterbox/twitteroauth/twitteroauth.php';
		}
	}
	
	/**
	 * include instagram api if not already included
	 */
	public function social_boxes_include_instagram_api(){
		if(!class_exists("Instagram")){
			require_once 'grid_instagram_box/instagram-api/instagram.php';
		}
	}
}
new GridSocialBoxes();


?>