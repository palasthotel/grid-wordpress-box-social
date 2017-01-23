<?php
/**
 * @var $this grid_facebook_feed_box
 */
?>
Facebook Posts
<?php
if($this->grid != null){
	$cs = $this->contentStructure();
	foreach ($cs as $item){
		$value = $this->content->{$item["key"]};
		if($item['type'] == "select"){
			foreach ($item['selections'] as $option){
				if($option['key'] == $value) {
					$value = $option["text"];
					break;
				}
			}
		}
		echo "<br>{$item['label']}: {$value}";
	}
	?>
<?php
}
?>