<?php

use Palasthotel\Grid\SocialBoxes\Plugin;
use Palasthotel\Grid\SocialBoxes\Settings;
use Palasthotel\Grid\SocialBoxes\Type\Facebook;

/**
 * @return Plugin
 */
function grid_social_boxes_plugin(){
	return Plugin::instance();
}

/**
 * init facebook sdk JS
 */
function grid_social_boxes_init_facebook_js($lang = "de_DE"){
	$grid_social_boxes = grid_social_boxes_plugin();
	/**
	 * @var Facebook $settings
	 */
	$settings = $grid_social_boxes->settings->pages[Settings::TYPE_FACEBOOK];
	$settings->init_facebook_sdk_js($lang);
}

