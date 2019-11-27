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

	const TRANSIENT_FETCH_ACCESS_TOKEN_ERROR = "grid_facebook_fetch_access_token_error";
	const TRANSIENT_FETCH_ACCESS_TOKEN_ERROR_VALUE = "error";

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

		// TODO: better use cronjob for this

		// check if base configuration is set
		$fb = $this->getApi();
		if($fb === null) return;
		$defaultAccessToken = $fb->getDefaultAccessToken();
		if($defaultAccessToken === null) return;

		if($this->hasFetchTokenError()){
			// admin notice will be displayed
			return;
		}

		// if is set than check expiration
		$willExpire = $this->willAccessTokenExpire();
		if( $willExpire === true ){
			// is expired so try to automatic get new one
			try{
				$token = $fb->getOAuth2Client()->getLongLivedAccessToken($defaultAccessToken);
				$fb->setDefaultAccessToken($token);
				$this->setWillAccessTokenExpire($fb->getOAuth2Client()->debugToken($token));
				update_option(self::OPTION_APP_TOKEN, (string) $token);

			} catch ( FacebookSDKException $e ) {
				set_transient(self::TRANSIENT_FETCH_ACCESS_TOKEN_ERROR, self::TRANSIENT_FETCH_ACCESS_TOKEN_ERROR_VALUE);
			}
			return;
		}

		if( $willExpire === null ){
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
					<?php echo $tokenValidTill; ?>
				</p>
			</div>
			<?php
		}

		if($this->hasFetchTokenError()){
			?>
			<div class="notice notice-error">
				<h2>Facebook API</h2>
				<p>
					Could not fetch new access token. You please check manually.
					<a href="<?php echo $this->getSelfURL(); ?>">Settings</a>
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
			update_option(self::OPTION_ACCESS_TOKEN_EXPIRES, $tokenMeta->getExpiresAt()->getTimestamp());
		} else {
			update_option(self::OPTION_ACCESS_TOKEN_EXPIRES, 10);
		}
	}

	public function hasFetchTokenError(){
		return get_transient(self::TRANSIENT_FETCH_ACCESS_TOKEN_ERROR) == self::TRANSIENT_FETCH_ACCESS_TOKEN_ERROR_VALUE;
	}

	/**
	 * @param int $daysOffset
	 *
	 * @return bool|null
	 */
	public function willAccessTokenExpire($daysOffset = 14){
		$expires = get_option(self::OPTION_ACCESS_TOKEN_EXPIRES, null);
		if($expires === null) return null;
		return intval($expires) < time() - $daysOffset * 24 * 60 * 60;
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
					$api = $this->getApi();
					$helper = $api->getRedirectLoginHelper();
					$url = $helper->getLoginUrl(
						$this->getSelfURL( array( "noheader" => false, 'facebook_callback_url' => 'it-is' ) ),
						array('manage_pages','pages_show_list', 'public_profile')
					);
					wp_redirect($url);
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
				$token = $fb->getOAuth2Client()->getLongLivedAccessToken($defaultToken);
				update_option(self::OPTION_APP_TOKEN, (string) $token);
				wp_redirect($this->getSelfURL());
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