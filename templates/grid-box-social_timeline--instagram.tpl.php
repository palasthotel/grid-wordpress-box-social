<?php
/**
 * @var $this grid_social_timeline_box
 * @var $item object instagram
 * @var $position int incremental counter
 */
?>
<div class="timeline__block">
	<div class="timeline__icon timeline__icon--instagram">
		<?php echo ph_get_svg_file("instagram.svg"); ?>
	</div>
	
	<div class="timeline__content">
		<img src="<?php echo $item->content->images->standard_resolution->url; ?>" alt="">
		<p><?php echo $item->content->caption->text; ?></p>
		<a href="<?php echo $item->content->link; ?>" class="timeline__readmore">weiterlesen</a>
		<span class="timeline__date"><?php echo date("d.m.Y - H:i", $item->time); ?></span>
	</div>
</div>