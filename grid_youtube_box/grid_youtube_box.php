<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */

class grid_youtube_box extends grid_static_base_box {

	public function type() {
		return 'youtube';
	}

	public function __construct() {
		parent::__construct();
		$this->content->q = '';
		$this->content->type = 'search';
		$this->content->count = 3;
		$this->content->info = 0;
		$this->content->related = 0;
	}

	public function build($editmode) {
		if ( $editmode ) {
			return $this->content;
		} else {
			$output = "<p>Youtube!</p>";
			
			// TODO: cache output with transient for max 500 calls an hour (api limit)
			
			$arr =  $this->getData();
			
			return implode("<br>",$arr);
		}
	}
	
	public function getData(){
		global $grid_social_boxes;
		$youtube = $grid_social_boxes->get_youtube_api();
		$arr = array();
		if($youtube != null){
			/**
			 * @var $result \Google_Service_YouTube_SearchListResponse
			 */
			
			switch ($this->content->type){
				case "channel":
					return $this->getChannelData();
				case "search":
				default:
					return $this->getSearchData();
			}
			
			
		}
		return $arr;
	}
	
	public function getChannelData(){
		global $grid_social_boxes;
		$youtube = $grid_social_boxes->get_youtube_api();
		$arr = array();
		if($youtube != null){
			/**
			 * @var $result \Google_Service_YouTube_ChannelListResponse
			 */
			$result = $youtube->channels->listChannels("id", array(
				"forUsername"=> $this->content->q,
				"maxResults" => $this->content->count,
			));
			
			if(count($result->getItems()) > 0){
				$channelID = $result->getItems()[0]->getId();
				$result = $youtube->search->listSearch("id", array(
					"channelId"=> $channelID,
					"maxResults" => $this->content->count,
					"order" => "date",
				));
				foreach ($result->getItems() as $key => $value){
					/**
					 * @var $value \Google_Service_YouTube_SearchResult
					 */
					ob_start();
					echo $this->getOembedHTML($value->getId()->videoId);
//					echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/'.$value->getId()->videoId .'" frameborder="0" allowfullscreen></iframe>';
					$arr[] = ob_get_contents();
					ob_end_clean();
				}
			}
		}
		return $arr;
	}
	
	public function getSearchData(){
		global $grid_social_boxes;
		$youtube = $grid_social_boxes->get_youtube_api();
		$arr = array();
		if($youtube != null){
			$result = $youtube->search->listSearch("id,snippet", array(
				"q"=> "cat",
				"maxResults" => $this->content->count,
			));
			foreach ($result->getItems() as $key => $value){
				/**
				 * @var $value \Google_Service_YouTube_SearchResult
				 */
				ob_start();
				echo $this->getOembedHTML($this->getId()->videoId);
//				echo '<iframe width="560" height="315" src="https://www.youtube.com/embed/'.$value->getId()->videoId .'" frameborder="0" allowfullscreen></iframe>';
				$arr[] = ob_get_contents();
				ob_end_clean();
			}
		}
		return $arr;
	}
	
	/**
	 * @param $videoid
	 * @param array $options
	 */
	public function getOembedHTML($videoid, $extend = array()){
		$options = array_merge(array(
			"scheme" => "http",
			"show_info" => 0,
			"related" => 0,
		), $extend);
		
		$content_url = $options["scheme"]."://www.youtube.com/watch?v=".urlencode($videoid);
		$url=$options['scheme']."://www.youtube.com/oembed?url=".$content_url."&format=json";
		
		$request=curl_init($url);
		curl_setopt($request,CURLOPT_RETURNTRANSFER,TRUE);
		curl_setopt($request,CURLOPT_HEADER,FALSE);
		$result=curl_exec($request);
		if($result===FALSE)
		{
			var_dump(curl_error($request));
			die();
		}
		curl_close($request);
		$result=json_decode($result);
		$html = $result->html;
		
		$url_show_info = "&showinfo=";
		if($this->content->info){
			$url_show_info.="1";
		} else {
			$url_show_info.="0";
		}
		$url_related = "&rel=";
		if($this->content->related){
			$url_related.="1";
		} else {
			$url_related.="0";
		}
		
		$html = str_replace("src=\"http://", "src=\"".$options["scheme"]."://", $html);
		$html = str_replace('feature=oembed', 'feature=oembed&wmode=transparent&html5=1'.$url_related.$url_show_info, $html);
		return $html;
	}

	public function contentStructure () {
		$cs = parent::contentStructure();
		
		return array_merge( $cs, array(
			array(
				'key' => 'q',
				'label' => 'Search for',
				'type' => 'text',
 			),
			array(
				'label' => 'Operation type',
				'key' => 'type',
				'type' => 'select',
				'selections' => array(
					array(
						'key' => 'search',
						'text' => 'Search',
					),
					array(
						'key' => 'channel',
						'text' => 'Channel',
					)
				)
			),
			array(
				'key' => 'count',
				'label' => t( 'Count' ),
				'type' => 'number',
			),
			array(
				'key'=>'info',
				'label' => t('Display title info'),
				'type'=>'checkbox',
			),
			array(
				'key'=>'related',
				'label'=>t('Display related videos at the end'),
				'type'=>'checkbox',
			),
		));
	}
}
