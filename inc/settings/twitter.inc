<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;


use Abraham\TwitterOAuth\TwitterOAuth;
use GridSocialBoxes\Settings;

class Twitter extends Base{

	const AUTH_URL = "__grid_social_twitter_auth_callback";
	const AUTH_PARAM = "grid_social_twitter_auth";
	const AUTH_VALUE = "do-authorize";

	/**
	 * @var TwitterOAuth
	 */
	private $api;

	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );

		add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
		add_action( 'init', array( $this, 'add_endpoint' ), 0 );

	}

	public function getSlug() {
		return Settings::TYPE_TWITTER;
	}

	public function getTitle(){
		return __("Twitter", "grid-social-boxes");
	}

	public function getApi(){
		/**
		 * check if options are saved
		 */
		if(get_site_option( 'grid_twitterbox_consumer_key' ) === false || get_site_option( 'grid_twitterbox_consumer_secret') === false ){
			return null;
		}

		if($this->api == null){

			$this->settings->plugin->include_twitter_api();

			$token = get_option( 'grid_twitterbox_accesstoken' );

			if ( $token === false || ! isset( $token['oauth_token'] ) || ! isset( $token['oauth_token_secret'] ) ) {
				$this->api = new TwitterOAuth(
					get_site_option( 'grid_twitterbox_consumer_key', '' ),
					get_site_option( 'grid_twitterbox_consumer_secret', '' )
				);
			} else {
				$this->api = new TwitterOAuth(
					get_option( 'grid_twitterbox_consumer_key' ),
					get_option( 'grid_twitterbox_consumer_secret' ),
					$token['oauth_token'],
					$token['oauth_token_secret']
				);
			}
		}

		return $this->api;
	}

	/**
	 * render settings page
	 */
	public function renderPage(){
		$access_token = get_site_option( 'grid_twitterbox_accesstoken');
		$this->settings->plugin->include_twitter_api();
		$callback_url = get_home_url()."/".self::AUTH_URL;

		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			update_site_option( 'grid_twitterbox_consumer_key', $_POST['grid_twitterbox_consumer_key'] );
			update_site_option( 'grid_twitterbox_consumer_secret', $_POST['grid_twitterbox_consumer_secret'] );
			$key = get_site_option( 'grid_twitterbox_consumer_key', '' );
			$secret = get_site_option( 'grid_twitterbox_consumer_secret', '' );

			if( !empty($secret) || !empty($key) ){

				session_start();

				$connection = new TwitterOAuth( $key, $secret );
				$request_token = null;
				try{
					$request_token = $connection->oauth(
						"oauth/request_token",
						array(
							"oauth_callback" => $callback_url
						)
					);
				} catch (\Exception $e){
					error_log($e, 4);
				}

				if($request_token != null){
					$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
					$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
					$url = $connection->url( 'oauth/authorize', array(
						'oauth_token' => $request_token['oauth_token']
					));
					header( 'Location: ' . $url );
					die();
				}



			} else if($access_token !== false){
				// TODO: revoke token
			}

		} else {


			if( $access_token!== false){
				?>
				<div class="notice notice-success">
					<p>Authorization granted!</p>
					<p><?php
						foreach ($access_token as $key => $val){
							?><strong><?php echo $key; ?>:</strong> <?php echo $val; ?><br /><?php
						}
						?></p>
				</div>
				<?php
			}

			?>

			<form method="POST" action="<?php echo $this->getSelfURL(array("noheader"=>true)); ?>">
				<p><?php _e("Get your credentials at <a href='https://apps.twitter.com/'>Twitter Apps</a>. Create or choose your app and have a look at 'Keys and Access Tokens'.", 'grid-social-boxes'); ?></p>

				<p>
					⚠️ <?php _e('Important: Use as callback url in your twitter app details settings: ', 'grid-social-boxes'); ?><br>
					<b><?php echo $callback_url; ?></b>
				</p>

				<p>
					<label for="grid_twitterbox_consumer_key">Consumer Key:</label><br>
					<input type="text" name="grid_twitterbox_consumer_key" id="grid_twitterbox_consumer_key"
					       value="<?php echo get_site_option( 'grid_twitterbox_consumer_key', '' );?>"><br>
					<label for="grid_twitterbox_consumer_secret">Consumer Secret:</label><br>
					<input type="text" name="grid_twitterbox_consumer_secret" id="grid_twitterbox_consumer_secret"
					       value="<?php echo get_site_option( 'grid_twitterbox_consumer_secret', '' );?>">
				</p>
				<?php echo get_submit_button( "Save" ); ?>
			</form>

			<?php
		}
	}

	public function add_query_vars($vars){
		$vars[] = self::AUTH_PARAM;
		return $vars;
	}
	public function sniff_requests(){
		global $wp;
		if (
				isset( $wp->query_vars[self::AUTH_PARAM] )
				&&
				$wp->query_vars[self::AUTH_PARAM] == self::AUTH_VALUE
		) {
			$this->callback();
			exit;
		}

	}
	public function add_endpoint(){
		add_rewrite_rule(
			'^'.self::AUTH_URL.'$',
			'index.php?'.self::AUTH_PARAM.'='.self::AUTH_VALUE, 'top'
		);
	}

	/**
	 * callback for twitter
	 */
	public function callback() {

		if(!current_user_can('manage_options')){
			wp_die(new \WP_Error(
				1,
				'You have no access to this area 🚨'
			));
		}


		$oauth_verifier = filter_input(INPUT_GET, 'oauth_verifier');

		if (empty($oauth_verifier)) {
			// something's missing, go and login again
			wp_die(new \WP_Error(
					1,
				'No verifier found 🚨'
			));
		}

		session_start();
		if ( empty($_SESSION['oauth_token']) || empty($_SESSION['oauth_token_secret']) ) {
			// something's missing, go and login again
			wp_die(new \WP_Error(
				1,
				'No session found 🚨'
			));
		}

		$this->settings->plugin->include_twitter_api();

		$connection = new TwitterOAuth(
			get_site_option( 'grid_twitterbox_consumer_key', '' ),
			get_site_option( 'grid_twitterbox_consumer_secret', '' ),
			$_SESSION['oauth_token'],
			$_SESSION['oauth_token_secret']
		);

		/* Request access tokens from twitter */
		try{
			$access_token = $connection->oauth( 'oauth/access_token', array(
				'oauth_verifier' => $oauth_verifier
			));
		} catch (\Exception $e){
			wp_die(new \WP_Error(
				1,
				'Could not fetch access token 🚨',
				$e
			));
		}

		update_site_option( 'grid_twitterbox_accesstoken', $access_token );

		wp_die("Authorization successfull ✅");

	}



}