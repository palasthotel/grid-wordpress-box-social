<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes;

use GridSocialBoxes\Settings\Instagram;
use GridSocialBoxes\Settings\Twitter;

class Settings {
	
	const PAGE_SLUG = "grid_social_boxes_settings";
	
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
}