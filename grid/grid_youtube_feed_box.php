<?php

use Palasthotel\Grid\SocialBoxes\Model\YouTubeFeedItem;
use Palasthotel\Grid\SocialBoxes\Plugin;

/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */

class grid_youtube_feed_box extends grid_rss_box  {

	public function type() {
		return 'youtube_feed';
	}

	public function __construct() {
		parent::__construct();
		// numItems and offset comes from grid_rss_box
		$this->content->channel_id = '';
        $this->content->playlist_id = '';
		$this->content->info = 0;
		$this->content->related = 0;
	}

	public function getUrl(){

        if( $this->content->channel_id != '' ) {
            return "https://www.youtube.com/feeds/videos.xml?channel_id={$this->content->channel_id}";
        }

        if( $this->content->playlist_id != '' ) {
            return "https://www.youtube.com/feeds/videos.xml?playlist_id={$this->content->playlist_id}";
        }

		return false;
	}

	public function build($editmode) {
		$this->content->url = $this->getUrl();

		if($editmode){
			return $this->content;
		}

		$result = parent::build($editmode);

		$NSYouTube = "http://www.youtube.com/xml/schemas/2015";
		$NSYahoo = "http://search.yahoo.com/mrss/";

		return array_map(function($entry) use ($NSYahoo, $NSYouTube){
			$item = new YouTubeFeedItem();
			$item->id = $entry->getRaw()->data["child"][$NSYouTube]["videoId"][0]["data"];
			$item->title = $entry->getTitle();
			$item->description = $entry->getRaw()->data["child"][$NSYahoo]["group"][0]["child"][$NSYahoo]["description"][0]["data"];
			$item->thumbnailUrl = $entry->getRaw()->data["child"][$NSYahoo]["group"][0]["child"][$NSYahoo]["content"][0]["attribs"][""]["url"];
			$item->author = $entry->getRaw()->get_author()->name;
			$item->published = new DateTime($entry->getRaw()->get_date("Y-m-d H:i:s"));
			$item->published->setTimezone(wp_timezone());
			$item->updated = new DateTime($entry->getRaw()->get_updated_date("Y-m-d H:i:s"));
			$item->updated->setTimezone(wp_timezone());
			$item->html = Plugin::instance()->oembed->getYouTubeHTML($item->id,[
				"show_info" => $this->content->info? "1": "0",
				"related" => $this->content->related ? "1":"0",
			]);
			return $item;
		}, $result);
	}

	public function contentStructure () {
		$cs = parent::contentStructure();
		$cs = array_filter($cs, function($entry){
			return $entry['key'] != "url";
		});
		return array_merge(
			array(
				array(
					'key' => 'channel_id',
					'label' => 'Channel',
					'type' => 'text',
				),
                array(
                    'key' => 'playlist_id',
                    'label' => 'Playlist',
                    'type' => 'text',
                ),
			),
			$cs,
			array(
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
