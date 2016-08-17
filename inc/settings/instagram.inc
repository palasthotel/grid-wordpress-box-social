<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;



class Instagram extends Base{
	
	const OPTION_KEY_CLIENT_ID = "grid_instagram_client_id";
	
	public function __construct( \GridSocialBoxes\Settings $settings ) {
		parent::__construct( $settings );
		
		add_action( 'admin_menu', array($this, 'admin_menu') );
	}
	
	public function getSlug() {
		return "instagram";
	}
	
	public function getTitle(){
		return __("Instagram Settings", "grid-social-boxes");
	}
	
	/**
	 * render settings page
	 */
	public function renderPage(){
		
		if ( isset( $_POST ) && ! empty( $_POST ) ) {
			$client_id = $_POST[self::OPTION_KEY_CLIENT_ID];
			update_site_option( self::OPTION_KEY_CLIENT_ID, $client_id );
			
			$redirect = add_query_arg( array( 'page' => 'grid_social_boxes_instagram_callback', 'noheader' => true ), admin_url( 'admin.php' ));
			
			header('Location: https://api.instagram.com/oauth/authorize/?client_id='.$client_id.
			       '&redirect_uri='.$redirect.'&response_type=code');
			die();
		}
		
		$this_url = add_query_arg( array( 'noheader' => true, 'page' => 'grid_social_boxes_settings' ), admin_url( 'options-general.php' ) );
		
		echo $this->getSelfURL();
		?>
		
		<form method="POST" action="<?php echo $this->getSelfURL(); ?>">
			
			<p>
				<label for="<?php echo self::OPTION_KEY_CLIENT_ID; ?>">Client ID:</label>
				<input type="text" name="<?php echo self::OPTION_KEY_CLIENT_ID; ?>" value="<?php echo get_site_option( self::OPTION_KEY_CLIENT_ID, '' );?>">
			</p>
			<?php echo get_submit_button( "Save" ); ?>
		</form>
		
		<p>INSTAGRAM</p>
		<?php
	}
	
	/**
	 * register submenu page for callback
	 */
	public function admin_menu(){
		add_submenu_page(
			null,
			'Grid Intagram Callback',
			'Grid Instagram Callback',
			'manage_options',
			'grid_social_boxes_instagram_callback',
			array( $this, 'callback')
		);
	}
	
}