<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace Palasthotel\Grid\SocialBoxes\Type;

use Palasthotel\Grid\SocialBoxes\Settings;

/**
 * @property Settings settings
 */
abstract class Base{

	/**
	 * Settings constructor.
	 *
	 * @param Settings $settings
	 */
	public function __construct( $settings) {
		$this->settings = $settings;
	}
	
	public function getSelfURL( $add = array() ){
		return add_query_arg( array_merge(array(
			'page' => Settings::PAGE_SLUG,
			'tab' => $this->getSlug(),
		), $add), admin_url( 'options-general.php' ) );
	}
	
	abstract public function getSlug();
	abstract public function getTitle();
	abstract public function renderPage();
	
}