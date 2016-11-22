<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;


use GridSocialBoxes\Settings;
use TwitterOAuth;

class Twitter extends Base{
	
	const ADMIN_PAGE_CALLBACK_SLUG = "grid_social_boxes_twitterbox_callback";
	
	/**
	 * @var TwitterOAuth
	 */
	private $api;
	
	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );
		add_action( 'admin_menu', array($this, 'admin_menu') );
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
		$callback_url = add_query_arg( array(
			'page' => self::ADMIN_PAGE_CALLBACK_SLUG,
			'noheader' => true
		), admin_url( 'admin.php' ) );
		
		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			update_site_option( 'grid_twitterbox_consumer_key', $_POST['grid_twitterbox_consumer_key'] );
			update_site_option( 'grid_twitterbox_consumer_secret', $_POST['grid_twitterbox_consumer_secret'] );
			$key = get_site_option( 'grid_twitterbox_consumer_key', '' );
			$secret = get_site_option( 'grid_twitterbox_consumer_secret', '' );
			
			if( !empty($secret) || !empty($key) ){
				
				$connection = new TwitterOAuth( $key, $secret );
				$request_token = $connection->getRequestToken( add_query_arg( array(
					'page' => self::ADMIN_PAGE_CALLBACK_SLUG,
					'noheader' => true
				), admin_url( 'admin.php' ) ) );
				
				session_start();
				$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
				$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
				$url = $connection->getAuthorizeURL( $token );
				header( 'Location: ' . $url );
				die();
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
					<?php _e('Use as callback url: ', 'grid-social-boxes'); ?><br>
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
	
	/**
	 * register submenu page for callback
	 */
	public function admin_menu(){
		add_submenu_page(
			null,
			'Grid Twitter Callback',
			'Grid Twitter Callback',
			'manage_options',
			self::ADMIN_PAGE_CALLBACK_SLUG,
			array( $this, 'callback')
		);
	}
	
	/**
	 * callback for twitter
	 */
	public function callback() {
		
		$this->settings->plugin->include_twitter_api();
		
		session_start();
		$connection = new TwitterOAuth(
			get_site_option( 'grid_twitterbox_consumer_key', '' ),
			get_site_option( 'grid_twitterbox_consumer_secret', '' ),
			$_SESSION['oauth_token'],
			$_SESSION['oauth_token_secret']
		);
		
		/* Request access tokens from twitter */
		$access_token = $connection->getAccessToken( $_REQUEST['oauth_verifier'] );
		update_site_option( 'grid_twitterbox_accesstoken', $access_token );
		echo 'Done! We\'re authenticated';
	}
	
	
	
}