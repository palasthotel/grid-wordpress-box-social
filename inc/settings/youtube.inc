<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;


use GridSocialBoxes\Settings;

class Youtube extends Base{
	
	const OPTION_KEY_CLIENT_ID = "grid_youtube_client_id";
	const OPTION_KEY_CLIENT_KEY = "grid_youtube_client_key";
	const PAGE_CALLBACK = "grid_social_boxes_youtube_callback";
	
	/**
	 * @var \Google_Service
	 */
	private $api;
	
	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );
		
		/**
		 * another implementation for callback url
		 * because only two get parameters are allowed for redirection url
		 * with instagram api
		 */
		add_action( 'init', array($this, 'register_route') );
		add_filter( 'query_vars', array($this, 'query_vars'), 10, 1 );
		add_action( 'parse_request' , array($this, 'callback'));
		
	}
	
	public function getSlug() {
		return Settings::TYPE_YOUTUBE;
	}
	
	public function getTitle(){
		return __("Youtube", "grid-social-boxes");
	}
	
	public function getApi(){
		
		
		if($this->api == null){
			
			$this->settings->plugin->include_youtube_api();
			$id = get_site_option( self::OPTION_KEY_CLIENT_ID, '' );
			$key = get_site_option( self::OPTION_KEY_CLIENT_KEY, '' );
			
			if(!empty($id) && !empty($key)){
				$this->api = new \Google_Client();
				
				$this->api->setScopes('https://www.googleapis.com/auth/youtube');
				$this->api->setClientId($id);
				$this->api->setClientSecret($key);
				
				$this->api->setRedirectUri(get_site_url()."/".self::PAGE_CALLBACK);
			}
		}
		
		return $this->api;
	}
	
	/**
	 * render settings page
	 */
	public function renderPage(){
		
		if(!empty($_POST[self::OPTION_KEY_CLIENT_KEY])){
			$id = sanitize_title($_POST[self::OPTION_KEY_CLIENT_ID]);
			$key = sanitize_title($_POST[self::OPTION_KEY_CLIENT_KEY]);
			update_site_option(self::OPTION_KEY_CLIENT_ID, $id);
			update_site_option(self::OPTION_KEY_CLIENT_KEY, $key);
			
			$api = $this->getApi();
			if($api != null){
				session_start();
				
				wp_redirect($api->createAuthUrl());
				die();
			}
			
		}
		
		
		?>
		<p>Anleitung für Youtube. https://console.developers.google.com/</p>
		<form method="POST" action="<?php echo $this->getSelfURL(array("noheader"=>"true")); ?>">
			<p>
				<label for="<?php echo self::OPTION_KEY_CLIENT_ID; ?>">Client ID:</label><br>
				<input type="text" name="<?php echo self::OPTION_KEY_CLIENT_ID; ?>" id="<?php echo self::OPTION_KEY_CLIENT_ID; ?>"
				       value="<?php echo get_site_option( self::OPTION_KEY_CLIENT_ID, '' );?>"><br>
				<label for="<?php echo self::OPTION_KEY_CLIENT_KEY; ?>">Client Schlüssel:</label><br>
				<input type="text" name="<?php echo self::OPTION_KEY_CLIENT_KEY; ?>" id="<?php echo self::OPTION_KEY_CLIENT_KEY; ?>"
				       value="<?php echo get_site_option( self::OPTION_KEY_CLIENT_KEY, '' );?>">
			</p>
			<?php echo get_submit_button( "Save" ); ?>
		</form>
		<?php
		$api = $this->getApi();
		if($api != null){
			
			echo "<p>Token: -- ".$api->getAccessToken()." --</p>";
			
		}
	}
	
	/**
	 * register submenu page for callback
	 */
	public function register_route(){
		add_rewrite_rule(
			'^'.self::PAGE_CALLBACK . '$',
			'index.php?'.self::PAGE_CALLBACK . '=1',
			'top'
		);
	}
	
	/**
	 * add query vars
	 * @param $query_vars
	 *
	 * @return array
	 */
	public function query_vars( $query_vars ){
		$query_vars[] = self::PAGE_CALLBACK;
		return $query_vars;
	}
	
	/**
	 * register submenu page for callback
	 */
	public function admin_menu(){
		add_submenu_page(
			null,
			'Grid Youtube Callback',
			'Grid Youtube Callback',
			'manage_options',
			self::PAGE_CALLBACK,
			array( $this, 'callback')
		);
	}
	
	/**
	 * callback for twitter
	 */
	public function callback($query) {
		
		/**
		 * var is set?
		 */
		
		if(!empty($query->query_vars[self::PAGE_CALLBACK])
		   && $query->query_vars[self::PAGE_CALLBACK]== "1" ) {
			
			/**
			 * user may manage options?
			 */
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_redirect( home_url() );
				die();
			}
			
			
			session_start();
			
			$api = $this->getApi();
			
			if (isset($_GET['code'])) {
				if (strval($_SESSION['state']) !== strval($_GET['state'])) {
					die('The session state did not match.');
				}
				
				$api->authenticate($_GET['code']);
				$_SESSION['token'] = $api->getAccessToken();
//			header('Location: ' . $redirect);
			}
			
			die($_SESSION['token']);
			
			
		}
		
	}
	
	
}