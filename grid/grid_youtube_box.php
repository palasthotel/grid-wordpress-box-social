<?php

use Palasthotel\Grid\SocialBoxes\Plugin;

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

			$videos = get_transient($this->getTransientKey( $this->content ));

			if(!is_array($videos)){
				try{
					$videos = $this->getData();

					if(!is_array($videos))
						throw new Exception("grid_youtube_box: getData() result was no array");

					if(count($videos) > 0 && !is_object($videos[0]))
						throw new Exception("grid_youtube_box: getData() results are no objects.");

					set_transient($this->getTransientKey( $this->content ), $videos, 60 * 60 );
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

	public function getTransientKey($object){
		return "grid_youtube_box_".md5(json_encode($object));
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
			try{
				$result = $youtube->channels->listChannels( "id,snippet", $options);
			} catch (Exception $e){
				error_log($e->getMessage());
				return array();
			}

			foreach ($result->getItems() as $channel){
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

		$transient = get_transient( $this->getTransientKey( $options ) );
		if(is_array($transient)){
			return $transient;
		}

		if($youtube != null){
			$result = $youtube->search->listSearch("id,snippet", $options);
			foreach ($result->getItems() as $key => $video){
				$snippet = $video->getSnippet();
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
					"rendered" => Plugin::instance()->oembed->getYouTubeHTML($video->getId()->videoId, [
						"scheme" => "http",
						"show_info" => $this->content->info? "1": "0",
						"related" => $this->content->related ? "1":"0",
					]),
					"published" => $snippet->getPublishedAt(),
				);
			}
		}

		set_transient($this->getTransientKey( $options ), $videos, 60 * 60 );

		return $videos;
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
