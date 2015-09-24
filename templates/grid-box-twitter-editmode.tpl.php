<div class="grid-box-editmode">
	<?php
	echo $content->title;
	if($this->grid){
		$fields = array("limit","user","hashtag","retweet");
		foreach($fields as $field){
			if(!empty($content->{$field})){
				echo "<br />".$field.": ".$content->{$field};
			}
		}
	}
	?>
</div>