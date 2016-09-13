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

	const OPTION_CONFIG = "grid_youtube_config";

	const TOKEN_CODE = "grid_youtube_token_code";
	const TOKEN_ACCESS = "grid_youtube_token_access";
	const TOKEN_TYPE = "grid_youtube_token_type";
	const TOKEN_EXPIRES = "grid_youtube_token_expires";
	const TOKEN_CREATED = "grid_youtube_token_created";

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
			$config = get_site_option( self::OPTION_CONFIG, '');
			$access_token = get_site_option(self::TOKEN_ACCESS,  '');

			if(!empty($access_token)){
				$this->api = new \Google_Client();
				$this->api->addScope(\Google_Service_YouTube::YOUTUBE_READONLY);
				$this->api->setAccessToken($access_token);
			}

			if(!empty($config) && is_array($config)){
				if($this->api == null) $this->api = new \Google_Client();
				$this->api->setAuthConfig($config);
				$this->api->setRedirectUri(get_site_url()."/".self::PAGE_CALLBACK);
			}



		}

		return $this->api;
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
		<p>Anleitung für Youtube. https://console.developers.google.com/</p>
		<form id="google-auth-form" method="POST" enctype="multipart/form-data"
		      action="<?php echo $this->getSelfURL(array("noheader"=>"true")); ?>">
			<p>
				<label for="<?php echo self::OPTION_CONFIG; ?>">Config:</label><br>
				<input type="file" name="<?php echo self::OPTION_CONFIG; ?>" />
			<h2>Credentials</h2>
					<?php
					$config = get_site_option(self::OPTION_CONFIG, array());

					function echo_array($data, $level){
						++$level;
						$level_prefix = "";
						for( $i = 0; $i < $level; $i++){
							$level_prefix.= "&nbsp;&nbsp;&nbsp;&nbsp;";
						}
						if(is_string($data)){
							echo $data."<br/>";
						}
						if(is_array($data)){
							foreach ($data as $key => $value){
								echo $level_prefix."<b>".$key.": </b>";
								if(is_array($value)){
									echo "<br />";
								}
								echo_array($value, $level);
							}
						}
					}
					echo_array($config, 0);
					?>
			</p>
			<?php
			$api = $this->getApi();
			if($api != null){
				$token = $api->getAccessToken();
				echo "<p><b>Token:</b> ".$token["access_token"]."</p>";

			}
			 ?>
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
				update_site_option(self::TOKEN_ACCESS, $token["access_token"]);
				update_site_option(self::TOKEN_TYPE, $token["token_type"]);
				update_site_option(self::TOKEN_EXPIRES, $token["expires_in"]);
				update_site_option(self::TOKEN_CREATED, $token["created"]);

			}

			header( 'location: '.$this->getSelfURL());
			die();
		}
	}
}
