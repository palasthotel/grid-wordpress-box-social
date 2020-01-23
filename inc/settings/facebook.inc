<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;

use Facebook\Exceptions\FacebookSDKException;
use GridSocialBoxes\Settings;

class Facebook extends Base {

	const OPTION_APP_ID = "grid_facebook_app_id";

	const OPTION_SECRET = "grid_facebook_secret";

	const OPTION_APP_TOKEN = "grid_facebook_app_token";

	const OPTION_LAZY = "grid_facebook_lazy";

	const OPTION_ACCESS_TOKEN_EXPIRES = "grid_facebook_access_token_expires";

	/**
	 * @var \Facebook\Facebook
	 */
	private $api;
	private $sdk_js_rendered;

	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );
		add_action('admin_init', array($this, 'check_access_token'));
		add_action('admin_notices', array($this, 'admin_notices'));
	}

	/**
	 * handle access token automatic extension
	 */
	function check_access_token(){

		// if is set than check expiration
		$willExpire = $this->willAccessTokenExpire();

		if( $willExpire === null ){

			// check if base configuration is set
			$fb = $this->getApi();
			if($fb === null) return;
			$defaultAccessToken = $fb->getDefaultAccessToken();
			if($defaultAccessToken === null) return;

			// we dont know if it will expire ask facebook
			$tokenMeta = $fb->getOAuth2Client()->debugToken($defaultAccessToken->getValue());
			$this->setWillAccessTokenExpire($tokenMeta);
		}
	}

	/**
	 * show notice if facebook does something noticeable
	 */
	function admin_notices(){
		$willExpire = $this->willAccessTokenExpire();
		if($willExpire === null){
			?>
			<div class="notice notice-warning">
				<h2>Facebook API</h2>
				<p>
					You do not seem to have a valid access token for grid social boxes.
					<a href="<?php echo $this->getSelfURL(); ?>">Settings</a>
				</p>
			</div>
			<?php
		} else if($willExpire == true){
			$timestamp = get_option(self::OPTION_ACCESS_TOKEN_EXPIRES);
			$date = new \DateTime();
			$date->setTimestamp($timestamp);
			$date->setTimezone(new \DateTimeZone(get_option('timezone_string', 'UTC')));
			$tokenValidTill = $date->format(get_option( 'date_format' )." ".get_option('time_format'));
			?>
			<div class="notice notice-warning">
				<h2>Facebook API</h2>
				<p>
					Access token will expired shortly for grid social boxes. Valid till:
					<?php echo $tokenValidTill; ?><br/>
					<a href="<?php echo $this->getSelfURL(); ?>">Goto settings and renew</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * @param \Facebook\Authentication\AccessTokenMetadata|null $tokenMeta
	 */
	public function setWillAccessTokenExpire($tokenMeta){
		if(is_a($tokenMeta, '\Facebook\Authentication\AccessTokenMetadata') && $tokenMeta->getIsValid()){
			$datetime = $tokenMeta->getExpiresAt();
			if($datetime != null){
				update_option(self::OPTION_ACCESS_TOKEN_EXPIRES, $datetime->getTimestamp());
				return;
			}
		}
		update_option(self::OPTION_ACCESS_TOKEN_EXPIRES, 10);

	}

	/**
	 * @param int $daysOffset
	 *
	 * @return bool|null
	 */
	public function willAccessTokenExpire($daysOffset = 14){
		$expires = get_option(self::OPTION_ACCESS_TOKEN_EXPIRES, null);
		if($expires === null) return null;
		$expires = intval($expires);
		$nowWithOffset = time() - $daysOffset * 24 * 60 * 60;
		return $expires > $nowWithOffset;
	}


	public function getSlug() {
		return Settings::TYPE_FACEBOOK;
	}

	public function getTitle() {
		return __( "Facebook", "grid-social-boxes" );
	}

	/**
	 * @return \Facebook\Facebook
	 */
	public function getApi() {

		/**
		 * check if options are saved
		 */
		if ( empty( get_site_option( self::OPTION_APP_ID ) )
		     || empty( get_site_option( self::OPTION_SECRET ) )
		) {
			return NULL;
		}

		if ( $this->api == NULL ) {
			$this->settings->plugin->include_facebook_sdk();
			try{
				$this->api = new \Facebook\Facebook( array(
					'app_id'                => get_site_option( self::OPTION_APP_ID ),
					'app_secret'            => get_site_option( self::OPTION_SECRET ),
					'default_access_token'  => get_site_option( self::OPTION_APP_TOKEN, '' ),
					'default_graph_version' => 'v2.10',
				) );
			} catch ( FacebookSDKException $e ) {
				error_log($e->getMessage());
				return NULL;
			}

		}

		return $this->api;
	}

	private function getCallbackUrl(){
		return $this->getSelfURL( array( "noheader" => false, 'facebook_callback_url' => 'it-is' ));
	}

	private function getFacebookLoginUrl(){
		$api = $this->getApi();
		$helper = $api->getRedirectLoginHelper();
		return $helper->getLoginUrl(
			$this->getCallbackUrl(),
			array('manage_pages','pages_show_list', 'public_profile')
		);
	}

	private function redirectToFacebookLoginUrl(){
		wp_redirect($this->getFacebookLoginUrl());
	}

	/**
	 * render settings page
	 */
	public function renderPage() {

		// first save values
		if ( isset( $_POST[ self::OPTION_APP_ID ] ) ) {
			/**
			 * save options
			 */
			$appToken = sanitize_text_field( $_POST[ self::OPTION_APP_TOKEN ] );
			update_site_option( self::OPTION_APP_ID, sanitize_text_field( $_POST[ self::OPTION_APP_ID ] ) );
			update_site_option( self::OPTION_SECRET, sanitize_text_field( $_POST[ self::OPTION_SECRET ] ) );
			update_site_option( self::OPTION_APP_TOKEN, $appToken );
			update_site_option(self::OPTION_LAZY, (isset($_POST[self::OPTION_LAZY]))? 1: 0);

			if(empty($appToken)){
				try {
					$this->redirectToFacebookLoginUrl();
					exit;
				} catch ( FacebookSDKException $e ) {
					error_log($e->getMessage());
				}
			}
		}

		// second retrieve access token
		if(isset($_GET['facebook_callback_url']) && $_GET['facebook_callback_url'] == 'it-is'){
			try {
				$api = $this->getApi();
				$helper = $api->getRedirectLoginHelper();
				$accessToken = $helper->getAccessToken();

				update_site_option( self::OPTION_APP_TOKEN, (string) $accessToken);

				wp_redirect($this->getSelfURL( array( "noheader" => false ) ));
				exit;
			} catch ( FacebookSDKException $e ) {
				error_log($e->getMessage());
			}
		}

		/**
		 * check for successful connection
		 */
		$fb           = $this->getApi();
		$defaultToken = $fb->getDefaultAccessToken()->getValue();

		if(isset($_GET['facebook_extend']) && $_GET['facebook_extend'] == "yes" ){
			try{
				$this->redirectToFacebookLoginUrl();
			} catch ( FacebookSDKException $e ) {
				var_dump($e);
				exit;
			}
		}

		if(empty($defaultToken)) {
			?>
			<div class="notice notice-warning">
				<p>No access token found!</p>
			</div>
			<?php
		} else {

			$token = $fb->getOAuth2Client()->debugToken($fb->getDefaultAccessToken()->getValue());
			$isTokenValid = $token->getIsValid();

			?>
			<div class="notice notice-success">
				<p>Authorization granted!</p>
				<p style="overflow-wrap: break-word;"><?php
					echo "<strong>Token:</strong> " . $defaultToken;
					?>
				<p><?php echo "<strong>Is valid:</strong> ". (($isTokenValid)? "yes": "no"); ?></p>

				<?php
				if($isTokenValid){
					$date = $token->getExpiresAt();
					$date->setTimezone(new \DateTimeZone(get_option('timezone_string', 'UTC')));
					$tokenValidTill = $date->format(get_option( 'date_format' )." ".get_option('time_format'));
					$extendUrl = $this->getSelfURL(array( "noheader" => true ,"facebook_extend" => "yes"));
					echo "<p><strong>Expires:</strong> " . $tokenValidTill." <a href='$extendUrl'>extend</a></p>";

				}
				?>
			</div>
			<?php

		}

		?>

		<form method="POST"
		      action="<?php echo $this->getSelfURL( array( "noheader" => true ) ); ?>">
			<p>Register a facebook application on <a target="_blank"
			                                         href="https://developers.facebook.com">developers.facebook.com</a>
				and get the <a target="_blank"
				               href="https://developers.facebook.com/tools/accesstoken/">app
					token</a>.</p>
			<?php
			$url = $this->getCallbackUrl();
			echo "<p>Callback Uri: $url</p>";
			?>
			<label>
				App ID:<br>
				<input name="<?php echo self::OPTION_APP_ID; ?>"
				       value="<?php echo get_site_option( self::OPTION_APP_ID ); ?>"/>
			</label>
			<br>
			<label>
				Secret:<br>
				<input name="<?php echo self::OPTION_SECRET; ?>"
				       value="<?php echo get_site_option( self::OPTION_SECRET ); ?>"/>
			</label>
			<br>
			<label>
				App token:<br>
				<input name="<?php echo self::OPTION_APP_TOKEN; ?>"
				       value="<?php echo get_site_option( self::OPTION_APP_TOKEN ); ?>"/>
			</label>
			<br>
			<br>
			<label>
				<input name="<?php echo self::OPTION_LAZY ?>"
					<?php echo (get_site_option(self::OPTION_LAZY))? "checked": "" ?>
				       value="<?php echo get_site_option( self::OPTION_APP_TOKEN ); ?>"
				       type="checkbox"/> Wait for user cookie permission before loading facebook posts.
			</label>
			<?php echo get_submit_button( "Save" ); ?>
		</form>

		<?php

	}

	/**
	 * initialize js for facebook integration
	 *
	 * @param string $lang
	 * @param bool $lazy
	 */
	function init_facebook_sdk_js( $lang = "de_DE" ) {
		wp_enqueue_script(
			\GridSocialBoxes::HANDLE_API_JS,
			$this->settings->plugin->url . "/js/grid-social-boxes-api.js"
		);
		wp_enqueue_script(
			\GridSocialBoxes::HANDLE_FACEBOOK_JS,
			$this->settings->plugin->url . "/grid_facebook_box/facebook.js",
			array( "jquery", \GridSocialBoxes::HANDLE_API_JS ),
			1,
			true
		);
		wp_localize_script(
			\GridSocialBoxes::HANDLE_FACEBOOK_JS,
			"GridSocialBoxes_Facebook",
			apply_filters(
				\GridSocialBoxes::FILTER_FACEBOOK_JS_ARGS,
				array(
					"config"   => array(
						"facebook_app_id" => get_site_option( self::OPTION_APP_ID ),
						"lazy"            => get_site_option( self::OPTION_LAZY) == "1",
						"lang"            => $lang,
					),
					"selector" => array(
						"target" => ".fb-post",
					),
					"i18n"     => array(
						"enable_button" => __( "Enable facebook contents", \GridSocialBoxes::DOMAIN ),
					),
				)
			)
		);
	}



}