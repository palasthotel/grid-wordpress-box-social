<?php
/**
 * Created by PhpStorm.
 * User: edward
 * Date: 17.08.16
 * Time: 13:07
 */

namespace GridSocialBoxes\Settings;

use GridSocialBoxes\Settings;

abstract class Base{
	
	/**
	 * @var \GridSocialBoxes
	 */
	public $settings;
	
	/**
	 * Settings constructor.
	 *
	 * @param \GridSocialBoxes $plugin
	 */
	public function __construct(\GridSocialBoxes\Settings $settings) {
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