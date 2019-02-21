<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;

use Facebook\Helpers\FacebookRedirectLoginHelper;
use GridSocialBoxes\Settings;

class Facebook extends Base {

	const OPTION_APP_ID = "grid_facebook_app_id";

	const OPTION_SECRET = "grid_facebook_secret";

	const OPTION_APP_TOKEN = "grid_facebook_app_token";

	/**
	 * @var \Facebook\Facebook
	 */
	private $api;
	private $sdk_js_rendered;

	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );
		$this->sdk_js_rendered = false;
	}


	public function getSlug() {
		return Settings::TYPE_FACEBOOK;
	}

	public function getTitle() {
		return __( "Facebook", "grid-social-boxes" );
	}

	/**
	 * @return \Facebook\Facebook
	 * @throws \Facebook\Exceptions\FacebookSDKException
	 */
	public function getApi() {

		/**
		 * check if options are saved
		 */
		if ( empty( get_site_option( self::OPTION_APP_ID ) )
		     || empty( get_site_option( self::OPTION_SECRET ) )
		     || empty( get_site_option( self::OPTION_APP_TOKEN ) )
		) {
			return NULL;
		}

		if ( $this->api == NULL ) {
			$this->settings->plugin->include_facebook_sdk();
			$this->api = new \Facebook\Facebook( array(
				'app_id'                => get_site_option( self::OPTION_APP_ID ),
				'app_secret'            => get_site_option( self::OPTION_SECRET ),
				'default_access_token'  => get_site_option( self::OPTION_APP_TOKEN ),
				'default_graph_version' => 'v2.5',
			) );
		}

		return $this->api;
	}

	/**
	 * render settings page
	 */
	public function renderPage() {

		if ( isset( $_POST[ self::OPTION_APP_ID ] ) ) {
			/**
			 * save options
			 */
			update_site_option( self::OPTION_APP_ID, sanitize_text_field( $_POST[ self::OPTION_APP_ID ] ) );
			update_site_option( self::OPTION_SECRET, sanitize_text_field( $_POST[ self::OPTION_SECRET ] ) );
			update_site_option( self::OPTION_APP_TOKEN, sanitize_text_field( $_POST[ self::OPTION_APP_TOKEN ] ) );
			wp_redirect( $this->getSelfURL( array( "noheader" => false ) ) );
			exit;
		}

		/**
		 * check for successful connection
		 */
		$fb           = $this->getApi();
		$access_token = NULL;
		if ( $fb != NULL ) {
			$access_token = $fb->getDefaultAccessToken();
		}

		if ( $access_token !== NULL ) {
			?>
			<div class="notice notice-success">
				<p>Authorization granted!</p>
				<p><?php
					echo "Token: " . $access_token->getValue();
					echo "<br>";
					$expires = $access_token->getExpiresAt();
					echo "Expires: " . ( ( $expires == NULL ) ? "Never" : $expires );
					?></p>
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
			<?php echo get_submit_button( "Save" ); ?>
		</form>

		<?php

	}

	/**
	 * initialize js for facebook integration
	 *
	 * @param string $lang
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
						"lazy"            => true,
						"facebook_app_id" => get_site_option( self::OPTION_APP_ID ),
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