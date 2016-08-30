<?php
/**
 * @var $this grid_social_timeline_box
 */
?>
<p>Social Timeline<?php
if($this->hasTwitter()){
	echo "<br>Twitter working";
}
if($this->hasInstagram()){
	echo "<br>Instagram working";
}
if($this->hasYoutube()){
	echo "<br>Youtube working";
}
?></p>