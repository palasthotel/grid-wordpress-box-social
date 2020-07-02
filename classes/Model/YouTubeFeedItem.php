<?php


namespace Palasthotel\Grid\SocialBoxes\Model;


use DateTime;

class YouTubeFeedItem {
	var $id = "";
	var $title = "";
	var $link = "";
	var $author = "";
	var $description = "";
	var $thumbnailUrl = "";
	/**
	 * @var DateTime
	 */
	var $published;

	/**
	 * @var DateTime
	 */
	var $updated;

	/**
	 * @var string rendered html
	 */
	var $html = "";

}