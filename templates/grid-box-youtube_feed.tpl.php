<?php
/**
 * @var $this grid_youtube_feed_box
 * @var $content YouTubeFeedItem[]
 */

use Palasthotel\Grid\SocialBoxes\Model\YouTubeFeedItem;

?>
<section class="youtube-feed">
	<h1>My Feed <?php echo count($content) ?></h1>
	<?php
	foreach ($content as $item){
		echo $item->html;
	}
	?>

</section>