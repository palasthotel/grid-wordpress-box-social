<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */

class grid_youtube_box extends grid_list_box  {

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

		$channels = $this->getChannels(array(
			"forUsername"=> $this->content->q,
			"maxResults" => $this->content->count,
		));

		$arr = array();

		foreach ($channels as $channel){
			$videos = $this->getVideos(array(
				"channelId" => $channel->id,
				"maxResults" => $this->content->count,
				"order" => "date",
			));
			foreach ($videos as $video){
				$arr[] = $this->getOembedHTML($video->id)->rendered;
			}
		}
		return $arr;
	}

	public function getSearchData(){

		$videos = $this->getVideos(array(
			"q"=> "cat",
			"maxResults" => $this->content->count,
		));
		$arr = array();

		foreach ($videos as $video){
			$arr[] = $video->rendered;
		}

		return $arr;
	}

	/**
	 *
	 * @param $options https://developers.google.com/youtube/v3/docs/channels/list
	 *
	 * @return array array ob channel objects
	 */
	public function getChannels($options){
		global $grid_social_boxes;
		$youtube = $grid_social_boxes->get_youtube_api();
		$channels = array();
		if($youtube != null) {
			/**
			 * @var $result \Google_Service_YouTube_ChannelListResponse
			 */
			$result = $youtube->channels->listChannels( "id,snippet", $options);
			foreach ($result->getItems() as $channel){
				/**
				 * @var $channel \Google_Service_YouTube_Channel
				 */
				/**
				 * @var $snippet \Google_Service_YouTube_ChannelSnippet
				 */
				$snippet = $channel->getSnippet();
				$channels[] = (object) array(
					"id" => $channel->getId(),
					"title" => $snippet->getTitle(),
					"description" => $snippet->getDescription(),
				);
			}
		}
		return $channels;
	}

	/**
	 * @param $options https://developers.google.com/youtube/v3/docs/search/list
	 *
	 * @return array array of video objects
	 */
	public function getVideos($options){
		$videos = array();
		global $grid_social_boxes;
		$youtube = $grid_social_boxes->get_youtube_api();
		if($youtube != null){
			$result = $youtube->search->listSearch("id,snippet", $options);
			foreach ($result->getItems() as $key => $video){
				/**
				 * @var $video \Google_Service_YouTube_SearchResult
				 */
				/**
				 * @param $snippet \Google_Service_YouTube_SearchResultSnippet
				 */
				$snippet = $video->getSnippet();
				$videos[] = (object) array(
					"id" => $video->getId()->videpId,
					"title" => $snippet->getTitle(),
					"description" => $snippet->getDescription(),
					"tumbnails" => array("thumbs"),
					"rendered" => $this->getOembedHTML($video->getId()->videoId),
				);
			}
		}
		return $videos;
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
