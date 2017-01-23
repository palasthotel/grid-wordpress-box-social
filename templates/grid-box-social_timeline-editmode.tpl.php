<?php
/**
 * @var $this grid_social_timeline_box
 */
?>
Social Timeline<?php
if($this->hasTwitter()){
	echo "<br>Twitter working";
}
if($this->hasInstagram()){
	echo "<br>Instagram working";
}
if($this->hasYoutube()){
	echo "<br>Youtube working";
}
if($this->hasFacebook()){
	echo "<br>Facebook working";
}

?>