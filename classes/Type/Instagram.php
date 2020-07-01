<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace Palasthotel\Grid\SocialBoxes\Type;

use Exception;
use Palasthotel\Grid\SocialBoxes\Settings;

class Instagram extends Base {

	const OPTION_KEY_CLIENT_ID = "grid_instagram_client_id";

	const OPTION_KEY_CLIENT_SECRET = "grid_instagram_client_secret";

	const OPTION_KEY_CLIENT_TOKEN = "grid_instagram_client_token";

	const OPTION_KEY_CODE = "grid_instagram_code";

	const PAGE_CALLBACK = "__grid_social_boxes_instagram___";

	private $api;

	public function __construct( $settings ) {
		parent::__construct( $settings );
		/**
		 * another implementation for callback url
		 * because only two get parameters are allowed for redirection url
		 * with instagram api
		 */
		add_action( 'init', array( $this, 'register_route' ) );
		add_filter( 'query_vars', array( $this, 'query_vars' ), 10, 1 );
		add_action( 'parse_request', array( $this, 'callback' ) );
	}

	public function getSlug() {
		return Settings::TYPE_INSTAGRAM;
	}

	public function getTitle() {
		return __( "Instagram", "grid-social-boxes" );
	}

	/**
	 * get instagram api
	 *
	 * @return \MetzWeb\Instagram\Instagram|null
	 * @throws Exception
	 */
	public function getApi() {
		$key    = get_site_option( self::OPTION_KEY_CLIENT_ID );
		$secret = get_site_option( self::OPTION_KEY_CLIENT_SECRET );
		if ( $key == false || $secret == false ) {
			return NULL;
		}

		if ( $this->api == NULL ) {
			$this->api = new \MetzWeb\Instagram\Instagram( array(
				"apiKey"      => $key,
				"apiSecret"   => $secret,
				"apiCallback" => get_site_url() . "/" . self::PAGE_CALLBACK,
			) );
			$token     = get_site_option( self::OPTION_KEY_CLIENT_TOKEN );
			if ( $token != false ) {
				$this->api->setAccessToken( $token );
			}
		}

		return $this->api;
	}

	/**
	 * render settings page
	 */
	public function renderPage() {

		if ( isset( $_POST ) && ! empty( $_POST ) ) {

			update_site_option( self::OPTION_KEY_CLIENT_ID, $_POST[ self::OPTION_KEY_CLIENT_ID ] );
			update_site_option( self::OPTION_KEY_CLIENT_SECRET, $_POST[ self::OPTION_KEY_CLIENT_SECRET ] );

			$instagram = $this->getApi();

			if ( $instagram == NULL ) {
				die( "error instagram is null" );
			}

			header( 'Location: ' . $instagram->getLoginUrl() );
			die();
		}

		$instagram = $this->getApi();
		if ( $instagram != NULL && get_site_option( self::OPTION_KEY_CLIENT_TOKEN ) !== false ) {
			$user = $instagram->getUser();
			if(is_object($user) && isset($user->data)){
				?>
				<div class="notice notice-success">
					<p>Authorization granted!</p>
					<p>
						<strong>Username: </strong><?php echo $user->data->username; ?>
						<br>
						<strong>Access
							Token: </strong><?php echo get_site_option( self::OPTION_KEY_CLIENT_TOKEN );
						?></p>

				</div>
				<?php
			} else {
				?>
				<div class="notice notice-error">
					<p>Token not valid. Seems to be expired.</p>
					<p><?php
						printf(
							__('Goto %sInstagram Developer%s and renew the API token.', \GridSocialBoxes::DOMAIN),

								'<a href="https://www.instagram.com/developer/">',
								'</a>'

						); ?></p>
				</div>
				<?php
			}

		} else {
			?>
			<p><?php
					printf(
						__('Goto %sInstagram Developer%s and register an App to get the following credentials.', \GridSocialBoxes::DOMAIN),

						'<a href="https://www.instagram.com/developer/">',
						'</a>'

					); ?></p>
			<?php
		}
		?>

		<form method="POST"
		      action="<?php echo $this->getSelfURL( array( "noheader" => true ) ); ?>">
			<p>
				<?php _e( 'Callback URL:', 'grid-social-boxes' ); ?><br>
				<b><?php echo get_site_url() . '/' . self::PAGE_CALLBACK ?></b>
			</p>
			<p>
				<label for="<?php echo self::OPTION_KEY_CLIENT_ID; ?>">Client
					ID:</label><br>
				<input type="text"
				       name="<?php echo self::OPTION_KEY_CLIENT_ID; ?>"
				       id="<?php echo self::OPTION_KEY_CLIENT_ID; ?>"
				       value="<?php echo get_site_option( self::OPTION_KEY_CLIENT_ID, '' ); ?>"><br>
				<label for="<?php echo self::OPTION_KEY_CLIENT_SECRET; ?>">Client
					Secret:</label><br>
				<input type="text"
				       name="<?php echo self::OPTION_KEY_CLIENT_SECRET; ?>"
				       id="<?php echo self::OPTION_KEY_CLIENT_ID; ?>"
				       value="<?php echo get_site_option( self::OPTION_KEY_CLIENT_SECRET, '' ); ?>">
			</p>
			<?php echo get_submit_button( "Save" ); ?>
		</form>
		<?php
	}

	/**
	 * register submenu page for callback
	 */
	public function register_route() {
		add_rewrite_rule(
			'^' . self::PAGE_CALLBACK . '$',
			'index.php?' . self::PAGE_CALLBACK . '=1',
			'top'
		);
	}

	/**
	 * add query vars
	 *
	 * @param $query_vars
	 *
	 * @return array
	 */
	public function query_vars( $query_vars ) {
		$query_vars[] = self::PAGE_CALLBACK;

		return $query_vars;
	}


	/**
	 * callback for twitter
	 */
	public function callback( $query ) {
		/**
		 * var is set?
		 */

		if ( ! empty( $query->query_vars[ self::PAGE_CALLBACK ] )
		     && $query->query_vars[ self::PAGE_CALLBACK ] == "1" ) {

			/**
			 * user may manage options?
			 */
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_redirect( home_url() );
				die();
			}

			/**
			 * instagram sends an error
			 */
			if ( ! empty( $_GET["error"] ) && $_GET ) {
				if ( ! empty( $_GET["error_reason"] ) ) {
					echo "<h1>" . $_GET["error_reason"] . "</h1>";
				}
				if ( ! empty( $_GET["error_description"] ) ) {
					echo "<p>" . $_GET["error_description"] . "</p>";
				}
				die( "<p>Instagram Error!</p>" );
			}

			/**
			 * if there is no code something went wrong
			 */
			if ( empty( $_GET["code"] ) ) {
				$redirect = $this->getSelfURL();
				header( 'Location: ' . $redirect );
				die();
			}

			/**
			 * save code
			 */
			update_site_option( self::OPTION_KEY_CODE, $_GET["code"] );

			/**
			 * ready to auth
			 */
			$instagram = $this->getApi();
			// receive OAuth token object
			$data = $instagram->getOAuthToken( $_GET["code"] );

			update_site_option( self::OPTION_KEY_CLIENT_TOKEN, $data->access_token );

			$username = $data->user->username;

			wp_redirect( $this->getSelfURL() );
			die();
		}
	}

}