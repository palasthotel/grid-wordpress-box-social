<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */

class grid_instagram_box extends grid_static_base_box {

	public function type() {
		return 'instagram';
	}

	public function __construct() {
		parent::__construct();
		$this->content->count = 3;
		$this->content->user = "self";
	}

	public function build($editmode) {
		if ( $editmode ) {
			return $this->content;
		} else {
			$output = "<p>Instagram!</p>";
			
			// TODO: cache output with transient for max 500 calls an hour (api limit)
			
			$arr = array();
			global $grid_social_boxes;
			if($grid_social_boxes != null){
				$api = $grid_social_boxes->get_instagram_api();
				if($api != null){
					$result = $api->getUserMedia($this->content->user, $this->content->count);
					$images = $result->data;
					foreach ($images as $item){
						$src = $item->images->low_resolution->url;
						$arr[] = "<img src='$src' />";
					}
				}
			}
			
			return implode("<br>",$arr);
		}
	}

	public function contentStructure () {
		$cs = parent::contentStructure();
		return array_merge( $cs, array(
			array(
				'key' => 'count',
				'label' => t( 'Count' ),
				'type' => 'number',
			),
			array(
				'key' => 'user',
				'label' => t( 'Different username (optional)' ),
				'type' => 'text',
			),
		));
	}
}
