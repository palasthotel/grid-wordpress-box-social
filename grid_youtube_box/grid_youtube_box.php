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
		$this->content->offset = 0;
	}

	public function build($editmode) {
		if ( $editmode ) {
			return $this->content;
		} else {

			if( ! isset( $this->content->offset ) ) {
				$this->content->offset = 0;
			}
			$this->content->offset = intval($this->content->offset);

			$videos = get_transient($this->getTransientKey());
			if(!is_array($videos)){
				try{
					$videos = $this->getData();
					set_transient($this->getTransientKey(), $videos, 60 * 60 );
				} catch (Exception $e){
					$videos = array();
					error_log($e->getMessage());
				}
			}

			$arr = array();
			foreach ($videos as $video){
				$arr[] = $video->rendered;
			}

			if( isset( $this->content->offset ) && $this->content->offset != 0 ) {
				// remove unwanted videos from list
				for( $i = 0; $i < $this->content->offset; $i++ ) {
					unset( $arr[ $i ] );
					unset( $videos[ $i ] );
				}
			}

			$this->content->videos = $videos;
			$this->content->html = implode("<br>",$arr);

			return $this->content->html;
		}
	}

	public function getTransientKey(){
		return "grid_youtube_box_".md5(json_encode($this->content));
	}

	public function getMaxResultsPlusOffset(){
		$offset = (isset($this->content->offset))? $this->content->offset: 0;
		return intval($this->content->count) + intval($offset);
	}

	public function getData(){
		$grid_social_boxes = grid_social_boxes_plugin();
		$youtube = $grid_social_boxes->get_youtube_api();
		$arr = array();
		if($youtube != null){
			/**
			 * @var $result \Google_Service_YouTube_SearchListResponse
			 */

			switch ($this->content->type){
				case "channel":
					return $this->getChannelData($this->content->q, $this->getMaxResultsPlusOffset());
				case "username":
					return $this->getUsernameData($this->content->q, $this->getMaxResultsPlusOffset());
				case "search":
				default:
					return $this->getSearchData($this->content->q, $this->getMaxResultsPlusOffset());
			}
		}
		return $arr;
	}

	public function getUsernameData($username, $max_results){
		$channels = $this->getChannels(array(
			"forUsername"=> $username,
			// "maxResults" => 1,
		));

		$arr = array();

		foreach ($channels as $channel){

			// dont overshoot max results
			$max_round_results = $max_results - count($arr);
			if($max_round_results < 1) break;

			// get videos of next users channel
			$videos = $this->getChannelData( $channel->id, $max_round_results );
			foreach ($videos as $video){
				$arr[] = $video;
			}

		}

		return $arr;
	}

	public function getChannelData( $channelId, $max_results){
		return $this->getVideos(array(
			"channelId" => $channelId,
			"maxResults" => $max_results,
			"order" => "date",
		));
	}

	public function getSearchData($query, $max_results){
		return $this->getVideos(array(
			"q"=> $query,
			"maxResults" => $max_results,
		));
	}

	/**
	 *
	 * @param $options https://developers.google.com/youtube/v3/docs/channels/list
	 *
	 * @return array array ob channel objects
	 */
	public function getChannels($options){
		$grid_social_boxes = grid_social_boxes_plugin();
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
		$grid_social_boxes = grid_social_boxes_plugin();
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
				/**
				 * @var $thumbnails \Google_Service_YouTube_ThumbnailDetails
				 */
				$thumbnails = $snippet->getThumbnails();

				$sizes = array("Default","High","Medium", "Standard", "Maxres");
				$thumbs = array();
				foreach ($sizes as $size){
					$method = "get".$size;
					if($thumbnails->$method() != null){
						/**
						 * @var $thumbnail \Google_Service_YouTube_Thumbnail
						 */
						$thumbnail = $thumbnails->$method();
						$thumbs[strtolower($size)] = (object)array(
							"url" => $thumbnail->getUrl(),
							"height" => $thumbnail->getHeight(),
							"width" => $thumbnail->getWidth(),
						);
					}
				}

				$videos[] = (object) array(
					"id" => $video->getId()->videoId,
					"title" => $snippet->getTitle(),
					"description" => $snippet->getDescription(),
					"tumbnails" => (object)$thumbs,
					"rendered" => $this->getOembedHTML($video->getId()->videoId),
					"published" => $snippet->getPublishedAt(),
				);
			}
		}
		return $videos;
	}

	/**
	 * @param $videoid
	 * @param array $options
	 *
	 * @return string
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
		$responseCode = curl_getinfo($request, CURLINFO_HTTP_CODE);
		curl_close($request);
		$html = "";
		if($responseCode == 200){
			$result=json_decode($result);
			if(is_object($result)) $html = $result->html;
		}


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
						'text' => 'Channel ID',
					),
					array(
						'key' => 'username',
						'text' => 'Username',
					)
				)
			),
			array(
				'key' => 'count',
				'label' => t( 'Count' ),
				'type' => 'number',
			),
			array(
				'key' => 'offset',
				'label' => t( 'Offset' ),
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
