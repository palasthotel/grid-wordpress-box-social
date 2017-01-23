<?php
/**
 * @var $this grid_social_timeline_box
 * @var $content array
 */
?>
<section class="timeline">
	
	<?php
	foreach ($content as $item){
		echo $item->rendered;
	}
	?>

</section>