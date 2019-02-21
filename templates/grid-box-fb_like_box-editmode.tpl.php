<div class="grid-box-editmode">
	Facebook Like Box
	<?php
	if($this->grid){
		$fields = array(
		"fb_page",
		"show_faces",
		"show_header",
		"datastream",
		"language",
		"colorscheme",
		"show_border",
		"force_wall",
		);
		foreach($fields as $field){
			if(!empty($content->{$field})){
				echo "<br />".$field.": ".$content->{$field};
			}
		}
	}
	?>
</div>