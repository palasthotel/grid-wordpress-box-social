<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;


use TwitterOAuth;

class Twitter extends Base{
	
	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );
		add_action( 'admin_menu', array($this, 'admin_menu') );
	}
	
	public function getSlug() {
		return "twitter";
	}
	
	public function getTitle(){
		return __("Twitter", "grid-social-boxes");
	}
	
	/**
	 * render settings page
	 */
	public function renderPage(){
		$this->settings->plugin->social_boxes_include_twitter_api();
		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			update_site_option( 'grid_twitterbox_consumer_key', $_POST['grid_twitterbox_consumer_key'] );
			update_site_option( 'grid_twitterbox_consumer_secret', $_POST['grid_twitterbox_consumer_secret'] );
			
			$connection = new TwitterOAuth( get_site_option( 'grid_twitterbox_consumer_key', '' ), get_site_option( 'grid_twitterbox_consumer_secret', '' ) );
			$request_token = $connection->getRequestToken( add_query_arg( array( 'page' => 'grid_social_boxes_twitterbox_callback', 'noheader' => true ), admin_url( 'admin.php' ) ) );
			session_start();
			$_SESSION['oauth_token'] = $token = $request_token['oauth_token'];
			$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
			$url = $connection->getAuthorizeURL( $token );
			header( 'Location: ' . $url );
			die();
		} else {
			
			
			if( get_site_option( 'grid_twitterbox_accesstoken')!== false){
			?>
			<div class="notice notice-success">
				<p>Authorization granted!</p>
			<p><?php
			$token = get_site_option( 'grid_twitterbox_accesstoken');
			foreach ($token as $key => $val){
				?><strong><?php echo $key; ?>:</strong> <?php echo $val; ?><br /><?php
			}
			?></p>
			</div>
			<?php
		}
			?>
			
			<form method="POST" action="<?php echo $this->getSelfURL(array("noheader"=>true)); ?>">
				
				<p>
					<label for="grid_twitterbox_consumer_key">Consumer Key:</label>
					<input type="text" name="grid_twitterbox_consumer_key" value="<?php echo get_site_option( 'grid_twitterbox_consumer_key', '' );?>">
					<label for="grid_twitterbox_consumer_secret">Consumer Secret:</label>
					<input type="text" name="grid_twitterbox_consumer_secret" value="<?php echo get_site_option( 'grid_twitterbox_consumer_secret', '' );?>">
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
			'grid_social_boxes_twitterbox_callback',
			array( $this, 'callback')
		);
	}
	
	/**
	 * callback for twitter
	 */
	public function callback() {
		
		$this->settings->plugin->social_boxes_include_twitter_api();
		
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