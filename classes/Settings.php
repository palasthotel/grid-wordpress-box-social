<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace Palasthotel\Grid\SocialBoxes;

use Palasthotel\Grid\SocialBoxes\Type\Base;

/**
 * @property Base[] pages
 */
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
	 * @var Plugin
	 */
	public $plugin;
	
	/**
	 * @var Type\Twitter
	 */
	public $twitter;
	/**
	 * @var Type\Instagram
	 */
	public $instagram;
	/**
	 * @var Type\Youtube
	 */
	public $youtube;
	/**
	 * @var Type\Facebook
	 */
	public $facebook;

	/**
	 * Settings constructor.
	 *
	 * @param Plugin $plugin
	 */
	public function __construct( $plugin) {
		$this->plugin = $plugin;
		
		$this->pages = array();

		$this->twitter = new Type\Twitter($this);
		$this->pages[$this->twitter->getSlug()] = $this->twitter;
		
		$this->instagram = new Type\Instagram($this);
		$this->pages[$this->instagram->getSlug()] = $this->instagram;
		
		$this->youtube = new Type\Youtube($this);
		$this->pages[$this->youtube->getSlug()] = $this->youtube;
		
		$this->facebook = new Type\Facebook($this);
		$this->pages[$this->facebook->getSlug()] = $this->facebook;
		
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