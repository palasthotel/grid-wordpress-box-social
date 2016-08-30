<?php
/**
 * @var $this grid_social_timeline_box
 * @var $item object
 */
?>
<div class="timeline__block">
	<div class="timeline__icon timeline__icon--twitter">
		<?php echo ph_get_svg_file("twitter.svg"); ?>
	</div>
	
	<div class="timeline__content">
		<p><?php echo $item->content->text; ?></p>
		<a href="<?php echo $item->user->url; ?>" class="timeline__readmore">weiterlesen</a>
		<span class="timeline__date"><?php echo date("H:i - d.m.Y"); ?></span>
	</div>
</div>