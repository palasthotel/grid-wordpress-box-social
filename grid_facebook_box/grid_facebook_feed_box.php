<?php

/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */
class grid_facebook_feed_box extends grid_list_box {
	
	const TRANSIENT = "grid_facebook_feed_box_";
	
	const DEFAULTS = array(
		"fb_page"         => "",
		"number_of_items" => 3,
		"type"            => "feed",
	);
	
	public function type() {
		return 'facebook_feed';
	}
	
	public function __construct() {
		parent::__construct();
		$this->content = (object) self::DEFAULTS;
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
			foreach (self::DEFAULTS as $key => $value){
				if(!isset($this->content->{$key})) $this->content->{$key} = $value;
			}
			
			$page = $this->content->fb_page;
			$type = $this->content->type;
			$noi = $this->content->number_of_items;
			
			
			$feed = get_site_transient( self::TRANSIENT . $this->boxid );
			if ( FALSE == $feed ) {
				$feed_object = $this->get_feed($page, $type);
				$feed = $feed_object->decodeBody();
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
			if ( ! empty( $feed ) ) {
				grid_social_boxes_init_facebook_js();
				foreach ( $items as $item ) {
					echo $this->get_post($item, $page);
				}
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
			$feed_object = $fb->get( "/{$page}/{$type}" );
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
		?>
		<div
				id="grid-facebook-post-<?php echo $item->id; ?>"
				class="fb-post"
				data-href="https://www.facebook.com/<?php echo $parts[0] ?>/posts/<?php echo $parts[1]; ?>/"
				data-show-text="true"
		>
			<blockquote
					cite="https://www.facebook.com/<?php echo $parts[0] ?>/posts/<?php echo $parts[1]; ?>/"
					class="fb-xfbml-parse-ignore">
				<?php if("" != $page): ?>
				Posted by <a
						href="https://www.facebook.com/facebook/"><?php echo $page; ?></a>
				on&nbsp;<a
						href="https://www.facebook.com/20531316728/posts/10154009990506729/"><?php echo $item->created_time; ?></a>
				<?php endif; ?>
			</blockquote>
		</div>
		<script>
			document.addEventListener("DOMContentLoaded", function () {
				var post = document.getElementById("grid-facebook-post-<?php echo $item->id; ?>");
				post.setAttribute("data-width", post.parentNode.clientWidth);
			});
		</script>
		<?php
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
