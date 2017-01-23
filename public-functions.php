<?php

/**
 * @return \GridSocialBoxes
 */
function grid_social_boxes_plugin(){
	/**
	 * @var $grid_social_boxes GridSocialBoxes
	 */
	global $grid_social_boxes;
	return $grid_social_boxes;
}

/**
 * init facebook sdk JS
 */
function grid_social_boxes_init_facebook_js(){
	$grid_social_boxes = grid_social_boxes_plugin();
	/**
	 * @var $settings \GridSocialBoxes\Settings\Facebook
	 */
	$settings = $grid_social_boxes->settings->pages[\GridSocialBoxes\Settings::TYPE_FACEBOOK];
	$settings->init_facebook_sdk_js();
}

