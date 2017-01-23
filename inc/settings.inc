<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes;

use GridSocialBoxes\Settings\Facebook;
use GridSocialBoxes\Settings\Instagram;
use GridSocialBoxes\Settings\Twitter;
use GridSocialBoxes\Settings\Youtube;

class Settings {
	
	/**
	 * settings page slug
	 */
	const PAGE_SLUG = "grid_social_boxes_settings";
	
	/**
	 * social media types
	 */
	const TYPE_TWITTER = "twitter";
	const TYPE_INSTAGRAM = "instagram";
	const TYPE_YOUTUBE = "youtube";
	const TYPE_FACEBOOK = "facebook";
	
	/**
	 * @var \GridSocialBoxes
	 */
	public $plugin;
	
	/**
	 * @var \GridSocialBoxes\Settings\Twitter
	 */
	public $twitter;
	
	/**
	 * Settings constructor.
	 *
	 * @param \GridSocialBoxes $plugin
	 */
	public function __construct(\GridSocialBoxes $plugin) {
		$this->plugin = $plugin;
		
		$this->pages = array();
		
		require_once $this->plugin->dir."/inc/settings/base.inc";
		
		require_once $this->plugin->dir."/inc/settings/twitter.inc";
		$twitter = new Twitter($this);
		$this->pages[$twitter->getSlug()] = $twitter;
		
		require_once $this->plugin->dir."/inc/settings/instagram.inc";
		$instagram = new Instagram($this);
		$this->pages[$instagram->getSlug()] = $instagram;
		
		require_once $this->plugin->dir."/inc/settings/youtube.inc";
		$youtube = new Youtube($this);
		$this->pages[$youtube->getSlug()] = $youtube;
		
		require_once $this->plugin->dir."/inc/settings/facebook.inc";
		$facebook = new Facebook($this);
		$this->pages[$facebook->getSlug()] = $facebook;
		
		add_action( 'admin_menu', array($this, 'social_boxes_admin_menu') );
		
	}
	
	/**
	 * register admin menu paths
	 */
	public function social_boxes_admin_menu() {
		add_submenu_page(
			'options-general.php',
			'Grid Social Boxes',
			'Grid Social Boxes',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_settings')
		);
		
	}
	/**
	 * render the settings page
	 */
	public function render_settings() {
		/**
		 * get selected or use first key of pages
		 */
		$current = (isset($_GET["tab"]))? $_GET["tab"]: array_keys($this->pages)[0];
		
		/**
		 * render if exits
		 * cache in object for redirects in page render to work
		 */
		ob_start();
		if(array_key_exists($current, $this->pages)){
			/**
			 * @var $obj Settings\Base
			 */
			$obj = $this->pages[$current];
			$obj->renderPage();
		} else {
			echo "<p>Ups... not found</p>";
		}
		$content = ob_get_contents();
		ob_end_clean();
		
		?>
		<h2>Social Boxes Settings</h2>
		<?php
		echo '<h2 class="nav-tab-wrapper">';
		
		foreach( $this->pages as $slug => $obj ){
			/**
			 * @var $obj Settings\Base
			 */
			$class = ( $slug == $current ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='".$obj->getSelfURL()."'>".$obj->getTitle()."</a>";
		}
		echo '</h2>';
		
		echo $content;
	}
	
	/**
	 * echo array contents
	 * @param $data
	 * @param int $level
	 */
	public function echo_array($data, $level = 0){
		++$level;
		$level_prefix = "";
		for( $i = 0; $i < $level; $i++){
			$level_prefix.= "&nbsp;&nbsp;&nbsp;&nbsp;";
		}
		if(is_string($data) || is_numeric($data)){
			echo $data."<br/>";
		}
		if(is_array($data)){
			foreach ($data as $key => $value){
				echo $level_prefix."<b>".$key.": </b>";
				if(is_array($value)){
					echo "<br />";
				}
				$this->echo_array($value, $level);
			}
		}
	}
	
}