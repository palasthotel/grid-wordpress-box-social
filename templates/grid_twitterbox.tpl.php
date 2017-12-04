<?php
/**
 * @author Palasthotel <rezeption@palasthotel.de>
 * @copyright Copyright (c) 2014, Palasthotel
 * @license http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @package Palasthotel\Grid-WordPress-Box-Social
 */
/**
 * @var array $content
 */
?>
<?php
	foreach($content as $tweet) {
?>
<div>
	<?php
	$text = (isset($tweet->full_text))? $tweet->full_text: $tweet->text;
	echo '--<br />'.$text
	?>
</div>
<?php
	}
?>