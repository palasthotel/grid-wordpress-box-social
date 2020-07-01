<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace Palasthotel\Grid\SocialBoxes\Type;

use Google_Client;
use Google_Service_YouTube;
use Palasthotel\Grid\SocialBoxes\Settings;

class Youtube extends Base{

	const OPTION_CONFIG = "grid_youtube_config";

	const TOKEN_CODE = "grid_youtube_code";
	const TOKEN = "grid_youtube_token";

	const PAGE_CALLBACK = "grid_social_boxes_youtube_callback";

	/**
	 * @var Google_Client
	 */
	private $api;

	/**
	 * @var Google_Service_YouTube
	 */
	private $youtube;

	public function __construct($settings ) {
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

			$config = get_site_option( self::OPTION_CONFIG, '');
			$access_token = get_site_option(self::TOKEN,  '');

			if(!empty($config) && is_array($config)){
				$this->api = new Google_Client();
				$this->api->addScope(Google_Service_YouTube::YOUTUBE);
				$this->api->setAuthConfig($config);
				$this->api->setRedirectUri(get_site_url()."/".self::PAGE_CALLBACK);
				$this->api->setAccessType("offline");
				$this->api->setIncludeGrantedScopes(true);
			}

			if($this->api != null && !empty($access_token)){
				$this->api->setAccessToken($access_token);

				if($this->api->isAccessTokenExpired()){

					$this->api->fetchAccessTokenWithRefreshToken();

//					$code = get_site_option(self::TOKEN_CODE, '');
//					$this->api->authenticate($code);
//					$this->api->fetchAccessTokenWithAuthCode()
//					$token = $this->api->getAccessToken();
//					update_site_option(self::TOKEN, $token);
				}
			}

		}

		return $this->api;
	}

	/**
	 * @return Google_Service_YouTube
	 */
	public function getYoutube(){
		$api = $this->getApi();
		if($api != null && !$api->isAccessTokenExpired()){
			$this->youtube = new Google_Service_YouTube($this->api);
		}
		return $this->youtube;
	}

	/**
	 * render settings page
	 */
	public function renderPage(){

		if(!empty($_FILES[self::OPTION_CONFIG])){

			if(isset($_FILES[self::OPTION_CONFIG])
			   && !empty($_FILES[self::OPTION_CONFIG]
			             && is_file($_FILES[self::OPTION_CONFIG]['tmp_name']))){
				$config = $_FILES[self::OPTION_CONFIG]['tmp_name'];
				$config_content = file_get_contents($config);
				$config_assoc = json_decode($config_content, true);
				update_site_option(self::OPTION_CONFIG, $config_assoc);
			}


			$api = $this->getApi();
			if($api != null){

				$url = filter_var($api->createAuthUrl(), FILTER_SANITIZE_URL);
				header('Location: ' . $url);
				die();
			}

		}
		?>
		
		<div class="notice notice-success">
			<p>Config JSON content:</p>
			<?php
			$config = get_site_option(self::OPTION_CONFIG, array());
			echo "<p>";
			$this->settings->echo_array($config);
			echo "</p>";
			?>
		</div>
		
		
		<?php
		$api = $this->getApi();
		if($api != null){
			$token = $api->getAccessToken();
			if($api->isAccessTokenExpired()){
				echo "<div class=\"notice notice-error\"><p>Authorization not granted. Token expired.</p></div>";
			} else {
				echo "<div class=\"notice notice-success\"><p>Authorization granted</p>";
			}
			echo "<p>";
			$this->settings->echo_array($token);
			echo "</p>";
			echo "</div>";
		}
		?>
		
		<form id="google-auth-form" method="POST" enctype="multipart/form-data"
		      action="<?php echo $this->getSelfURL(array("noheader"=>"true")); ?>">
			<p>Anleitung für Youtube Data API. https://console.developers.google.com/</p>
			<p>
				<?php _e('Callback URL:', 'grid-social-boxes'); ?><br>
				<b><?php echo get_site_url().'/'.self::PAGE_CALLBACK ?></b>
			</p>
			<p>
				<label for="<?php echo self::OPTION_CONFIG; ?>">Upload oauth2 credentials JSON:</label><br>
				<input type="file" name="<?php echo self::OPTION_CONFIG; ?>" />
			</p>
			
			<?php echo get_submit_button( "Save" ); ?>
		</form>
		
		
		
		<?php
		
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

			$api = $this->getApi();

			if (isset($_GET['code'])) {

				$api->authenticate($_GET['code']);
				update_site_option(self::TOKEN_CODE, sanitize_text_field($_GET['code']));

				$token= $api->getAccessToken();
				update_site_option(self::TOKEN, $token);

			}

			header( 'location: '.$this->getSelfURL());
			die();
		}
	}
}
