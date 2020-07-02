<div class="grid-box-editmode">
    <?php echo ($this->grid)? "<b>": ""; ?>
    YouTube Feed
	<?php
	echo ($this->grid) ? "</b>" : "";
	if($this->grid){
		$fields = array("channel_id","numItems","offset", "url");
		foreach($fields as $field){
			if(!empty($content->{$field})){
				echo "<br />".$field.": ".$content->{$field};
			}
		}
	}
	?>
</div>