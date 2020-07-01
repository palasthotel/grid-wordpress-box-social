<?php

/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */
class grid_facebook_feed_box extends grid_list_box {
	
	const TRANSIENT = "grid_facebook_feed_box_";
	
	public function type() {
		return 'facebook_feed';
	}
	
	public function __construct() {
		parent::__construct();
		$this->content->fb_page = "";
		$this->content->number_of_items = 3;
		$this->content->type = "feed";
	}
	
	public function build( $editmode ) {
		if ( $editmode ) {
			return $this->content;
		} else {
			
			$plugin = grid_social_boxes_plugin();
			$fb     = $plugin->get_facebook_api();
			
			/**
			 * fallback to defaults if not defined
			 */
			$page = (!empty($this->content->fb_page))? $this->content->fb_page : "";
			$type = (!empty($this->content->type))? $this->content->type: "feed";
			$noi = (!empty($this->content->number_of_items))? $this->content->number_of_items: 3;
			
			
			$feed = get_site_transient( self::TRANSIENT . $this->boxid );
			if ( FALSE == $feed ) {
				$feed_object = $this->get_feed($page, $type);
				$feed = $feed_object->getDecodedBody();
				set_site_transient(self::TRANSIENT . $this->boxid , $feed, 60);
			}
			
			if ( ! is_array( $feed ) ) {
				return "<p>No Facebook posts found.</p>";
			}
			
			$feed   = (object) $feed;
			$data   = $feed->data;
			$paging = $feed->paging;
			
			$items = array();
			foreach ( $data as $index => $item ) {
				$item = (object) $item;
				if ( $index >= $noi ) {
					break;
				}
				$items[] = $item;
			}
			
			
			ob_start();
			if ( $overridden_template = locate_template('grid/grid_facebook_feed_box.tpl.php' ) ) {
					require $overridden_template;
				} else {
					require dirname(__FILE__).'/../templates/grid_facebook_feed_box.tpl.php';
				}
			$output = ob_get_contents();
			ob_end_clean();
			
			return $output;
		}
	}
	
	public function get_feed($page, $type){
		$plugin = grid_social_boxes_plugin();
		$fb     = $plugin->get_facebook_api();
		try {
			$feed_object = $fb->get( "/{$page}/{$type}?fields=".GRID_FACEBOOK_FIELDS );
			if ( ! $feed_object->isError() ) {
				return $feed_object;
			}
		} catch ( Exception $e ) {
		}
		return null;
	}
	
	/**
	 * @param {Object} $item facebook feed object
	 *
	 * @return string
	 */
	public function get_post($item, $page = ""){
		$parts = explode( "_", $item->id );
		ob_start();
		if($overridden_template = locate_template("grid/facebook-post.tpl.php")){
			include $overridden_template;
		} else {
			require plugin_dir_path(__FILE__)."../templates/facebook-post.tpl.php";
		}
		$output = ob_get_contents();
		ob_end_clean();
		return $output;
	}
	
	public function contentStructure() {
		$cs = parent::contentStructure();
		
		return array_merge( $cs, array(
			array(
				'key'   => 'fb_page',
				'label' => t( 'Facebook page' ),
				'type'  => 'text',
			),
			array(
				'key'   => 'number_of_items',
				'label' => t( 'Number of posts' ),
				'type'  => 'number',
			),
			array(
				'key'        => 'type',
				'label'      => t( 'Stream type' ),
				'type'       => 'select',
				'selections' => array(
					array(
						'key'  => 'feed',
						'text' => t( 'Feed' ),
					),
					array(
						'key'  => 'posts',
						'text' => t( 'Published posts' ),
					),
					array(
						'key'  => 'promoted_posts',
						'text' => t( 'Promoted posts' ),
					),
				),
			),
		) );
	}
}
