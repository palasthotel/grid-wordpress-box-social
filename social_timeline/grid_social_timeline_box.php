<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-Social-Boxes
 */
class grid_social_timeline_box extends grid_static_base_box {

	const PREFIX_TWITTER = "twitter";
	const PREFIX_INSTAGRAM = "instagram";
	const PREFIX_YOUTUBE = "youtube";
	
	public function __construct() {
		parent::__construct();
		$this->content->limit = 5;
		$this->content->sort = 1;
	}

	public function type() {
		return 'social_timeline';
	}
	
	/**
	 * build the content
	 *
	 * @param bool $editmode
	 *
	 * @return $content
	 */
	public function build( $editmode ) {
		
		if(empty($this->content->sort)) $this->content->sort = 1;
		
		if ( $editmode ) {
			$this->content->title = t("Social timeline by User");
			return $this->content;
		} else {
			$content = array();
			global $grid_social_boxes;
			
			/**
			 * get twitter contents
			 */
			if($this->hasTwitter() ){
				/**
				 * @var $connection TwitterOAuth
				 */
				$connection = $grid_social_boxes->get_twitter_api();
				if ( 'retweets' == $this->content->twitter_retweet ) {
					$result = $connection->get(
						'https://api.twitter.com:443/1.1/search/tweets.json?src=typd&q='.$this->content->twitter_user,
						array(
							"count" => $this->content->twitter_limit,
						)
					);
					$result = $result->statuses;
				} else {
					$result = $connection->get(
						'https://api.twitter.com:443/1.1/statuses/user_timeline.json',
						array(
							'screen_name' => $this->content->twitter_user,
							"count" => $this->content->twitter_limit,
						)
					);
				}
				
				foreach( $result as $key => $tweet ){
					$datetime = new DateTime($tweet->created_at);
					$content[] = (object)array(
						"time" => (int) $datetime->format("U"),
						"content" => $tweet,
						"type" => self::PREFIX_TWITTER,
					);
				}
				
			}
			
			/**
			 * get instagram contents
			 */
			if($this->hasInstagram()){
				$api = $grid_social_boxes->get_instagram_api();
				if($api != null){
					$result = $api->getUserMedia('self', $this->content->instagram_count);
					$images = $result->data;
					foreach ($images as $item){
						$src = $item->images->low_resolution->url;
						$content[] = (object)array(
							"time" => (int)$item->created_time,
							"content" => $item,
							"type" => self::PREFIX_INSTAGRAM
						);
					}
				}
			}
			
			/**
			 * sort by timestamp
			 */
			$sort = $this->content->sort;
			usort($content, function($a, $b) use ($sort){
				if($a->time == $b->time){
					return 0;
				}
				return ($a->time > $b->time) ? -1*$sort: $sort;
			});
			
			/**
			 * throw away more than limit items
			 */
			array_splice($content, $this->content->limit);
			
			/**
			 * render items
			 */
			for ($i = 0; $i < count($content); $i++){
				$content[$i]->rendered = $this->renderItem($content[$i]);
			}
			
			return $content;
		}
	}
	
	/**
	 * content structure
	 * @return array
	 */
	public function contentStructure () {
		$cs = parent::contentStructure();
		
		global $grid_social_boxes;
		
		$apis = array();
		
		if($grid_social_boxes->get_twitter_api() != null){
			$twitter = new grid_twitter_box();
			$apis = array_merge(
				$apis,
				array(
					array(
						"label"=> "",
						"text" => __("Configuration for Twitter.", "grid-social-boxes"),
						"type" => "info",
					)
				),
				$this->prefixStructure($twitter->contentStructure(), self::PREFIX_TWITTER)
			);
		}
		
		if($grid_social_boxes->get_instagram_api() != null){
			$instagram = new grid_instagram_box();
			$apis = array_merge(
				$apis,
				array(
					array(
						"label"=> "",
						"text" => __("Configuration for Instagram.", "grid-social-boxes"),
						"type" => "info",
					)
				),
				$this->prefixStructure($instagram->contentStructure(), self::PREFIX_INSTAGRAM)
			);
		}
		
		$apis = array_merge(
			$apis,
			array(
				array(
					"label"=> "",
					"text" => __("Configuration for Youtube coming soon.", "grid-social-boxes"),
					"type" => "info",
				)
			)
		);
		
		if(count($apis) > 0){
			return array_merge(
				$cs,
				array(
					array(
						'key' => 'limit',
						'type' => 'number',
						'label' => 'Anzahl der Einträge insgesamt',
					),
					array(
						'key' => 'sort',
						'label' => __('Sort order', 'grid-social-boxes'),
						'type' => 'select',
						'selections' => array(
							array( 'key' => 1, 'text' => __("Latest first", 'grid-social-boxes')),
							array( 'key' => -1, 'text' => __("Oldest first", 'grid-social-boxes')),
						),
					)
				),
				$apis
			);
		}
		
		return array_merge(
			$cs,
			array(
				array(
					"label"=> "Info",
					"text" => __("You have to confiure the social apis first.", "grid-social-boxes"),
					"type" => "info",
				)
			)
		);
		
	}
	
	/**
	 * check if twitter config is set
	 * @return bool
	 */
	public function hasTwitter(){
		return $this->isWorking(self::PREFIX_TWITTER);
	}
	
	/**
	 * check if instagram config is set
	 * @return bool
	 */
	public function hasInstagram(){
		return $this->isWorking(self::PREFIX_INSTAGRAM);
	}
	
	/**
	 * check if youtube config is set
	 * @return bool
	 */
	public function hasYoutube(){
		return $this->isWorking(self::PREFIX_YOUTUBE);
	}
	
	/**
	 * check api prefix configurations
	 * @param $api_prefix
	 *
	 * @return bool
	 */
	private function isWorking($api_prefix){
		foreach ($this->content as $key => $value){
			if(strpos($key, $api_prefix ) !== false && $value != ""){
				return true;
			}
		}
		return false;
	}
	
	/**
	 * prefix structures
	 *
	 * @param $structure
	 * @param $prefix
	 *
	 * @return mixed
	 */
	private function prefixStructure($structure, $prefix){
		for($i = 0; $i < count($structure); $i++){
			$structure[$i]["key"] = $prefix."_".$structure[$i]["key"];
		}
		return $structure;
	}
	
	/**
	 * @param $item
	 *
	 * @return string
	 */
	private function renderItem($item){
		global $grid_social_boxes;
		$rendered = "";
		
		ob_start();
		if(locate_template("grid/grid-box-social_timeline--".$item->type.".tpl.php") !== ''){
			get_template_part("grid/grid-box-social_timeline--".$item->type.".tpl.php");
		} else {
			require $grid_social_boxes->dir."/templates/grid-box-social_timeline--".$item->type.".tpl.php";
		}
		$rendered = ob_get_contents();
		ob_end_clean();
		return $rendered;
	}

}